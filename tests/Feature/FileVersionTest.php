<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FileVersionTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;
    protected FilesystemAdapter $storage;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);

        $this->storage = Storage::fake('files');
    }

    public function testNewFileVersionCanBeCreated(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();
        $fileVersionContent = $this->storage->get($fileVersion->storage_path);

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'label' => 'New version',
        ]);

        $response->assertRedirect("/files/{$file->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'label' => 'New version',
            'version' => $fileVersion->version + 1,
        ]);

        $newVersion = $file->latestVersion;

        $this->assertNotNull($newVersion->storage_path);

        $this->storage->assertExists(
            $newVersion->storage_path,
            $fileVersionContent
        );
    }

    public function testNewFileVersionReceivesNextHighestVersionNumberWhenCreated(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $trashedFileVersion = FileVersion::factory()
            ->for($file)
            ->trashed()
            ->create(['version' => $fileVersion->version + 1]);

        $response = $this->post("/files/{$file->uuid}/file-versions");

        $response->assertRedirect("/files/{$file->uuid}");

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'version' => $trashedFileVersion->version + 1,
        ]);
    }

    public function testNewFileVersionCantBeCreatedIfFileDoesntExist(): void {
        $response = $this->post('/files/doesnt-exist/file-versions', [
            'label' => 'New version',
        ]);

        $response->assertNotFound();
    }

    public function testNewFileVersionCantBeCreatedIfUserCantUpdateFile(): void {
        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'label' => 'New version',
        ]);

        $response->assertForbidden();
    }

    public function testNewFileVersionCantBeCreatedIfFileDoesntHaveAnyVersions(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'label' => 'New version',
        ]);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_WARNING, $message->type);

            return true;
        });
    }

    public function testCreateFileVersionFailsIfTargetFileAlreadyExists(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $fileUuid = Str::freezeUuids();

        $this->storage->put($fileUuid, '');

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'label' => 'New version',
        ]);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });

        // cleanup
        Str::createUuidsNormally();
    }

    public function testCreateFileVersionFailsIfFileCantBeCopied(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $this->storage->delete($fileVersion->storage_path);

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'label' => 'New version',
        ]);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testEditFileVersionViewCanBeRendered(): void {
        $version = 632;

        $file = File::factory()
            ->for($this->user)
            ->create(['next_version' => $version]);

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/file-versions/{$fileVersion->id}/edit");

        $response->assertOk();

        $response->assertSee($file->fileName);
        $response->assertSee($version);
    }

    public function testEditFileVersionViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/file-versions/{$fileVersion->id}/edit");

        $response->assertForbidden();
    }

    public function testEditFileVersionViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/file-versions/does-not-exist/edit');

        $response->assertNotFound();
    }

    public function testFileVersionCanBeEdited(): void {
        $newLabel = 'New Label';

        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->put("/file-versions/{$fileVersion->id}", [
            'label' => $newLabel,
        ]);

        $response->assertRedirect("/files/{$file->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $fileVersion->refresh();

        $this->assertEquals($newLabel, $fileVersion->label);
    }

    public function testFileVersionCannotBeEditedIfItDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->put("/file-versions/{$fileVersion->id}", [
            'label' => 'New Label',
        ]);

        $response->assertForbidden();
    }

    public function testFileVersionCanBeMovedToTrash(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->delete("/file-versions/{$fileVersion->id}");

        $response->assertRedirect("/files/{$file->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertSoftDeleted('file_versions', [
            'id' => $fileVersion->id,
        ]);
    }

    public function testFileVersionCannotBeMovedToTrashIfItDoesNotBelongToUser(): void {
        $otherUser = User::factory()->create();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->delete("/file-versions/{$fileVersion->id}");

        $response->assertForbidden();
    }

    public function testFileVersionCannotBeMovedToTrashIfItDoesNotExist(): void {
        $response = $this->delete('/file-versions/doesnt-exist');

        $response->assertNotFound();
    }
}
