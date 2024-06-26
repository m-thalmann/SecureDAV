<?php

namespace Tests\Feature\Auth;

use App\Notifications\VerifyEmailNotification;
use App\Providers\RouteServiceProvider;
use App\Support\SessionMessage;
use Illuminate\Auth\Events\Verified;
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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS,
            key: 'session-message'
        );

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function testEmailVerificationSucceeds(): void {
        Event::fake([Verified::class]);

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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }
}
