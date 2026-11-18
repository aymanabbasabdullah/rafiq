<?php

namespace App\Providers;

use App\Contracts\Safety\SafetyLexicon;
use App\Models\User;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\FakeAiTextGenerator;
use App\Services\Ai\Gemini\GeminiTextGenerator;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\SafetyLexiconProvider;
use App\Support\BootChecks;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(ActiveRulesetResolver::class);
        $this->app->bind(SafetyLexicon::class, SafetyLexiconProvider::class);
        $this->app->scoped(AiSettings::class);

        // Provider chosen explicitly by RAFIQ_AI_PROVIDER (M09-FR-09). "gemini" with a missing key fails
        // visibly (not_configured ⇒ "unavailable" reply); it never falls back to the fake.
        $this->app->bind(AiTextGenerator::class, function (): AiTextGenerator {
            if (config('rafiq.ai.provider') === 'fake' && ! $this->app->isProduction()) {
                return new FakeAiTextGenerator;
            }

            return new GeminiTextGenerator(
                apiKey: (string) config('services.gemini.api_key'),
                model: config('services.gemini.model'),
                baseUrl: (string) config('services.gemini.base_url'),
                timeout: (int) config('services.gemini.timeout'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BootChecks::assertSafeConfiguration(
            environment: (string) $this->app->environment(),
            demoMode: (bool) config('rafiq.demo_mode'),
            debug: (bool) config('app.debug'),
            aiProvider: (string) config('rafiq.ai.provider'),
            sessionLifetime: (int) config('session.lifetime'),
            sessionDriver: (string) config('session.driver'),
        );

        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());

        Password::defaults(function () {
            $rule = Password::min(12);

            return $this->app->runningUnitTests() ? $rule : $rule->uncompromised();
        });

        ResetPassword::createUrlUsing(
            fn (User $user, string $token): string => route('admin.password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]),
        );

        RateLimiter::for('admin-login', function (Request $request): Limit {
            $email = strtolower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('admin-2fa', fn (Request $request): Limit => Limit::perMinute(5)->by(
            ($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip(),
        ));

        // AI limits (AI-013): per session, and per IP keyed by an HMAC so no raw IP is stored in the cache.
        RateLimiter::for('ai-session', fn (Request $request): Limit => Limit::perMinute(app(AiSettings::class)->limits()['per_session_per_minute'])
            ->by('ai-session|'.$request->session()->getId()));

        RateLimiter::for('ai-ip', fn (Request $request): Limit => Limit::perHour(app(AiSettings::class)->limits()['per_ip_per_hour'])
            ->by('ai-ip|'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'))));

        RateLimiter::for('admin', fn (Request $request): Limit => Limit::perMinute(60)->by(
            ($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip(),
        ));

        /**
         * Four-eyes medical review rule (SAFE-003, BR-11-1, BR-11-5):
         * The reviewer must hold a verified Medical Reviewer role and must not be
         * the author or last editor of the item under review.
         */
        Gate::define('four-eyes', function (User $user, ?int $authorId, ?int $lastEditorId = null): bool {
            if (! $user->hasVerifiedMedicalReviewerRole()) {
                return false;
            }

            if ($authorId !== null && $user->id === $authorId) {
                return false;
            }

            if ($lastEditorId !== null && $user->id === $lastEditorId) {
                return false;
            }

            return true;
        });
    }
}
