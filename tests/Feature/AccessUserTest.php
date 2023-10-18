<?php

namespace Tests\Feature;

use App\Models\AccessUser;
use App\Models\File;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexAccessUsersViewCanBeRendered(): void {
        $fileCount = 13;

        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get('/access-users');

        $response->assertOk();

        $response->assertSee($accessUser->username);
        $response->assertSee($accessUser->label);
        $response->assertSee($fileCount);
    }

    public function testIndexAccessUsersViewDoesOnlyShowItemsOfTheAuthenticatedUser(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->create();

        $otherUser = $this->createUser();

        $otherAccessUser = AccessUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get('/access-users');

        $response->assertOk();

        $response->assertSee($accessUser->username);
        $response->assertDontSee($otherAccessUser->username);
    }

    public function testCreateAccessUserViewCanBeRendered(): void {
        $response = $this->get('/access-users/create');

        $response->assertOk();
    }

    public function testNewAccessUserCanBeCreated(): void {
        $label = 'Test Access User';
        $readonly = true;

        $response = $this->post('/access-users', [
            'label' => $label,
            'readonly' => $readonly,
        ]);

        $createdAccessUser = AccessUser::query()
            ->where('label', $label)
            ->first();

        $response->assertRedirect(
            "/access-users/{$createdAccessUser->username}"
        );

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $response->assertSessionHas('generated-password', function (
            string $password
        ) use ($createdAccessUser) {
            $this->assertTrue(
                Hash::check($password, $createdAccessUser->password)
            );

            return true;
        });

        $this->assertDatabaseHas('access_users', [
            'id' => $createdAccessUser->id,
            'label' => $label,
            'readonly' => $readonly,
            'active' => true,
            'user_id' => $this->user->id,
        ]);
    }

    public function testShowAccessUserViewCanBeRendered(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->get("/access-users/{$accessUser->username}");

        $response->assertOk();

        $response->assertSee($accessUser->username);
        $response->assertSee($accessUser->label);
        $response->assertSee("({$accessUser->files->count()})");
    }

    public function testShowAccessUserViewFailsIfAccessUserDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $accessUser = AccessUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/access-users/{$accessUser->username}");

        $response->assertForbidden();
    }

    public function testShowAccessUserViewFailsIfAccessUserDoesNotExist(): void {
        $response = $this->get('/access-users/does-not-exist');

        $response->assertNotFound();
    }

    public function testEditAccessUserViewCanBeRendered(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/access-users/{$accessUser->username}/edit");

        $response->assertOk();

        $response->assertSee($accessUser->username);
        $response->assertSee($accessUser->label);
    }

    public function testEditAccessUserViewFailsIfAccessUserDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $accessUser = AccessUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/access-users/{$accessUser->username}/edit");

        $response->assertForbidden();
    }

    public function testEditAccessUserViewFailsIfAccessUserDoesNotExist(): void {
        $response = $this->get('/access-users/does-not-exist/edit');

        $response->assertNotFound();
    }

    public function testAccessUserCanBeUpdated(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->create();

        $label = 'Test Access User';
        $readonly = !$accessUser->readonly;
        $active = !$accessUser->active;

        $response = $this->put("/access-users/{$accessUser->username}", [
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
        ]);

        $response->assertRedirect("/access-users/{$accessUser->username}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('access_users', [
            'id' => $accessUser->id,
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
            'user_id' => $this->user->id,
        ]);
    }

    public function testAccessUserCantBeUpdatedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessUser = AccessUser::factory()
            ->for($otherUser)
            ->create();

        $label = 'Test Access User';
        $readonly = !$accessUser->readonly;
        $active = !$accessUser->active;

        $response = $this->put("/access-users/{$accessUser->username}", [
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('access_users', [
            'id' => $accessUser->id,
            'label' => $accessUser->label,
            'readonly' => $accessUser->readonly,
            'active' => $accessUser->active,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testAccessUserCanBeDeleted(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/access-users/{$accessUser->username}");

        $response->assertRedirect('/access-users');
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseMissing('access_users', [
            'id' => $accessUser->id,
        ]);
        $this->assertDatabaseMissing('access_user_files', [
            'access_user_id' => $accessUser->id,
        ]);
    }

    public function testAccessUserCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessUser = AccessUser::factory()
            ->for($otherUser)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/access-users/{$accessUser->username}");

        $response->assertForbidden();

        $this->assertDatabaseHas('access_user_files', [
            'access_user_id' => $accessUser->id,
        ]);
    }
}