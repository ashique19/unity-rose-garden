<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        Schema::defaultStringLength(191);

        $timezone = config('app.timezone', 'Asia/Dhaka');
        date_default_timezone_set($timezone);
        Date::use(Carbon::class);
        Carbon::setLocale(config('app.locale', 'en'));

        // Force Laravel to generate URLs using the exact APP_URL variable when in production/codespaces
        if (config('app.env') !== 'local' || str_contains(config('app.url'), 'github.dev')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
