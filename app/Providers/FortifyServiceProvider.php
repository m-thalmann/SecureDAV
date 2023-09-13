<?php

namespace App\Providers;

use App\Auth\Fortify\Actions\ResetsUserPasswords;
use App\Auth\Fortify\Actions\UpdatesUserPasswords;
use App\Auth\Fortify\Actions\UpdatesUserProfileInformation;
use App\Auth\Fortify\Responses\EmailVerificationNotificationSentResponse;
use App\Auth\Fortify\Responses\FailedPasswordResetLinkRequestResponse;
use App\Auth\Fortify\Responses\FailedPasswordResetResponse;
use App\Auth\Fortify\Responses\LoginLockoutResponse;
use App\Auth\Fortify\Responses\PasswordResetResponse;
use App\Auth\Fortify\Responses\PasswordUpdateResponse;
use App\Auth\Fortify\Responses\ProfileInformationUpdatedResponse;
use App\Auth\Fortify\Responses\SuccessfulPasswordResetLinkRequestResponse;
use App\Auth\Fortify\Responses\VerifyEmailResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse as FailedPasswordResetResponseContract;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\PasswordUpdateResponse as PasswordUpdateResponseContract;
use Laravel\Fortify\Contracts\ProfileInformationUpdatedResponse as ProfileInformationUpdatedResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->registerLoginResponses();
        $this->registerPasswordResetResponses();
        $this->registerUpdateProfileInformationResponses();
        $this->registerUpdatePasswordResponses();
        $this->registerVerifyEmailResponses();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $this->configureLogin();
        $this->configureConfirmPassword();
        $this->configurePasswordReset();
        $this->configureUpdateProfileInformation();
        $this->configureUpdatePassword();
        $this->configureVerifyEmail();

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

    protected function configureConfirmPassword(): void {
        Fortify::confirmPasswordView('auth.confirm-password');
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
            FailedPasswordResetResponseContract::class,
            FailedPasswordResetResponse::class
        );
        $this->app->singleton(
            PasswordResetResponseContract::class,
            PasswordResetResponse::class
        );
    }

    protected function configurePasswordReset(): void {
        Fortify::resetUserPasswordsUsing(ResetsUserPasswords::class);
        Fortify::requestPasswordResetLinkView('auth.forgot-password');

        Fortify::resetPasswordView('auth.reset-password');
    }

    protected function registerUpdateProfileInformationResponses(): void {
        $this->app->singleton(
            ProfileInformationUpdatedResponseContract::class,
            ProfileInformationUpdatedResponse::class
        );
    }

    protected function configureUpdateProfileInformation(): void {
        Fortify::updateUserProfileInformationUsing(
            UpdatesUserProfileInformation::class
        );
    }

    protected function registerUpdatePasswordResponses(): void {
        $this->app->singleton(
            PasswordUpdateResponseContract::class,
            PasswordUpdateResponse::class
        );
    }

    protected function configureUpdatePassword(): void {
        Fortify::updateUserPasswordsUsing(UpdatesUserPasswords::class);
    }

    protected function registerVerifyEmailResponses(): void {
        $this->app->singleton(
            VerifyEmailResponseContract::class,
            VerifyEmailResponse::class
        );
        $this->app->singleton(
            EmailVerificationNotificationSentResponseContract::class,
            EmailVerificationNotificationSentResponse::class
        );
    }

    protected function configureVerifyEmail(): void {
        Fortify::verifyEmailView('auth.verify-email');
    }

    protected function configureRateLimiting(): void {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });
    }
}
