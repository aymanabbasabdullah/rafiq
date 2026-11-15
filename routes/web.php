<?php

use App\Enums\Locale;
use App\Http\Controllers\Public\GateController;
use App\Http\Controllers\Public\JourneyController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\LegalPageController;
use App\Http\Controllers\Public\RootRedirectController;
use App\Http\Controllers\Public\SessionController;
use App\Http\Controllers\Public\UnderAgeController;
use App\Http\Controllers\Public\WelcomeController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', RootRedirectController::class)->name('root');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', Locale::values())])
    ->middleware(SetLocale::class)
    ->group(function (): void {
        Route::get('/', LandingController::class)->name('public.landing');
        Route::get('/welcome', WelcomeController::class)->name('public.welcome');

        // Journeys (Module-02)
        Route::get('/journeys', [JourneyController::class, 'index'])->name('journeys.index');

        Route::middleware('age')->group(function (): void {
            Route::get('/learn', [JourneyController::class, 'enter'])->name('journeys.learn');

            Route::middleware(['no-store', 'encrypt-history'])->group(function (): void {
                Route::get('/doctor-prep', [JourneyController::class, 'enter'])->name('journeys.doctor_prep');

                Route::middleware('consent')->group(function (): void {
                    Route::get('/concern', [JourneyController::class, 'enter'])->name('journeys.concern');
                    Route::get('/conception', [JourneyController::class, 'enter'])->name('journeys.conception');
                });
            });
        });

        // Gates (Module-03)
        Route::middleware('throttle:30,1')->group(function (): void {
            Route::get('/gate/age', [GateController::class, 'showAge'])->name('gate.age');
            Route::post('/gate/age', [GateController::class, 'storeAge'])->name('gate.age.store');

            Route::middleware('age')->group(function (): void {
                Route::get('/gate/consent', [GateController::class, 'showConsent'])->middleware(['no-store', 'encrypt-history'])->name('gate.consent');
                Route::post('/gate/consent', [GateController::class, 'storeConsent'])->name('gate.consent.store');
                Route::post('/gate/ai-notice', [GateController::class, 'storeAiNotice'])->name('gate.ai-notice');
            });
        });

        Route::get('/under-18', UnderAgeController::class)->middleware(['no-store', 'encrypt-history'])->name('public.under18');
        Route::post('/session/clear', [SessionController::class, 'clear'])->middleware('throttle:30,1')->name('session.clear');

        Route::get('/{page}', LegalPageController::class)
            ->where('page', implode('|', LegalPageController::PAGES))
            ->name('public.legal');
    });
