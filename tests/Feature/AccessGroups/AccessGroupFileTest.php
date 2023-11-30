<?php

namespace Tests\Feature\AccessGroups;

use App\Models\AccessGroup;
use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccessGroupFileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testCreateAccessGroupFileViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testCreateAccessGroupFileViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $files = File::factory(3)
            ->for($this->user)
            ->create(['directory_id' => null]);

        $directories = Directory::factory(2)
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create"
        );

        $response->assertOk();

        $response->assertSee($accessGroup->label);

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }
    }

    public function testCreateAccessGroupFileViewCanBeRenderedWithDirectory(): void {
        $this->passwordConfirmed();

        $otherFiles = File::factory(3)
            ->for($this->user)
            ->create(['directory_id' => null]);

        $otherDirectories = Directory::factory(2)
            ->for($this->user)
            ->create();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $files = File::factory(3)
            ->for($this->user)
            ->for($directory)
            ->create();

        $directories = Directory::factory(2)
            ->for($this->user)
            ->for($directory);

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create?directory={$directory->uuid}"
        );

        $response->assertOk();

        $response->assertSee($accessGroup->label);

        $response->assertSee($directory->name);

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }

        foreach ($otherDirectories as $directory) {
            $response->assertDontSee($directory->name);
        }
    }

    public function testCreateAccessGroupFileViewOnlyShowsFilesNotYetInTheAccessGroup(): void {
        $this->passwordConfirmed();

        $nonGroupFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $groupFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->hasAttached($groupFile)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create"
        );

        $response->assertOk();

        $response->assertSee($nonGroupFile->name);
        $response->assertDontSee($groupFile->name);
    }

    public function testCreateAccessGroupFileViewDoesNotShowDirectoriesAndFilesOfOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $otherFiles = File::factory(3)
            ->for($otherUser)
            ->create(['directory_id' => null]);

        $otherDirectories = Directory::factory(2)
            ->for($otherUser)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create"
        );

        $response->assertOk();

        $response->assertSee($accessGroup->label);

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }

        foreach ($otherDirectories as $directory) {
            $response->assertDontSee($directory->name);
        }
    }

    public function testCreateAccessGroupFileViewCantBeRenderedWithDirectoryOfOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create?directory={$directory->uuid}"
        );

        $response->assertNotFound();
    }

    public function testCreateAccessGroupFileViewCantBeRenderedWithDirectoryIfDirectoryDoesntExist(): void {
        $this->passwordConfirmed();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/access-groups/{$accessGroup->uuid}/files/create?directory=non-existent"
        );

        $response->assertNotFound();
    }

    public function testCreateAccessGroupFileViewCantBeRenderedIfAccessGroupDoesntExist(): void {
        $response = $this->get('/access-groups/non-existent/files/create');

        $response->assertNotFound();
    }

    public function testCreateAccessGroupFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->post(
            "/access-groups/{$accessGroup->uuid}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testAccessGroupFileCanBeCreated(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->post("/access-groups/{$accessGroup->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertRedirect("/access-groups/{$accessGroup->uuid}#files");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('access_group_files', [
            'access_group_id' => $accessGroup->id,
            'file_id' => $file->id,
        ]);
    }

    public function testAccessGroupFileCantBeCreatedIfUserCantUpdateAccessGroup(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post("/access-groups/{$accessGroup->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('access_group_files', [
            'access_group_id' => $accessGroup->id,
            'file_id' => $file->id,
        ]);
    }

    public function testAccessGroupFileCantBeCreatedIfUserCantUpdateFile(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->post("/access-groups/{$accessGroup->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('access_group_files', [
            'access_group_id' => $accessGroup->id,
            'file_id' => $file->id,
        ]);
    }

    public function testAccessGroupFileCantBeCreatedIfFileDoesNotExist(): void {
        $this->passwordConfirmed();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->from(
            "/access-groups/{$accessGroup->uuid}/files/create"
        )->post("/access-groups/{$accessGroup->uuid}/files", [
            'file_uuid' => 'non-existent',
        ]);

        $response->assertRedirect(
            "/access-groups/{$accessGroup->uuid}/files/create"
        );

        $response->assertSessionHasErrors('file_uuid');

        $this->assertDatabaseMissing('access_group_files', [
            'access_group_id' => $accessGroup->id,
        ]);
    }

    public function testAccessGroupFileWillNotBeCreatedIfAlreadyPresent(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create();

        $response = $this->post("/access-groups/{$accessGroup->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertRedirect("/access-groups/{$accessGroup->uuid}#files");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_WARNING
        );

        $this->assertDatabaseHas('access_group_files', [
            'access_group_id' => $accessGroup->id,
            'file_id' => $file->id,
        ]);
    }

    public function testDeleteAccessGroupFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create();

        $confirmResponse = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/access-groups/{$accessGroup->uuid}/files/{$file->uuid}"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testAccessGroupFileCanBeDeleted(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create();

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/access-groups/{$accessGroup->uuid}/files/{$file->uuid}"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('access_group_files', [
            'access_group_id' => $accessGroup->id,
            'file_id' => $file->id,
        ]);
    }

    public function testAccessGroupFileCantBeDeletedIfUserCantUpdateAccessGroup(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $accessGroup = AccessGroup::factory()
            ->for($otherUser)
            ->hasAttached($file)
            ->create();

        $response = $this->delete(
            "/access-groups/{$accessGroup->uuid}/files/{$file->uuid}"
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('access_group_files', [
            'access_group_id' => $accessGroup->id,
            'file_id' => $file->id,
        ]);
    }

    public function testAccessGroupFileCantBeDeletedIfItDoesNotExist(): void {
        $accessGroup = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $response = $this->delete(
            "/access-groups/{$accessGroup->uuid}/files/non-existent"
        );

        $response->assertNotFound();
    }
}

