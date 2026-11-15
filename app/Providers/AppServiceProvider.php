<?php

namespace App\Providers;

use App\Models\User;
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
        //
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
