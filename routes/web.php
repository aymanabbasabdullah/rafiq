<?php

use App\Enums\Locale;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TwoFactorController;
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

// Rafiq Admin (Module-11-A)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['admin.locale', 'no-store', 'encrypt-history'])
    ->group(function (): void {
        Route::middleware('guest')->group(function (): void {
            Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.store');
            Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
            Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:admin-login')->name('password.email');
            Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
            Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:admin-login')->name('password.update');
        });

        Route::middleware(['auth', 'admin.active', 'throttle:admin'])->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::patch('/locale', [DashboardController::class, 'updateLocale'])->name('locale.update');

            Route::get('/two-factor-setup', [TwoFactorController::class, 'showSetup'])->name('two-factor.setup');
            Route::post('/two-factor-setup', [TwoFactorController::class, 'enable'])->middleware('throttle:admin-2fa')->name('two-factor.enable');
            Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
            Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:admin-2fa')->name('two-factor.verify');

            Route::middleware('2fa')->group(function (): void {
                Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

                Route::get('/confirm-password', [AuthController::class, 'showConfirmPassword'])->name('password.confirm');
                Route::post('/confirm-password', [AuthController::class, 'confirmPassword'])->middleware('throttle:admin-login')->name('password.confirm.store');

                Route::middleware('password.confirm:admin.password.confirm')->group(function (): void {
                    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
                    Route::match(['post', 'put'], '/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('users.roles');
                    Route::match(['post', 'patch'], '/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
                    Route::post('/users/{user}/reset-two-factor', [AdminUserController::class, 'resetTwoFactor'])->name('users.reset-two-factor');
                });

                Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');
            });
        });
    });

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
