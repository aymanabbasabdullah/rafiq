<?php

namespace App\Providers;

use App\Support\BootChecks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

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
    }
}
