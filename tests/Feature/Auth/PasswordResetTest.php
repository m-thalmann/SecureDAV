<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testRequestPasswordResetScreenCanBeRendered(): void {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function testPasswordResetEmailCanBeRequested(): void {
        Notification::fake();

        $user = User::factory()->create();

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

        $user = User::factory()->create();

        $request1 = $this->post('/forgot-password', ['email' => $user->email]);

        $request1->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);
            $this->assertEquals(
                __(Password::RESET_LINK_SENT),
                $message->message
            );

            return true;
        });

        $request2 = $this->post('/forgot-password', ['email' => $user->email]);

        $request2->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);
            $this->assertEquals(
                __(Password::RESET_THROTTLED),
                $message->message
            );

            return true;
        });

        Notification::assertSentTo($user, ResetPassword::class, 1);
    }

    public function testResetPasswordScreenCanBeRendered(): void {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
        ) {
            $response = $this->get('/reset-password/' . $notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function testPasswordCanBeResetWithValidToken(): void {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
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

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
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

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
        ) use ($otherUser) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $otherUser->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHas('session-message', function (
                SessionMessage $message
            ) {
                $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);
                $this->assertEquals(
                    __(Password::INVALID_TOKEN),
                    $message->message
                );

                return true;
            });

            return true;
        });
    }
}
