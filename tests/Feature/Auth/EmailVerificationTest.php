<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use App\Support\SessionMessage;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase {
    use LazilyRefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        config(['app.email_verification_enabled' => true]);
    }

    public function testEmailVerificationIsRequestedWhenUserUnverified(): void {
        $user = $this->createUser(emailVerified: false);

        $this->actingAs($user);

        $response = $this->get(RouteServiceProvider::HOME);

        $response->assertRedirect('/email/verify');
    }

    public function testEmailVerificationIsNotRequestedWhenUserVerified(): void {
        $user = $this->createUser(emailVerified: true);

        $this->actingAs($user);

        $response = $this->get(RouteServiceProvider::HOME);

        $response->assertOk();
    }

    public function testEmailVerificationIsNotRequestWhenVerificationIsTurnedOff(): void {
        config(['app.email_verification_enabled' => false]);

        $user = $this->createUser(emailVerified: false);

        $this->actingAs($user);

        $response = $this->get(RouteServiceProvider::HOME);

        $response->assertOk();
    }

    public function testEmailVerificationEmailCanBeResent(): void {
        Notification::fake();

        $user = $this->createUser(emailVerified: false);

        $this->actingAs($user);

        $response = $this->from('/email/verify')->post(
            '/email/verification-notification'
        );

        $response->assertRedirect('/email/verify');
        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testEmailVerificationSucceeds(): void {
        Event::fake();

        $user = $this->createUser(emailVerified: false);

        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $response->assertRedirect(RouteServiceProvider::HOME);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });
    }
}

