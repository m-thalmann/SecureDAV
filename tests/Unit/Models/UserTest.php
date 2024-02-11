<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testInitialsAttributeReturnsInitialsOfName(): void {
        $user = User::factory()->make(['name' => 'John Doe']);

        $this->assertEquals('JD', $user->initials);
    }

    public function testHasVerifiedEmailReturnsTrueWhenEmailIsVerified(): void {
        config(['app.email_verification_enabled' => true]);

        $user = User::factory()->make(['email_verified_at' => now()]);

        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function testHasVerifiedEmailReturnsFalseWhenEmailIsNotVerified(): void {
        config(['app.email_verification_enabled' => true]);

        $user = User::factory()->make(['email_verified_at' => null]);

        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function testHasVerifiedEmailReturnsTrueWhenEmailVerificationIsDisabled(): void {
        // email verification is disabled for tests per default (see phpunit.xml)

        $user = User::factory()->make(['email_verified_at' => null]);

        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function testSendEmailVerificationNotificationDoesNothingWhenEmailVerificationIsDisabled(): void {
        // email verification is disabled for tests per default (see phpunit.xml)

        Notification::fake();

        $user = User::factory()->make();

        $user->sendEmailVerificationNotification();

        Notification::assertNothingSent();
    }

    public function testSendEmailVerificationNotificationSendsNotificationWhenEmailVerificationIsEnabled(): void {
        config(['app.email_verification_enabled' => true]);

        Notification::fake();

        $user = User::factory()->make();

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
