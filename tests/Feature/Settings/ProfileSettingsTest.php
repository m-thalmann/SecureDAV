<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;
    protected TestResponse $settingsViewResponse;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
        $this->actingAs($this->user);

        $this->session(['auth.password_confirmed_at' => time()]);

        $this->settingsViewResponse = $this->get('/settings/profile');
    }

    public function testProfileSettingsConfirmPasswordAndCanThenBeRendered(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $confirmResponse = $this->get('/settings/profile');
        $confirmResponse->assertRedirectToRoute('password.confirm');

        $this->session(['auth.password_confirmed_at' => time()]);

        $response = $this->get('/settings/profile');
        $response->assertOk();
    }

    public function testProfileInformationCanBeUpdated(): void {
        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'j.d@example.com',
        ]);

        $response->assertRedirect('/settings/profile#update-information');
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });
    }

    public function testProfileInformationCantBeUpdatedWithDuplicateEmail(): void {
        $otherUser = $this->createUser();

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

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect('/settings/profile#update-information');

        Notification::assertSentTo($this->user, VerifyEmail::class);
    }

    public function testPasswordCanBeUpdated(): void {
        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/settings/profile#update-password');
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });
    }

    public function testPasswordUpdateFailsIfCurrentPasswordIsIncorrect(): void {
        $response = $this->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/settings/profile#update-password');
        $response->assertSessionHasErrors(
            'current_password',
            errorBag: 'updatePassword'
        );
    }

    public function testAccountCanBeDeleted(): void {
        $response = $this->delete('/settings/profile');

        $response->assertRedirect('/login');
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
        ]);
    }

    public function testAccountCantBeDeletedIfDeleteFails(): void {
        User::deleting(fn() => false);

        $response = $this->delete('/settings/profile');

        $response->assertRedirect('/settings/profile#delete-account');
        $response->assertSessionHas(
            'session-message[delete-account]',
            function (SessionMessage $message) {
                $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

                return true;
            }
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
        ]);
    }
}
