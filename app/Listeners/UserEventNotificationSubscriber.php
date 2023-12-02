<?php

namespace App\Listeners;

use App\Events\EmailUpdated;
use App\Events\PasswordUpdated;
use App\Events\UserDeleted;
use App\Events\WebDavResumed;
use App\Events\WebDavSuspended;
use App\Notifications\EmailUpdatedNotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\PasswordUpdatedNotification;
use App\Notifications\RecoveryCodeReplacedNotification;
use App\Notifications\TwoFactorAuthenticationDisabledNotification;
use App\Notifications\TwoFactorAuthenticationEnabledNotification;
use App\Notifications\UserDeletedNotification;
use App\Notifications\WebDavResumedNotification;
use App\Notifications\WebDavSuspendedNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class UserEventNotificationSubscriber {
    public function handleEmailUpdated(EmailUpdated $event): void {
        $event->user->notify(new EmailUpdatedNotification($event->user));
    }

    public function handlePasswordUpdated(PasswordUpdated $event): void {
        $event->user->notify(new PasswordUpdatedNotification($event->user));
    }

    public function handlePasswordReset(PasswordReset $event): void {
        /**
         * @var \App\Models\User
         */
        $user = $event->user;
        $user->notify(new PasswordResetNotification($user));
    }

    public function handleTwoFactorAuthenticationEnabled(
        TwoFactorAuthenticationConfirmed $event
    ): void {
        $event->user->notify(
            new TwoFactorAuthenticationEnabledNotification($event->user)
        );
    }

    public function handleTwoFactorAuthenticationDisabled(
        TwoFactorAuthenticationDisabled $event
    ): void {
        $event->user->notify(
            new TwoFactorAuthenticationDisabledNotification($event->user)
        );
    }

    public function handleRecoveryCodeReplaced(
        RecoveryCodeReplaced $event
    ): void {
        /**
         * @var \App\Models\User
         */
        $user = $event->user;
        $user->notify(new RecoveryCodeReplacedNotification($user));
    }

    public function handleUserDeleted(UserDeleted $event): void {
        Notification::route('mail', [
            $event->userData['email'] => $event->userData['name'],
        ])->notify(new UserDeletedNotification($event->userData));
    }

    public function handleWebDavSuspended(WebDavSuspended $event): void {
        $event->user->notify(new WebDavSuspendedNotification($event->user));
    }

    public function handleWebDavResumed(WebDavResumed $event): void {
        $event->user->notify(new WebDavResumedNotification($event->user));
    }

    public function subscribe(Dispatcher $events): array {
        return [
            EmailUpdated::class => 'handleEmailUpdated',
            PasswordUpdated::class => 'handlePasswordUpdated',
            PasswordReset::class => 'handlePasswordReset',
            TwoFactorAuthenticationConfirmed::class =>
                'handleTwoFactorAuthenticationEnabled',
            TwoFactorAuthenticationDisabled::class =>
                'handleTwoFactorAuthenticationDisabled',
            RecoveryCodeReplaced::class => 'handleRecoveryCodeReplaced',
            UserDeleted::class => 'handleUserDeleted',
            WebDavSuspended::class => 'handleWebDavSuspended',
            WebDavResumed::class => 'handleWebDavResumed',
        ];
    }
}

