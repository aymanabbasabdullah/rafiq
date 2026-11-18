<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the active application locale for Rafiq Admin from the admin's `preferred_locale` (M11A-FR-07).
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $candidate = $user instanceof User
            ? $user->preferred_locale
            : ($request->hasSession() ? (string) $request->session()->get('locale', '') : '');

        $locale = Locale::tryFrom((string) $candidate) ?? Locale::default();

        App::setLocale($locale->value);

        return $next($request);
    }
}
