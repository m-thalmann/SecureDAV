<?php

namespace App\Providers;

use App\Jobs\RunBackup;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/browse';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')->group(base_path('routes/web.php'));

            Route::prefix('dav')->group(base_path('routes/dav.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting() {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(authUser()?->id ?: $request->ip());
        });

        RateLimiter::for('backups', function (RunBackup $job) {
            return Limit::perMinutes(5, 2)->by(
                $job->backupConfiguration->user_id
            );
        });
    }
}
