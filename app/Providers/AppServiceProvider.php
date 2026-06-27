<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use App\View\Composers\HomeLcpComposer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Hostinger layout: Laravel root == public_html, Vite build lives at /build (not /public/build).
        if (is_file(base_path('build/manifest.json'))) {
            $this->app->usePublicPath(base_path());
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        View::composer('app', HomeLcpComposer::class);

        RateLimiter::for('erp-proxy-general', function (Request $request) {
            return [
                Limit::perMinute(90)->by('erp-proxy-general:'.$request->ip()),
            ];
        });

        RateLimiter::for('erp-proxy-write', function (Request $request) {
            return [
                Limit::perMinute(30)->by('erp-proxy-write:'.$request->ip()),
            ];
        });
    }
}
