<?php

namespace Tests\Unit\Notifications;

use App\Events\EmailUpdated;
use App\Events\PasswordUpdated;
use App\Events\UserDeleted;
use App\Events\WebDavResumed;
use App\Events\WebDavSuspended;
use App\Models\User;
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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Tests\TestCase;

class UserEventNotificationsTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
    }

    /**
     * @dataProvider eventAndNotificationProvider
     */
    public function testEmailUpdatedEventSendsNotification(
        callable $eventCreator,
        string $notificationClass
    ): void {
        Notification::fake();

        event($eventCreator($this->user));

        Notification::assertSentTo($this->user, $notificationClass, function (
            mixed $notification
        ) {
            $this->assertEquals($this->user->id, $notification->user->id);

            /**
             * @var MailMessage
             */
            $mail = $notification->toMail($this->user);
            $data = $notification->toArray($this->user);

            $this->assertEquals($data['title'], $mail->subject);
            $this->assertEquals($data['body'], $mail->introLines[0]);

            return true;
        });
    }

    public function testUserDeletedEventSendsNotification(): void {
        Notification::fake();

        $this->user->delete();

        event(new UserDeleted($this->user));

        Notification::assertSentOnDemand(function (
            UserDeletedNotification $notification,
            array $channels,
            object $notifiable
        ) {
            $recipient = array_keys($notifiable->routes['mail'])[0];

            $this->assertEquals($this->user->email, $recipient);
            $this->assertEquals(
                $this->user->name,
                $notifiable->routes['mail'][$recipient]
            );
            $this->assertEquals($this->user->id, $notification->userData['id']);

            /**
             * @var MailMessage
             */
            $mail = $notification->toMail($notifiable);
            $data = $notification->toArray($notifiable);

            $this->assertEquals($data['title'], $mail->subject);
            $this->assertEquals($data['body'], $mail->introLines[0]);

            return true;
        });
    }

    public static function eventAndNotificationProvider(): array {
        return [
            [
                fn(User $user) => new EmailUpdated($user),
                EmailUpdatedNotification::class,
            ],
            [
                fn(User $user) => new PasswordUpdated($user),
                PasswordUpdatedNotification::class,
            ],
            [
                fn(User $user) => new PasswordReset($user),
                PasswordResetNotification::class,
            ],
            [
                fn(User $user) => new TwoFactorAuthenticationConfirmed($user),
                TwoFactorAuthenticationEnabledNotification::class,
            ],
            [
                fn(User $user) => new TwoFactorAuthenticationDisabled($user),
                TwoFactorAuthenticationDisabledNotification::class,
            ],
            [
                fn(User $user) => new RecoveryCodeReplaced($user, 'test-code'),
                RecoveryCodeReplacedNotification::class,
            ],
            [
                fn(User $user) => new WebDavSuspended($user),
                WebDavSuspendedNotification::class,
            ],
            [
                fn(User $user) => new WebDavResumed($user),
                WebDavResumedNotification::class,
            ],
        ];
    }
}
