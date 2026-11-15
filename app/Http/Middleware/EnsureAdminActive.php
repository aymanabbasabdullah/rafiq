<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated admin account is active and holds at least one active role (M11A-FR-02, SEC-007).
 */
class EnsureAdminActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->is_active) {
            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['code' => 'admin_inactive'], 403);
            }

            return redirect()->route('admin.login')->withErrors([
                'email' => __('admin.auth.inactive'),
            ]);
        }

        if ($user->activeRoleKeys() === []) {
            abort(403);
        }

        return $next($request);
    }
}
