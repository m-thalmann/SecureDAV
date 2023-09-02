<?php

namespace App\Providers;

use App\Auth\Fortify\Actions\ResetsUserPasswords;
use App\Auth\Fortify\Responses\FailedPasswordResetLinkRequestResponse;
use App\Auth\Fortify\Responses\FailedPasswordResetResponse;
use App\Auth\Fortify\Responses\LoginLockoutResponse;
use App\Auth\Fortify\Responses\PasswordResetResponse;
use App\Auth\Fortify\Responses\SuccessfulPasswordResetLinkRequestResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse as FailedPasswordResetResponseContracts;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContracts;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->registerLoginResponses();
        $this->registerPasswordResetResponses();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $this->configureLogin();
        $this->configurePasswordReset();

        $this->configureRateLimiting();
    }

    protected function registerLoginResponses(): void {
        $this->app->singleton(
            LockoutResponseContract::class,
            LoginLockoutResponse::class
        );
    }

    protected function configureLogin(): void {
        Fortify::loginView('auth.login');
    }

    protected function registerPasswordResetResponses(): void {
        $this->app->singleton(
            FailedPasswordResetLinkRequestResponseContract::class,
            FailedPasswordResetLinkRequestResponse::class
        );
        $this->app->singleton(
            SuccessfulPasswordResetLinkRequestResponseContract::class,
            SuccessfulPasswordResetLinkRequestResponse::class
        );

        $this->app->singleton(
            FailedPasswordResetResponseContracts::class,
            FailedPasswordResetResponse::class
        );
        $this->app->singleton(
            PasswordResetResponseContracts::class,
            PasswordResetResponse::class
        );
    }

    protected function configurePasswordReset(): void {
        Fortify::resetUserPasswordsUsing(ResetsUserPasswords::class);
        Fortify::requestPasswordResetLinkView('auth.forgot-password');

        Fortify::resetPasswordView('auth.reset-password');
    }

    protected function configureRateLimiting(): void {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });
    }
}
