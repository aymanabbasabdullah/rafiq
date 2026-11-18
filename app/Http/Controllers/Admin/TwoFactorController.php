<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mandatory TOTP 2FA setup on first login and challenge verification on subsequent logins (M11A-FR-01, SEC-007).
 */
class TwoFactorController extends Controller
{
    public function showSetup(Request $request, TotpService $totp): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('admin.login');
        }

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.dashboard');
        }

        if (! is_string($user->two_factor_secret) || $user->two_factor_secret === '') {
            $user->two_factor_secret = $totp->generateSecret();
            $user->save();
        }

        return Inertia::render('Admin/Auth/TwoFactorSetup', [
            'secret' => $user->two_factor_secret,
            'provisioningUri' => $totp->provisioningUri($user->email, $user->two_factor_secret),
            'submitUrl' => route('admin.two-factor.enable'),
            'logoutUrl' => route('admin.logout'),
        ]);
    }

    public function enable(Request $request, TotpService $totp, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = (string) ($user->two_factor_secret ?? '');

        if (! $totp->verify($secret, (string) $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => [__('admin.two_factor.invalid_code')],
            ]);
        }

        $recoveryCodes = $totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
            'last_login_at' => now(),
        ])->save();

        $request->session()->forget('admin_two_factor_pending');
        $request->session()->flash('recovery_codes', $recoveryCodes);

        $audit->record('auth.two_factor.enabled', $user, [], $user);
        $audit->record('auth.login', $user, ['method' => 'totp_setup'], $user);

        return redirect()->route('admin.dashboard');
    }

    public function showChallenge(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('admin.login');
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        if ($request->session()->get('admin_two_factor_pending') !== true) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/TwoFactorChallenge', [
            'submitUrl' => route('admin.two-factor.verify'),
            'logoutUrl' => route('admin.logout'),
        ]);
    }

    public function verifyChallenge(Request $request, TotpService $totp, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('admin.login');
        }

        $code = trim((string) $request->input('code', ''));
        $recoveryCode = trim((string) $request->input('recovery_code', ''));
        $recoveryCandidate = $recoveryCode !== '' ? $recoveryCode : $code;

        $verifiedByTotp = $code !== '' && $totp->verify((string) $user->two_factor_secret, $code);
        $verifiedByRecovery = ! $verifiedByTotp && $recoveryCandidate !== '' && $totp->consumeRecoveryCode($user, $recoveryCandidate);

        if (! $verifiedByTotp && ! $verifiedByRecovery) {
            $audit->record('auth.two_factor.failed', $user, [], $user);

            throw ValidationException::withMessages([
                'code' => [__('admin.two_factor.invalid_code')],
            ]);
        }

        $request->session()->forget('admin_two_factor_pending');
        $user->forceFill(['last_login_at' => now()])->save();

        if ($verifiedByRecovery) {
            $audit->record('auth.two_factor.recovery_used', $user, [], $user);
        }

        $audit->record('auth.login', $user, [
            'method' => $verifiedByRecovery ? 'recovery_code' : 'totp',
        ], $user);

        return redirect()->intended(route('admin.dashboard'));
    }
}
