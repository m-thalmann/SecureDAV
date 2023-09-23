<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testShowFileViewCanBeRendered(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}");

        $response->assertOk();

        $response->assertSee($file->fileName);
    }

    public function testShowFileViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->get("/files/{$file->uuid}");

        $response->assertForbidden();
    }

    public function testShowFileViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist');

        $response->assertNotFound();
    }

    public function testEditFileViewCanBeRendered(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/edit");

        $response->assertOk();

        $response->assertSee($file->fileName);
    }

    public function testEditFileViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->get("/files/{$file->uuid}/edit");

        $response->assertForbidden();
    }

    public function testEditFileViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist/edit');

        $response->assertNotFound();
    }

    public function testFileCanBeEdited(): void {
        $newName = 'New Name';
        $newDescription = 'New Description';

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}", [
            'name' => $newName,
            'description' => $newDescription,
        ]);

        $response->assertRedirect("/files/{$file->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $file->refresh();

        $this->assertEquals($newName, $file->name);
        $this->assertEquals($newDescription, $file->description);
    }

    public function testFileCannotBeEditedIfItDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->put("/files/{$file->uuid}", [
            'name' => 'New Name',
            'description' => 'New Description',
        ]);

        $response->assertForbidden();
    }

    public function testFileCantBeRenamedIfNameAlreadyExistsInSameDirectoryForUser(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $otherFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => $file->directory_id]);

        $response = $this->put("/files/{$file->uuid}", [
            'name' => $otherFile->name,
            'description' => 'New Description',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function testFileCanBeMovedToTrash(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->create(['directory_id' => $directory->id]);

        $response = $this->delete("/files/{$file->uuid}");

        $response->assertRedirect("/browse/{$directory->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertSoftDeleted($file);

        $trashedFile = File::withTrashed()
            ->where('id', $file->id)
            ->first();

        $this->assertNull($trashedFile->directory_id);
    }

    public function testFileCannotBeMovedToTrashIfItDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->delete("/files/{$file->uuid}");

        $response->assertForbidden();
    }

    public function testFileCannotBeMovedToTrashIfItDoesNotExist(): void {
        $response = $this->delete('/files/does-not-exist');

        $response->assertNotFound();
    }
}
