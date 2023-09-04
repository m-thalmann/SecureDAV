<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
        $this->actingAs($this->user);
    }

    public function testProfileSettingsCanBeRendered(): void {
        $response = $this->get('/settings/profile');

        $response->assertStatus(200);
    }

    public function testProfileInformationCanBeUpdated(): void {
        $this->get('/settings/profile');

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'j.d@example.com',
        ]);

        $response->assertRedirect('/settings/profile#update-information');
        $response->assertSessionHas(
            'session-message[update-profile-information]',
            function (SessionMessage $message) {
                $this->assertEquals(
                    SessionMessage::TYPE_SUCCESS,
                    $message->type
                );

                return true;
            }
        );
    }

    public function testProfileInformationCantBeUpdatedWithDuplicateEmail(): void {
        $otherUser = $this->createUser();

        $this->get('/settings/profile');

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => $otherUser->email,
        ]);

        $response->assertRedirect('/settings/profile#update-information');
        $response->assertSessionHasErrors(
            'email',
            errorBag: 'updateProfileInformation'
        );
    }

    public function testUpdateProfileInformationSendsEmailVerification(): void {
        Notification::fake();

        config(['app.email_verification_enabled' => true]);

        $this->get('/settings/profile');

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect('/settings/profile#update-information');

        Notification::assertSentTo($this->user, VerifyEmail::class);
    }

    public function testPasswordCanBeUpdated(): void {
        $this->get('/settings/profile');

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/settings/profile#update-password');
        $response->assertSessionHas(
            'session-message[update-password]',
            function (SessionMessage $message) {
                $this->assertEquals(
                    SessionMessage::TYPE_SUCCESS,
                    $message->type
                );

                return true;
            }
        );
    }
}
