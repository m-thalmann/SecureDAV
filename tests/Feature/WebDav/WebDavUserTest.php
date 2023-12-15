<?php

namespace Tests\Feature\WebDav;

use App\Models\File;
use App\Models\User;
use App\Models\WebDavUser;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebDavUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexWebDavUsersViewCanBeRendered(): void {
        $fileCount = 13;

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get('/web-dav-users');

        $response->assertOk();

        $response->assertSee($webDavUser->label);
        $response->assertSee($fileCount);
    }

    public function testIndexWebDavUsersViewDoesOnlyShowItemsOfTheAuthenticatedUser(): void {
        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $otherUser = $this->createUser();

        $otherWebDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get('/web-dav-users');

        $response->assertOk();

        $response->assertSee($webDavUser->label);
        $response->assertDontSee($otherWebDavUser->label);
    }

    public function testIndexWebDavUsersViewCanSearchList(): void {
        $webDavUsers = WebDavUser::factory(20)
            ->for($this->user)
            ->create();

        $searchedUser = WebDavUser::factory()
            ->for($this->user)
            ->create([
                'label' => 'Test User',
            ]);

        $response = $this->get('/web-dav-users?q=' . $searchedUser->label);

        $response->assertOk();

        $response->assertSee($searchedUser->label);

        foreach ($webDavUsers as $webDavUser) {
            $response->assertDontSee($webDavUser->label);
        }
    }

    public function testCreateWebDavUserViewCanBeRendered(): void {
        $response = $this->get('/web-dav-users/create');

        $response->assertOk();
    }

    public function testNewWebDavUserCanBeCreated(): void {
        $label = 'Test User';
        $readonly = true;

        $response = $this->post('/web-dav-users', [
            'label' => $label,
            'readonly' => $readonly,
        ]);

        $createdWebDavUser = WebDavUser::query()
            ->where('label', $label)
            ->firstOrFail();

        $response->assertRedirect(
            "/web-dav-users/{$createdWebDavUser->username}"
        );

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $response->assertSessionHas('generated-password', function (
            string $password
        ) use ($createdWebDavUser) {
            $this->assertTrue(
                Hash::check($password, $createdWebDavUser->password)
            );

            return true;
        });

        $this->assertDatabaseHas('web_dav_users', [
            'id' => $createdWebDavUser->id,
            'label' => $label,
            'readonly' => $readonly,
            'active' => true,
            'user_id' => $this->user->id,
        ]);
    }

    public function testShowWebDavUserViewCanBeRendered(): void {
        $fileCount = 10;

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get("/web-dav-users/{$webDavUser->username}");

        $response->assertOk();

        $response->assertSee($webDavUser->label);
        $response->assertSee("({$fileCount})");
    }

    public function testShowWebDavUserViewFailsIfWebDavUserDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/web-dav-users/{$webDavUser->username}");

        $response->assertNotFound();
    }

    public function testEditWebDavUserViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get(
            "/web-dav-users/{$webDavUser->username}/edit"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testEditWebDavUserViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/web-dav-users/{$webDavUser->username}/edit");

        $response->assertOk();

        $response->assertSee($webDavUser->label);
    }

    public function testEditWebDavUserViewFailsIfWebDavUserDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/web-dav-users/{$webDavUser->username}/edit");

        $response->assertNotFound();
    }

    public function testUpdatedWebDavUserConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->put(
            "/web-dav-users/{$webDavUser->username}",
            [
                'label' => 'Label',
                'readonly' => true,
                'active' => false,
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testWebDavUserCanBeUpdated(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $label = 'Test WebDav User';
        $readonly = !$webDavUser->readonly;
        $active = !$webDavUser->active;

        $response = $this->put("/web-dav-users/{$webDavUser->username}", [
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
        ]);

        $response->assertRedirect("/web-dav-users/{$webDavUser->username}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('web_dav_users', [
            'id' => $webDavUser->id,
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
            'user_id' => $this->user->id,
        ]);
    }

    public function testWebDavUserCantBeUpdatedForOtherUser(): void {
        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $label = 'Test WebDav User';
        $readonly = !$webDavUser->readonly;
        $active = !$webDavUser->active;

        $response = $this->put("/web-dav-users/{$webDavUser->username}", [
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('web_dav_users', [
            'id' => $webDavUser->id,
            'label' => $webDavUser->label,
            'readonly' => $webDavUser->readonly,
            'active' => $webDavUser->active,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testResetWebDavUserPasswordConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->post(
            "/web-dav-users/{$webDavUser->username}/reset-password"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testWebDavUserPasswordCanBeReset(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->post(
            "/web-dav-users/{$webDavUser->username}/reset-password"
        );

        $response->assertRedirect("/web-dav-users/{$webDavUser->username}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $webDavUser->refresh();

        $response->assertSessionHas('generated-password', function (
            string $password
        ) use ($webDavUser) {
            $this->assertTrue(Hash::check($password, $webDavUser->password));

            return true;
        });
    }

    public function testWebDavUserPasswordCantBeResetForOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post(
            "/web-dav-users/{$webDavUser->username}/reset-password"
        );

        $response->assertNotFound();
    }

    public function testDeleteWebDavUserConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->delete(
            "/web-dav-users/{$webDavUser->username}"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testWebDavUserCanBeDeleted(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/web-dav-users/{$webDavUser->username}");

        $response->assertRedirect('/web-dav-users');

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('web_dav_users', [
            'id' => $webDavUser->id,
        ]);
        $this->assertDatabaseMissing('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
        ]);
    }

    public function testWebDavUserCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/web-dav-users/{$webDavUser->username}");

        $response->assertNotFound();

        $this->assertDatabaseHas('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
        ]);
    }
}

