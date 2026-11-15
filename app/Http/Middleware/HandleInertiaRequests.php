<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Only non-personal data is shared: language, direction, interface strings,
     * demo flag and the configured emergency number.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Locale-dependent props are resolved lazily: this runs before the route's
        // SetLocale middleware, so the language is only known when the page renders.
        return [
            ...parent::share($request),
            'locale' => fn (): array => $this->localeProps($request, $this->currentLocale()),
            'translations' => fn (): array => $this->translations($this->currentLocale()),
            'demoMode' => (bool) config('rafiq.demo_mode'),
            'emergency' => [
                'number' => $this->emergencyNumber(),
            ],
        ];
    }

    private function currentLocale(): Locale
    {
        return Locale::tryFrom(App::getLocale()) ?? Locale::default();
    }

    /**
     * @return array{current: string, direction: string, alternate: array{code: string, name: string, url: string}}
     */
    private function localeProps(Request $request, Locale $locale): array
    {
        return [
            'current' => $locale->value,
            'direction' => $locale->direction(),
            'alternate' => [
                'code' => $locale->alternate()->value,
                'name' => $locale->alternate()->nativeName(),
                'url' => $this->alternateUrl($request, $locale->alternate()),
            ],
        ];
    }

    /**
     * The same page in the other language, or that language's home page (L10N-003).
     */
    private function alternateUrl(Request $request, Locale $alternate): string
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name !== null && Route::has($name) && array_key_exists('locale', $route->parameters())) {
            return route($name, [...$route->parameters(), 'locale' => $alternate->value]);
        }

        return route('public.landing', ['locale' => $alternate->value]);
    }

    /**
     * @return array<string, string>
     */
    private function translations(Locale $locale): array
    {
        /** @var array<string, string> $lines */
        $lines = app('translator')->getLoader()->load($locale->value, '*', '*');

        return $lines;
    }

    private function emergencyNumber(): ?string
    {
        $country = (string) config('rafiq.emergency.country');
        $number = config("rafiq.emergency.numbers.{$country}");

        return is_string($number) && $number !== '' ? $number : null;
    }
}
