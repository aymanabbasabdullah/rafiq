<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Builds the URL of the current page in each supported language (L10N-003, L10N-013).
 */
class LocalizedUrls
{
    /**
     * @return array<string, string> keyed by locale code
     */
    public static function forRequest(Request $request): array
    {
        $urls = [];

        foreach (Locale::cases() as $locale) {
            $urls[$locale->value] = self::forLocale($request, $locale);
        }

        return $urls;
    }

    /**
     * The current page in the given language, or that language's landing page.
     */
    public static function forLocale(Request $request, Locale $locale): string
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name !== null && Route::has($name) && array_key_exists('locale', $route->parameters())) {
            return route($name, [...$route->parameters(), 'locale' => $locale->value]);
        }

        return route('public.landing', ['locale' => $locale->value]);
    }
}
