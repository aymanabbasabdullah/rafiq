<?php

use App\Enums\Locale;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AiConfigurationController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\Clinical\ReleaseController;
use App\Http\Controllers\Admin\Clinical\RuleController;
use App\Http\Controllers\Admin\Clinical\RuleReviewController;
use App\Http\Controllers\Admin\Content\ArticleController;
use App\Http\Controllers\Admin\Content\ArticleReviewController;
use App\Http\Controllers\Admin\Content\CategoryController;
use App\Http\Controllers\Admin\Content\MarkdownPreviewController;
use App\Http\Controllers\Admin\Content\SourceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Public\AiController;
use App\Http\Controllers\Public\AiDevTestController;
use App\Http\Controllers\Public\AssessmentController;
use App\Http\Controllers\Public\GateController;
use App\Http\Controllers\Public\GlossaryController;
use App\Http\Controllers\Public\JourneyController;
use App\Http\Controllers\Public\KnowledgeController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\LegalPageController;
use App\Http\Controllers\Public\RootRedirectController;
use App\Http\Controllers\Public\SessionController;
use App\Http\Controllers\Public\UnderAgeController;
use App\Http\Controllers\Public\WelcomeController;
use App\Http\Middleware\SetLocale;
use App\Models\RulesetRelease;
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

                // Privacy-safe analytics (Module-11-E): aggregated counters only.
                Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

                // Content management & editorial workflow (Module-11-B)
                Route::prefix('content')->name('content.')->group(function (): void {
                    Route::get('/', [ArticleController::class, 'index'])->name('index');
                    Route::post('/preview', MarkdownPreviewController::class)->middleware('throttle:120,1')->name('preview');
                    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
                    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
                    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
                    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
                    Route::post('/translations/{translation}/{action}', ArticleReviewController::class)
                        ->whereIn('action', ArticleReviewController::ACTIONS)
                        ->name('translations.action');

                    Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
                    Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
                    Route::put('/sources/{source}', [SourceController::class, 'update'])->name('sources.update');

                    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
                    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
                    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
                });

                // AI configuration (Module-11-D), Super Admin only.
                Route::prefix('ai')->name('ai.')->group(function (): void {
                    Route::get('/', [AiConfigurationController::class, 'index'])->name('index');
                    Route::post('/draft', [AiConfigurationController::class, 'storeDraft'])->name('draft.store');
                    Route::put('/draft/{configuration}', [AiConfigurationController::class, 'updateDraft'])->name('draft.update');
                    Route::post('/draft/{configuration}/evaluate', [AiConfigurationController::class, 'evaluate'])->name('draft.evaluate');
                    Route::post('/disable', [AiConfigurationController::class, 'disable'])->name('disable');
                    Route::middleware('password.confirm:admin.password.confirm')->group(function (): void {
                        Route::post('/draft/{configuration}/activate', [AiConfigurationController::class, 'activate'])->name('draft.activate');
                        Route::get('/draft/{configuration}/activate', fn () => redirect()->route('admin.ai.index'));
                    });
                });

                // Clinical rules management (Module-11-C). Conditions and priorities come from Git only.
                Route::prefix('clinical')->name('clinical.')->group(function (): void {
                    Route::get('/rules', [RuleController::class, 'index'])->name('rules.index');
                    Route::get('/rules/{version}', [RuleController::class, 'show'])->name('rules.show');
                    Route::post('/rules/{version}/{action}', RuleReviewController::class)
                        ->whereIn('action', RuleReviewController::ACTIONS)
                        ->name('rules.action');

                    Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.index');
                    Route::middleware('password.confirm:admin.password.confirm')->group(function (): void {
                        Route::post('/releases/{release}/publish', [ReleaseController::class, 'publish'])->name('releases.publish');
                        // After password confirmation the browser returns here with GET: go back to the release.
                        Route::get('/releases/{release}/publish', fn (RulesetRelease $release) => redirect()->route('admin.clinical.releases.index', ['release' => $release->version]));
                    });
                    Route::post('/releases/{release}/{action}', [ReleaseController::class, 'action'])
                        ->whereIn('action', ReleaseController::ACTIONS)
                        ->name('releases.action');
                });
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
            // J1 Learn = Knowledge Center (Module-08)
            Route::get('/learn', [KnowledgeController::class, 'index'])->name('journeys.learn');
            Route::post('/learn/search', [KnowledgeController::class, 'search'])->middleware('throttle:60,1')->name('learn.search');
            Route::get('/learn/article/{key}', [KnowledgeController::class, 'articleByKey'])->where('key', '[a-z0-9-]+')->name('learn.article.key');
            Route::get('/learn/{category}', [KnowledgeController::class, 'category'])->name('learn.category');
            Route::get('/learn/{category}/{slug}', [KnowledgeController::class, 'article'])->name('learn.article');
            Route::get('/glossary', GlossaryController::class)->name('glossary.index');

            // Rafiq AI companion (Module-09): only on explicit request, after the AI notice.
            Route::get('/ai/suggestions', [AiController::class, 'suggestions'])->middleware('throttle:60,1')->name('ai.suggestions');
            Route::post('/ai/messages', [AiController::class, 'message'])
                ->middleware(['no-store', 'ai-notice', 'throttle:ai-session', 'throttle:ai-ip'])
                ->name('ai.messages');

            // Development-only Postman test of the real pipeline (never registered outside local/testing;
            // 404 unless RAFIQ_AI_DEV_TEST_ROUTE=true). Same gates and limits as the visitor endpoint.
            if (app()->environment(['local', 'testing'])) {
                Route::post('/dev/ai/messages', [AiDevTestController::class, 'message'])
                    ->middleware(['no-store', 'ai-notice', 'throttle:ai-session', 'throttle:ai-ip'])
                    ->name('ai.dev.messages');
            }

            Route::middleware(['no-store', 'encrypt-history'])->group(function (): void {
                Route::get('/doctor-prep', [JourneyController::class, 'enter'])->name('journeys.doctor_prep');

                Route::middleware('consent')->group(function (): void {
                    Route::get('/concern', [JourneyController::class, 'enter'])->name('journeys.concern');
                    Route::get('/conception', [JourneyController::class, 'enter'])->name('journeys.conception');

                    // Safety screening and evaluation (Module-04/05): JSON only, nothing stored.
                    Route::middleware('throttle:30,1')->group(function (): void {
                        Route::post('/assessment/screen', [AssessmentController::class, 'screen'])->name('assessment.screen');
                        Route::post('/assessment/evaluate', [AssessmentController::class, 'evaluate'])->name('assessment.evaluate');
                    });
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
