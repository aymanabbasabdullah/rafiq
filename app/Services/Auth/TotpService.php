<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * RFC 6238 TOTP two-factor authentication and recovery codes for Rafiq Admin (ADM-001, SEC-007).
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    public function generateSecret(int $length = 32): string
    {
        $bytes = random_bytes($length);
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[ord($bytes[$i]) & 31];
        }

        return $secret;
    }

    public function provisioningUri(string $email, string $secret): string
    {
        $issuer = rawurlencode((string) config('app.name', 'Rafiq'));
        $account = rawurlencode($email);

        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=".self::DIGITS.'&period='.self::PERIOD;
    }

    public function codeAt(string $secret, ?int $timestamp = null): string
    {
        $time = $timestamp ?? now()->getTimestamp();
        $counter = intdiv($time, self::PERIOD);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $key = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $truncated, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $normalized = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/^\d{6}$/', $normalized) !== 1 || $secret === '') {
            return false;
        }

        $time = $timestamp ?? now()->getTimestamp();

        foreach ([-1, 0, 1] as $window) {
            if (hash_equals($this->codeAt($secret, $time + ($window * self::PERIOD)), $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    /**
     * Consume a matching recovery code from the user's encrypted recovery list.
     */
    public function consumeRecoveryCode(User $user, string $inputCode): bool
    {
        $normalized = strtoupper(trim($inputCode));

        if ($normalized === '') {
            return false;
        }

        /** @var list<string> $codes */
        $codes = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : [];

        foreach ($codes as $index => $stored) {
            if (hash_equals(strtoupper($stored), $normalized)) {
                unset($codes[$index]);
                $user->two_factor_recovery_codes = array_values($codes);
                $user->save();

                return true;
            }
        }

        return false;
    }

    private function base32Decode(string $secret): string
    {
        $clean = strtoupper((string) preg_replace('/[^A-Z2-7]/i', '', $secret));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($clean); $i < $len; $i++) {
            $val = strpos(self::ALPHABET, $clean[$i]);

            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
