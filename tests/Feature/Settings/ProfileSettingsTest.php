<?php

namespace Tests\Feature\Settings;

use App\Events\EmailUpdated;
use App\Events\PasswordUpdated;
use App\Events\UserDeleted;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
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

        $this->passwordConfirmed();

        $this->settingsViewResponse = $this->get('/settings/profile');
    }

    public function testProfileSettingsConfirmPasswordAndCanThenBeRendered(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $confirmResponse = $this->get('/settings/profile');
        $confirmResponse->assertRedirectToRoute('password.confirm');

        $this->passwordConfirmed();

        $response = $this->get('/settings/profile');
        $response->assertOk();
    }

    public function testProfileInformationCanBeUpdated(): void {
        Event::fake([EmailUpdated::class]);

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => $this->user->email,
        ]);

        $response->assertRedirect('/settings/profile#update-information');
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Test User',
            'email' => $this->user->email,
        ]);

        Event::assertNothingDispatched();
    }

    public function testProfileEmailCanBeUpdated(): void {
        $newEmail = 'new-email@test.example.com';

        Event::fake([EmailUpdated::class]);

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => $newEmail,
        ]);

        $response->assertRedirect('/settings/profile#update-information');
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Test User',
            'email' => $newEmail,
        ]);

        Event::assertDispatched(EmailUpdated::class, function (
            EmailUpdated $event
        ) {
            $this->assertEquals($this->user->id, $event->user->id);

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
        Event::fake([EmailUpdated::class]);

        config(['app.email_verification_enabled' => true]);

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect('/settings/profile#update-information');

        Notification::assertSentTo($this->user, VerifyEmail::class);

        Event::assertDispatched(EmailUpdated::class, function (
            EmailUpdated $event
        ) {
            $this->assertEquals($this->user->id, $event->user->id);

            return true;
        });
    }

    public function testPasswordCanBeUpdated(): void {
        Event::fake([PasswordUpdated::class]);

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/settings/profile#update-password');
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        Event::assertDispatched(PasswordUpdated::class, function (
            PasswordUpdated $event
        ) {
            $this->assertEquals($this->user->id, $event->user->id);

            return true;
        });
    }

    public function testPasswordUpdateFailsIfCurrentPasswordIsIncorrect(): void {
        Event::fake([PasswordUpdated::class]);

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

        Event::assertNothingDispatched();
    }

    public function testManageBrowserSessionsShowsAllSessionsForUser(): void {
        config(['session.driver' => 'database']);

        $this->passwordConfirmed();
        $this->actingAs($this->user)->get('/'); // needed to store data to database

        $response = $this->get('/settings/profile');

        $response->assertViewHas('sessions', function (array $sessions) {
            $this->assertCount(1, $sessions);
            $this->assertIsBool($sessions[0]->isCurrentDevice);

            return true;
        });
    }

    public function testUserCanBeLoggedOutFromAllSessions() {
        config(['session.driver' => 'database']);

        $this->passwordConfirmed();
        $this->actingAs($this->user)->get('/');

        $this->assertDatabaseHas(
            config('session.table', 'sessions'),
            ['user_id' => $this->user->getAuthIdentifier()],
            connection: config('session.connection')
        );

        $response = $this->delete('/settings/profile/sessions');

        $response->assertRedirectToRoute('login');
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing(
            config('session.table', 'sessions'),
            ['user_id' => $this->user->getAuthIdentifier()],
            connection: config('session.connection')
        );
        $this->assertGuest();
    }

    public function testLogoutFromAllSessionsFailsIfSessionDriverIsNotDatabase(): void {
        $response = $this->delete('/settings/profile/sessions');

        $response->assertRedirect('/settings/profile#browser-sessions');
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message[browser-sessions]'
        );
    }

    public function testAccountCanBeDeleted(): void {
        Event::fake([UserDeleted::class]);

        $response = $this->delete('/settings/profile');

        $response->assertRedirect('/login');
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
        ]);

        Event::assertDispatched(UserDeleted::class, function (
            UserDeleted $event
        ) {
            $this->assertEquals($this->user->id, $event->userData['id']);

            return true;
        });
    }

    public function testDeletingAccountDeletesAllUserFiles(): void {
        Event::fake([UserDeleted::class]);

        $files = File::factory(4)
            ->for($this->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();
        $files->load('latestVersion');

        $response = $this->delete('/settings/profile');

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
        ]);
        $this->assertDatabaseMissing('files', [
            'user_id' => $this->user->id,
        ]);

        foreach ($files as $file) {
            $this->storageFake->assertMissing(
                $file->latestVersion->storage_path
            );
        }
    }
}

