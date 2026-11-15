<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces mandatory TOTP 2FA enrollment and challenge verification before any admin page (M11A-FR-01, M11A-FR-02, SEC-007).
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasTwoFactorEnabled()) {
            return $request->expectsJson()
                ? response()->json(['code' => 'two_factor_setup_required'], 403)
                : redirect()->route('admin.two-factor.setup');
        }

        if ($request->hasSession() && $request->session()->get('admin_two_factor_pending') === true) {
            return $request->expectsJson()
                ? response()->json(['code' => 'two_factor_challenge_required'], 403)
                : redirect()->route('admin.two-factor.challenge');
        }

        return $next($request);
    }
}
