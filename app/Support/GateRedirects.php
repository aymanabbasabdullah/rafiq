<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Journey;
use Illuminate\Support\Facades\Route;

/**
 * Where the age and consent gates send a visitor afterwards.
 *
 * Only named public routes from this allow-list are accepted, so the `intended`
 * value can never become an open redirect (Module-03 §8).
 */
class GateRedirects
{
    /**
     * @var list<string>
     */
    public const INTENDED_ROUTES = [
        'public.landing',
        'journeys.index',
        'journeys.learn',
        'journeys.concern',
        'journeys.conception',
        'journeys.doctor_prep',
    ];

    /**
     * The given route name if it is an allowed destination, otherwise null.
     */
    public static function allowed(?string $routeName): ?string
    {
        if ($routeName === null || ! in_array($routeName, self::INTENDED_ROUTES, true) || ! Route::has($routeName)) {
            return null;
        }

        $journey = Journey::fromRouteName($routeName);

        if ($journey !== null && ! $journey->isEnabled()) {
            return null;
        }

        return $routeName;
    }

    /**
     * URL for an allowed destination, falling back to the landing page.
     */
    public static function urlFor(?string $routeName): string
    {
        return route(self::allowed($routeName) ?? 'public.landing');
    }
}
