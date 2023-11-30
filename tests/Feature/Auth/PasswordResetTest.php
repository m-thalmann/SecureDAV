<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testRequestPasswordResetScreenCanBeRendered(): void {
        $response = $this->get('/forgot-password');

        $response->assertOk();
    }

    public function testPasswordResetEmailCanBeRequested(): void {
        Notification::fake();

        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testPasswordResetEmailRequestFailsIfUserDoesntExist(): void {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'not-a-user@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    }

    public function testPasswordResetEmailRequestIsRateLimitedAfterOneSuccessfulRequest(): void {
        Notification::fake();

        $user = $this->createUser();

        $request1 = $this->post('/forgot-password', ['email' => $user->email]);

        $this->assertRequestHasSessionMessage(
            $request1,
            SessionMessage::TYPE_SUCCESS,
            key: 'session-message',
            additionalChecks: function (SessionMessage $message) {
                $this->assertEquals(
                    __(Password::RESET_LINK_SENT),
                    $message->message
                );
            }
        );

        $request2 = $this->post('/forgot-password', ['email' => $user->email]);

        $this->assertRequestHasSessionMessage(
            $request2,
            SessionMessage::TYPE_ERROR,
            key: 'session-message',
            additionalChecks: function (SessionMessage $message) {
                $this->assertEquals(
                    __(Password::RESET_THROTTLED),
                    $message->message
                );
            }
        );

        Notification::assertSentTo($user, ResetPassword::class, 1);
    }

    public function testResetPasswordScreenCanBeRendered(): void {
        Notification::fake();

        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            ResetPassword $notification
        ) {
            $response = $this->get('/reset-password/' . $notification->token);

            $response->assertOk();

            return true;
        });
    }

    public function testPasswordCanBeResetWithValidToken(): void {
        Notification::fake();

        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            ResetPassword $notification
        ) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        });
    }

    public function testPasswordResetFailsWithInvalidEmail(): void {
        Notification::fake();

        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            ResetPassword $notification
        ) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => 'invalid-email@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertInvalid(['email']);

            return true;
        });
    }

    public function testPasswordResetFailsWithInvalidToken(): void {
        Notification::fake();

        $user = $this->createUser();
        $otherUser = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            ResetPassword $notification
        ) use ($otherUser) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $otherUser->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $this->assertRequestHasSessionMessage(
                $response,
                SessionMessage::TYPE_ERROR,
                key: 'session-message',
                additionalChecks: function (SessionMessage $message) {
                    $this->assertEquals(
                        __(Password::INVALID_TOKEN),
                        $message->message
                    );
                }
            );

            return true;
        });
    }
}

