<?php

use App\Http\Middleware\EncryptSensitiveHistory;
use App\Http\Middleware\EnsureAgeConfirmed;
use App\Http\Middleware\EnsureAiNoticeAcknowledged;
use App\Http\Middleware\EnsureAssessmentConsent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoStoreCache;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\InertiaErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'locale' => SetLocale::class,
            'no-store' => NoStoreCache::class,
            'age' => EnsureAgeConfirmed::class,
            'consent' => EnsureAssessmentConsent::class,
            'ai-notice' => EnsureAiNoticeAcknowledged::class,
            'encrypt-history' => EncryptSensitiveHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->dontFlash(config('rafiq.sensitive_keys', []));

        $exceptions->respond(
            fn (Response $response, Throwable $exception, Request $request): Response => InertiaErrorPage::render($response, $request),
        );
    })->create();
