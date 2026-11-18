<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders bilingual Inertia error pages (FR-101).
 *
 * The 503 page carries the general safety message (SAFE-009) so a visitor who
 * may need urgent care is never left without guidance.
 */
class InertiaErrorPage
{
    /**
     * @var list<int>
     */
    private const HANDLED_STATUSES = [403, 404, 419, 429, 500, 503];

    public static function render(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();

        if (! in_array($status, self::HANDLED_STATUSES, true) || $request->expectsJson()) {
            return $response;
        }

        if ($status === 500 && config('app.debug')) {
            return $response;
        }

        App::setLocale(self::localeFor($request)->value);

        Inertia::share(app(HandleInertiaRequests::class)->share($request));

        return app(SecurityHeaders::class)->handle(
            $request,
            fn (): Response => Inertia::render('Errors/Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status),
        );
    }

    private static function localeFor(Request $request): Locale
    {
        $fromUrl = Locale::tryFrom((string) $request->segment(1));

        if ($fromUrl !== null) {
            return $fromUrl;
        }

        $fromSession = $request->hasSession() ? Locale::tryFrom((string) $request->session()->get('locale')) : null;

        return $fromSession
            ?? Locale::tryFrom((string) $request->getPreferredLanguage(Locale::values()))
            ?? Locale::default();
    }
}
