<?php

namespace App\Providers;

use App\Auth\Fortify\Actions\ConfirmTwoFactorAuthentication;
use App\Auth\Fortify\Actions\CreatesNewUsers;
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
use App\Auth\Fortify\Responses\RecoveryCodesGeneratedResponse;
use App\Auth\Fortify\Responses\RegisterResponse;
use App\Auth\Fortify\Responses\SuccessfulPasswordResetLinkRequestResponse;
use App\Auth\Fortify\Responses\TwoFactorDisabledResponse;
use App\Auth\Fortify\Responses\TwoFactorConfirmedResponse;
use App\Auth\Fortify\Responses\TwoFactorEnabledResponse;
use App\Auth\Fortify\Responses\VerifyEmailResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication as BaseConfirmTwoFactorAuthentication;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse as FailedPasswordResetResponseContract;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\PasswordUpdateResponse as PasswordUpdateResponseContract;
use Laravel\Fortify\Contracts\ProfileInformationUpdatedResponse as ProfileInformationUpdatedResponseContract;
use Laravel\Fortify\Contracts\RecoveryCodesGeneratedResponse as RecoveryCodesGeneratedResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Contracts\TwoFactorConfirmedResponse as TwoFactorConfirmedResponseContract;
use Laravel\Fortify\Contracts\TwoFactorDisabledResponse as TwoFactorDisabledResponseContract;
use Laravel\Fortify\Contracts\TwoFactorEnabledResponse as TwoFactorEnabledResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Fortify::ignoreRoutes();

        $this->registerLoginResponses();
        $this->registerRegistrationResponses();
        $this->registerPasswordResetResponses();
        $this->registerUpdateProfileInformationResponses();
        $this->registerUpdatePasswordResponses();
        $this->registerVerifyEmailResponses();
        $this->registerTwoFactorAuthenticationResponses();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $this->configureLogin();
        $this->configureRegistration();
        $this->configureConfirmPassword();
        $this->configurePasswordReset();
        $this->configureUpdateProfileInformation();
        $this->configureUpdatePassword();
        $this->configureVerifyEmail();
        $this->configureTwoFactorAuthentication();

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

    protected function configureRegistration(): void {
        Fortify::createUsersUsing(CreatesNewUsers::class);

        Fortify::registerView('auth.register');
    }

    protected function registerRegistrationResponses(): void {
        $this->app->singleton(
            RegisterResponseContract::class,
            RegisterResponse::class
        );
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

    protected function registerTwoFactorAuthenticationResponses(): void {
        $this->app->singleton(
            TwoFactorEnabledResponseContract::class,
            TwoFactorEnabledResponse::class
        );
        $this->app->singleton(
            TwoFactorDisabledResponseContract::class,
            TwoFactorDisabledResponse::class
        );
        $this->app->singleton(
            TwoFactorConfirmedResponseContract::class,
            TwoFactorConfirmedResponse::class
        );
        $this->app->singleton(
            RecoveryCodesGeneratedResponseContract::class,
            RecoveryCodesGeneratedResponse::class
        );
    }

    protected function configureTwoFactorAuthentication(): void {
        Fortify::twoFactorChallengeView('auth.two-factor-challenge');

        $this->app->bind(
            BaseConfirmTwoFactorAuthentication::class,
            ConfirmTwoFactorAuthentication::class
        );
    }

    protected function configureRateLimiting(): void {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });
    }
}

