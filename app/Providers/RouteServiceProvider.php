<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path default setelah login atau redirect lain yang butuh HOME.
     */
    public const HOME = '/dashboard';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // (opsional) limiter yang kamu perlukan
        RateLimiter::for('uploads', function (Request $request) {
            return [Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())];
        });

        RateLimiter::for('pdf', function (Request $request) {
            return [Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())];
        });

        // ⬇️ WAJIB: daftar routes web & api
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
