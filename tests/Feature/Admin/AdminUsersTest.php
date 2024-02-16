<?php

namespace Tests\Feature\Admin;

use App\Events\EmailUpdated;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUsersTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->forceFill(['is_admin' => true])->save();

        $this->actingAs($this->user);
    }

    public function testIndexAdminUsersViewConfirmsPassword(): void {
        $response = $this->get('/admin/users');

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testIndexAdminUsersViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $users = User::factory()
            ->count(5)
            ->create();

        $response = $this->get('/admin/users');

        $response->assertOk();

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }

        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    public function testIndexAdminUsersViewCanBeSearched(): void {
        $this->passwordConfirmed();

        $users = User::factory()
            ->count(2)
            ->create();

        $response = $this->get('/admin/users?q=' . $users[0]->name);

        $response->assertOk();
        $response->assertSee($users[0]->name);
        $response->assertDontSee($users[1]->name);
    }

    public function testIndexAdminUsersViewCantBeRenderedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $response = $this->get('/admin/users');

        $response->assertForbidden();
    }

    public function testCreateUserViewConfirmsPassword(): void {
        $response = $this->get('/admin/users/create');

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testCreateUserViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $response = $this->get('/admin/users/create');

        $response->assertOk();
    }

    public function testCreateUserViewCantBeRenderedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $response = $this->get('/admin/users/create');

        $response->assertForbidden();
    }

    public function testStoreUserConfirmsPassword(): void {
        $response = $this->post('/admin/users');

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testStoreUserCanBeCreated(): void {
        $this->passwordConfirmed();

        $response = $this->post('/admin/users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirectToRoute('admin.users.index');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testStoreUserCantBeCreatedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $response = $this->post('/admin/users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'name' => 'John Doe',
        ]);
    }

    public function testEditUserViewConfirmsPassword(): void {
        $user = User::factory()->create();

        $response = $this->get("/admin/users/{$user->id}/edit");

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testEditUserViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $user = User::factory()->create();

        $response = $this->get("/admin/users/{$user->id}/edit");

        $response->assertOk();
    }

    public function testEditUserViewCantBeRenderedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $user = User::factory()->create();

        $response = $this->get("/admin/users/{$user->id}/edit");

        $response->assertForbidden();
    }

    public function testEditUserViewCantBeRenderedForOwnUser(): void {
        $this->passwordConfirmed();

        $response = $this->get("/admin/users/{$this->user->id}/edit");

        $response->assertForbidden();
    }

    public function testUpdateUserConfirmsPassword(): void {
        $user = User::factory()->create();

        $response = $this->put("/admin/users/{$user->id}");

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testUpdateUserCanBeUpdated(): void {
        Event::fake([EmailUpdated::class, PasswordReset::class]);

        $this->passwordConfirmed();

        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->put("/admin/users/{$user->id}", [
            'name' => 'John Doe',
            'email' => $user->email,
            'is_admin' => false,
        ]);

        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => $user->email,
            'is_admin' => false,
        ]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $response->assertSessionMissing('generated-password');

        Event::assertNothingDispatched();
    }

    public function testUpdateUserCanBeUpdatedWithPasswordReset(): void {
        Event::fake();

        $this->passwordConfirmed();

        $user = User::factory()->create();

        $response = $this->put("/admin/users/{$user->id}", [
            'name' => 'John Doe',
            'email' => $user->email,
            'is_admin' => true,
            'reset_password' => true,
        ]);

        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => $user->email,
            'is_admin' => true,
        ]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $user->refresh();

        $response->assertSessionHas('generated-password', function (
            string $password
        ) use ($user) {
            $this->assertTrue(Hash::check($password, $user->password));

            return true;
        });

        Event::assertDispatched(PasswordReset::class);
    }

    public function testUpdateUserCanBeUpdatedWithNewEmail(): void {
        Event::fake();
        Notification::fake();

        config(['app.email_verification_enabled' => true]);

        $this->passwordConfirmed();

        $user = User::factory()->create();

        $response = $this->put("/admin/users/{$user->id}", [
            'name' => 'John Doe',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'new-email@example.com',
        ]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        Event::assertDispatched(EmailUpdated::class);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testUpdateUserCantBeUpdatedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $user = User::factory()->create();

        $response = $this->put("/admin/users/{$user->id}", [
            'name' => 'John Doe',
            'email' => $user->email,
            'is_admin' => false,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'name' => 'John Doe',
        ]);
    }

    public function testUpdateUserCantBeUpdatedForOwnUser(): void {
        $this->passwordConfirmed();

        $response = $this->put("/admin/users/{$this->user->id}", [
            'name' => 'John Doe',
            'email' => $this->user->email,
            'is_admin' => false,
        ]);

        $response->assertForbidden();
    }
}
