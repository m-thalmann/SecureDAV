<?php

namespace Tests\Feature\AccessGroups;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessGroupUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testCreateAccessGroupUserViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get(
            "/access-groups/{$accessGroup->uuid}/access-group-users/create"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testCreateAccessGroupUserViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/access-group-users/create"
        );

        $response->assertOk();

        $response->assertSee($accessGroup->label);
    }

    public function testCreateAccessGroupUserViewFailsIfAccessGroupDoesntExist(): void {
        $response = $this->get(
            '/access-groups/nonexistent/access-group-users/create'
        );

        $response->assertNotFound();
    }

    public function testCreateAccessGroupUserViewFailsIfUserCantUpdateAccessGroup(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/access-group-users/create"
        );

        $response->assertForbidden();
    }

    public function testCreateAccessGroupUserConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->post(
            "/access-groups/{$accessGroup->uuid}/access-group-users",
            [
                'label' => 'Label',
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testNewAccessGroupUserCanBeCreated(): void {
        $this->passwordConfirmed();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $label = 'Test Access Group User';

        $response = $this->post(
            "/access-groups/{$accessGroup->uuid}/access-group-users",
            [
                'label' => $label,
            ]
        );

        $createdAccessGroupUser = AccessGroupUser::query()
            ->where('label', $label)
            ->first();

        $response->assertRedirect("/access-groups/{$accessGroup->uuid}#users");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $response->assertSessionHas('generated-password', function (
            string $password
        ) use ($createdAccessGroupUser) {
            $this->assertTrue(
                Hash::check($password, $createdAccessGroupUser->password)
            );

            return true;
        });

        $this->assertDatabaseHas('access_group_users', [
            'id' => $createdAccessGroupUser->id,
            'label' => $label,
            'access_group_id' => $accessGroup->id,
        ]);
    }

    public function testNewAccessGroupUserCantBeCreatedIfUserCantUpdateAccessGroup(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post(
            "/access-groups/{$accessGroup->uuid}/access-group-users",
            [
                'label' => 'Test Access Group User',
            ]
        );

        $response->assertForbidden();
    }

    public function testEditAccessGroupUserViewCanBeRendered(): void {
        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($this->user))
            ->create();

        $response = $this->get(
            "/access-group-users/{$accessGroupUser->username}/edit"
        );

        $response->assertOk();

        $response->assertSee($accessGroupUser->label);
        $response->assertSee($accessGroupUser->accessGroup->label);
    }

    public function testEditAccessGroupUserViewFailsIfAccessGroupUserDoesntExist(): void {
        $response = $this->get('/access-group-users/nonexistent/edit');

        $response->assertNotFound();
    }

    public function testEditAccessGroupUserViewFailsIfUserCantUpdateAccessGroupUser(): void {
        $otherUser = $this->createUser();

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($otherUser))
            ->create();

        $response = $this->get(
            "/access-group-users/{$accessGroupUser->username}/edit"
        );

        $response->assertForbidden();
    }

    public function testAccessGroupUserCanBeUpdated(): void {
        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($this->user))
            ->create();

        $label = 'Test Access Group User';

        $response = $this->put(
            "/access-group-users/{$accessGroupUser->username}",
            [
                'label' => $label,
            ]
        );

        $response->assertRedirect(
            "/access-groups/{$accessGroupUser->accessGroup->uuid}#users"
        );

        $this->assertDatabaseHas('access_group_users', [
            'id' => $accessGroupUser->id,
            'label' => $label,
        ]);
    }

    public function testAccessGroupUserCantBeUpdatedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($otherUser))
            ->create();

        $response = $this->put(
            "/access-group-users/{$accessGroupUser->username}",
            [
                'label' => 'Test Access Group User',
            ]
        );

        $response->assertForbidden();
    }

    public function testDeleteAccessGroupUserConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($this->user))
            ->create();

        $confirmResponse = $this->delete(
            "/access-group-users/{$accessGroupUser->username}"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testAccessGroupUserCanBeDeleted(): void {
        $this->passwordConfirmed();

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($this->user))
            ->create();

        $response = $this->delete(
            "/access-group-users/{$accessGroupUser->username}"
        );

        $response->assertRedirect(
            "/access-groups/{$accessGroupUser->accessGroup->uuid}#users"
        );

        $this->assertDatabaseMissing('access_group_users', [
            'id' => $accessGroupUser->id,
        ]);
    }

    public function testAccessGroupUserCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($otherUser))
            ->create();

        $response = $this->delete(
            "/access-group-users/{$accessGroupUser->username}"
        );

        $response->assertForbidden();
    }

    public function testResetAccessGroupUserPasswordConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($this->user))
            ->create();

        $confirmResponse = $this->post(
            "/access-group-users/{$accessGroupUser->username}/reset-password"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testAccessGroupUserPasswordCanBeReset(): void {
        $this->passwordConfirmed();

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($this->user))
            ->create();

        $response = $this->post(
            "/access-group-users/{$accessGroupUser->username}/reset-password"
        );

        $response->assertRedirect(
            "/access-groups/{$accessGroupUser->accessGroup->uuid}#users"
        );

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $accessGroupUser->refresh();

        $response->assertSessionHas('generated-password', function (
            string $password
        ) use ($accessGroupUser) {
            $this->assertTrue(
                Hash::check($password, $accessGroupUser->password)
            );

            return true;
        });
    }

    public function testAccessGroupUserPasswordCantBeResetForOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $accessGroupUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($otherUser))
            ->create();

        $response = $this->post(
            "/access-group-users/{$accessGroupUser->username}/reset-password"
        );

        $response->assertForbidden();
    }
}

