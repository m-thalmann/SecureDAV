<?php

namespace Tests\Feature;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Models\User;
use App\View\Helpers\SessionMessage;
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

    public function testCreateAccessGroupUserViewCanBeRendered(): void {
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
        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/access-group-users/create"
        );

        $response->assertForbidden();
    }

    public function testNewAccessGroupUserCanBeCreated(): void {
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
}
