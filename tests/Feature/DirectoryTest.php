<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testCreateDirectoryViewCanBeRendered(): void {
        $response = $this->get('/directories/create');

        $response->assertOk();
    }

    public function testCreateDirectoryViewCanBeRenderedWithParentDirectory(): void {
        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/directories/create?directory={$parentDirectory->uuid}"
        );

        $response->assertOk();

        $response->assertSee($parentDirectory->name);
    }

    public function testCreateDirectoryViewFailsIfParentDirectoryDoesntExist(): void {
        $response = $this->get('/directories/create?directory=nonexistent');

        $response->assertNotFound();
    }

    public function testCreateDirectoryViewFailsIfUserCantUpdateParentDirectory(): void {
        $otherUser = $this->createUser();

        $parentDirectory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get(
            "/directories/create?directory={$parentDirectory->uuid}"
        );

        $response->assertNotFound();
    }

    public function testDirectoryCanBeCreated(): void {
        $directoryName = 'NewDirectory';

        $response = $this->post('/directories', [
            'name' => $directoryName,
        ]);

        $this->assertDatabaseHas('directories', [
            'name' => $directoryName,
            'user_id' => $this->user->id,
        ]);

        $createdDirectory = Directory::query()
            ->where('name', $directoryName)
            ->first();

        $response->assertRedirect("/browse/{$createdDirectory->uuid}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testDirectoryCanBeCreatedWithParentDirectory(): void {
        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $directoryName = 'NewDirectory';

        $response = $this->post('/directories', [
            'name' => $directoryName,
            'parent_directory_uuid' => $parentDirectory->uuid,
        ]);

        $this->assertDatabaseHas('directories', [
            'name' => $directoryName,
            'parent_directory_id' => $parentDirectory->id,
            'user_id' => $this->user->id,
        ]);

        $createdDirectory = Directory::query()
            ->where('name', $directoryName)
            ->first();

        $response->assertRedirect("/browse/{$createdDirectory->uuid}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testDirectoryCantBeCreatedIfParentDirectoryDoesntExist(): void {
        $response = $this->post('/directories', [
            'name' => 'NewDirectory',
            'parent_directory_uuid' => 'nonexistent',
        ]);

        $response->assertNotFound();
    }

    public function testDirectoryCantBeCreatedIfUserCantUpdateParentDirectory(): void {
        $otherUser = $this->createUser();

        $parentDirectory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post('/directories', [
            'name' => 'NewDirectory',
            'parent_directory_uuid' => $parentDirectory->uuid,
        ]);

        $response->assertNotFound();
    }

    public function testDirectoryCantBeCreatedIfNameIsNotUniqueInParentDirectory(): void {
        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $directory = Directory::factory()
            ->for($this->user)
            ->for($parentDirectory, 'parentDirectory')
            ->create();

        $response = $this->from('/directories/create')->post('/directories', [
            'name' => $directory->name,
            'parent_directory_uuid' => $parentDirectory->uuid,
        ]);

        $response->assertRedirect('/directories/create');

        $response->assertSessionHasErrors('name');
    }

    public function testDirectoryCantBeCreatedIfFileWithSameNameExistsInParentDirectory(): void {
        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($parentDirectory)
            ->create();

        $response = $this->from('/directories/create')->post('/directories', [
            'name' => $file->name,
            'parent_directory_uuid' => $parentDirectory->uuid,
        ]);

        $response->assertRedirect('/directories/create');

        $response->assertSessionHasErrors('name');
    }

    public function testEditDirectoryViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get("/directories/{$directory->uuid}/edit");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testEditDirectoryViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/directories/{$directory->uuid}/edit");

        $response->assertOk();

        $response->assertSee($directory->name);
    }

    public function testEditDirectoryViewCantBeRenderedForOtherUser(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/directories/{$directory->uuid}/edit");

        $response->assertNotFound();
    }

    public function testEditDirectoryNameConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->put("/directories/{$directory->uuid}", [
            'name' => 'New name',
        ]);
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testDirectoryNameCanBeEdited(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $otherDirectory = Directory::factory()
            ->for($this->user)
            ->for($directory, 'parentDirectory')
            ->create();

        $newName = $otherDirectory->name;

        $response = $this->put("/directories/{$directory->uuid}", [
            'name' => $newName,
        ]);

        $response->assertRedirect("/browse/{$directory->uuid}");
        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('directories', [
            'id' => $directory->id,
            'name' => $newName,
        ]);
    }

    public function testDirectoryCantBeRenamedIfNameAlreadyExistsInSameDirectoryForUser(): void {
        $this->passwordConfirmed();

        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $directory = Directory::factory()
            ->for($this->user)
            ->for($parentDirectory, 'parentDirectory')
            ->create();

        $otherDirectory = Directory::factory()
            ->for($this->user)
            ->for($parentDirectory, 'parentDirectory')
            ->create();

        $response = $this->from("/directories/{$directory->uuid}/edit")->put(
            "/directories/{$directory->uuid}",
            [
                'name' => $otherDirectory->name,
            ]
        );

        $response->assertRedirect("/directories/{$directory->uuid}/edit");
        $response->assertSessionHasErrors('name');
    }

    public function testDirectoryCantBeRenamedIfFileWithSameNameAlreadyExistsInSameDirectoryForUser(): void {
        $this->passwordConfirmed();

        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $directory = Directory::factory()
            ->for($this->user)
            ->for($parentDirectory, 'parentDirectory')
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($parentDirectory)
            ->create();

        $response = $this->from("/directories/{$directory->uuid}/edit")->put(
            "/directories/{$directory->uuid}",
            [
                'name' => $file->name,
            ]
        );

        $response->assertRedirect("/directories/{$directory->uuid}/edit");
        $response->assertSessionHasErrors('name');
    }

    public function testDirectoryCantBeRenamedForOtherUser(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->put("/directories/{$directory->uuid}", [
            'name' => 'NewName',
        ]);

        $response->assertNotFound();
    }

    public function testDeleteDirectoryConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->delete("/directories/{$directory->uuid}");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testDirectoryCanBeDeleted(): void {
        $this->passwordConfirmed();

        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create();

        $directory = Directory::factory()
            ->for($this->user)
            ->for($parentDirectory, 'parentDirectory')
            ->create();

        $response = $this->delete("/directories/{$directory->uuid}");

        $response->assertRedirect("/browse/{$parentDirectory->uuid}");
        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('directories', [
            'id' => $directory->id,
        ]);
    }

    public function testDirectoryCantBeDeletedIfContainsDirectory(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        Directory::factory()
            ->for($this->user)
            ->for($directory, 'parentDirectory')
            ->create();

        $response = $this->from("/browse/{$directory->uuid}")->delete(
            "/directories/{$directory->uuid}"
        );

        $response->assertRedirect("/browse/{$directory->uuid}");
        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );

        $this->assertDatabaseHas('directories', [
            'id' => $directory->id,
        ]);
    }

    public function testDirectoryCantBeDeletedIfContainsFile(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $response = $this->from("/browse/{$directory->uuid}")->delete(
            "/directories/{$directory->uuid}"
        );

        $response->assertRedirect("/browse/{$directory->uuid}");
        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );

        $this->assertDatabaseHas('directories', [
            'id' => $directory->id,
        ]);
    }

    public function testDirectoryCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->delete("/directories/{$directory->uuid}");

        $response->assertNotFound();
    }
}

