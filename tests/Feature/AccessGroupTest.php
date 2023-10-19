<?php

namespace Tests\Feature;

use App\Models\AccessGroup;
use App\Models\File;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccessGroupTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexAccessGroupsViewCanBeRendered(): void {
        $fileCount = 13;

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get('/access-groups');

        $response->assertOk();

        $response->assertSee($accessGroup->label);
        $response->assertSee($fileCount);
    }

    public function testIndexAccessGroupsViewDoesOnlyShowItemsOfTheAuthenticatedUser(): void {
        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $otherUser = $this->createUser();

        $otherAccessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get('/access-groups');

        $response->assertOk();

        $response->assertSee($accessGroup->label);
        $response->assertDontSee($otherAccessGroup->label);
    }

    public function testCreateAccessGroupViewCanBeRendered(): void {
        $response = $this->get('/access-groups/create');

        $response->assertOk();
    }

    public function testNewAccessGroupCanBeCreated(): void {
        $label = 'Test Access Group';
        $readonly = true;

        $response = $this->post('/access-groups', [
            'label' => $label,
            'readonly' => $readonly,
        ]);

        $createdAccessGroup = AccessGroup::query()
            ->where('label', $label)
            ->first();

        $response->assertRedirect("/access-groups/{$createdAccessGroup->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('access_groups', [
            'id' => $createdAccessGroup->id,
            'label' => $label,
            'readonly' => $readonly,
            'active' => true,
            'user_id' => $this->user->id,
        ]);
    }

    public function testShowAccessGroupViewCanBeRendered(): void {
        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->get("/access-groups/{$accessGroup->uuid}");

        $response->assertOk();

        $response->assertSee($accessGroup->label);
        $response->assertSee("({$accessGroup->files->count()})");
    }

    public function testShowAccessGroupViewFailsIfAccessGroupDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/access-groups/{$accessGroup->uuid}");

        $response->assertForbidden();
    }

    public function testShowAccessGroupViewFailsIfAccessGroupDoesNotExist(): void {
        $response = $this->get('/access-groups/does-not-exist');

        $response->assertNotFound();
    }

    public function testEditAccessGroupViewCanBeRendered(): void {
        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/access-groups/{$accessGroup->uuid}/edit");

        $response->assertOk();

        $response->assertSee($accessGroup->label);
    }

    public function testEditAccessGroupViewFailsIfAccessGroupDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/access-groups/{$accessGroup->uuid}/edit");

        $response->assertForbidden();
    }

    public function testEditAccessGroupViewFailsIfAccessGroupDoesNotExist(): void {
        $response = $this->get('/access-groups/does-not-exist/edit');

        $response->assertNotFound();
    }

    public function testAccessGroupCanBeUpdated(): void {
        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $label = 'Test Access Group';
        $readonly = !$accessGroup->readonly;
        $active = !$accessGroup->active;

        $response = $this->put("/access-groups/{$accessGroup->uuid}", [
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
        ]);

        $response->assertRedirect("/access-groups/{$accessGroup->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('access_groups', [
            'id' => $accessGroup->id,
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
            'user_id' => $this->user->id,
        ]);
    }

    public function testAccessGroupCantBeUpdatedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $label = 'Test Access Group';
        $readonly = !$accessGroup->readonly;
        $active = !$accessGroup->active;

        $response = $this->put("/access-groups/{$accessGroup->uuid}", [
            'label' => $label,
            'readonly' => $readonly,
            'active' => $active,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('access_groups', [
            'id' => $accessGroup->id,
            'label' => $accessGroup->label,
            'readonly' => $accessGroup->readonly,
            'active' => $accessGroup->active,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testAccessGroupCanBeDeleted(): void {
        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/access-groups/{$accessGroup->uuid}");

        $response->assertRedirect('/access-groups');
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseMissing('access_groups', [
            'id' => $accessGroup->id,
        ]);
        $this->assertDatabaseMissing('access_group_files', [
            'access_group_id' => $accessGroup->id,
        ]);
    }

    public function testAccessGroupCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/access-groups/{$accessGroup->uuid}");

        $response->assertForbidden();

        $this->assertDatabaseHas('access_group_files', [
            'access_group_id' => $accessGroup->id,
        ]);
    }
}