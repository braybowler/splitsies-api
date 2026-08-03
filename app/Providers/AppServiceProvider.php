<?php

namespace App\Providers;

use App\Contracts\MagicLinkTokenRepositoryContract;
use App\Repositories\MagicLinkTokenRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MagicLinkTokenRepositoryContract::class,
            MagicLinkTokenRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('magic-link', function (Request $request) {
            return [
                Limit::perHour(5)->by((string) $request->input('email')),
                Limit::perHour(20)->by($request->ip()),
            ];
        });
    }
}
