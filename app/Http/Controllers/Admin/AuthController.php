<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rafiq Admin authentication, password recovery, and password confirmation (M11A-FR-01, SEC-007, SEC-008).
 */
class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return Inertia::render('Admin/Auth/Login', [
            'status' => $request->session()->get('status'),
            'loginUrl' => route('admin.login.store'),
            'forgotPasswordUrl' => route('admin.password.request'),
        ]);
    }

    public function login(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));
        $password = (string) $request->validated('password');

        $candidate = User::query()->where('email', $email)->first();

        if ($candidate !== null && ! $candidate->is_active) {
            $audit->record('auth.login_blocked_inactive', $candidate, ['email' => $email], $candidate);

            throw ValidationException::withMessages([
                'email' => [__('admin.auth.inactive')],
            ]);
        }

        if ($candidate === null || ! Hash::check($password, $candidate->password)) {
            $audit->record('auth.login_failed', $candidate, ['email' => $email], $candidate);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        Auth::login($candidate);
        $request->session()->regenerate();

        if ($candidate->hasTwoFactorEnabled()) {
            $request->session()->put('admin_two_factor_pending', true);

            return redirect()->route('admin.two-factor.challenge');
        }

        return redirect()->route('admin.two-factor.setup');
    }

    public function logout(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $audit->record('auth.logout', $user, [], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showForgotPassword(Request $request): Response
    {
        return Inertia::render('Admin/Auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
            'submitUrl' => route('admin.password.email'),
            'loginUrl' => route('admin.login'),
        ]);
    }

    public function sendResetLink(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        if ($user !== null) {
            PasswordBroker::broker()->sendResetLink(['email' => $email]);
            $audit->record('auth.password_reset_requested', $user, ['email' => $email], $user);
        }

        return back()->with('status', __('passwords.sent'));
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('Admin/Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'submitUrl' => route('admin.password.update'),
        ]);
    }

    public function resetPassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBroker::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($audit): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $audit->record('auth.password_reset', $user, ['email' => $user->email], $user);
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect()->route('admin.login')->with('status', __($status));
    }

    public function showConfirmPassword(): Response
    {
        return Inertia::render('Admin/Auth/ConfirmPassword', [
            'submitUrl' => route('admin.password.confirm.store'),
        ]);
    }

    public function confirmPassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! $user instanceof User || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('admin.dashboard'));
    }
}
