<?php

use App\Enums\Locale;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\RootRedirectController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', RootRedirectController::class)->name('root');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', Locale::values())])
    ->middleware(SetLocale::class)
    ->group(function (): void {
        Route::get('/', LandingController::class)->name('public.landing');
    });
