<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language from the `{locale}` route segment (L10N-002).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locale::tryFrom((string) $request->route('locale'));

        if ($locale === null) {
            abort(404);
        }

        App::setLocale($locale->value);
        URL::defaults(['locale' => $locale->value]);

        if ($request->hasSession() && $request->session()->get('locale') !== $locale->value) {
            $request->session()->put('locale', $locale->value);
        }

        return $next($request);
    }
}
