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

        $newVersion = $file
            ->versions()
            ->latest()
            ->first();

        $this->assertNotNull($newVersion->storage_path);

        $this->storage->assertExists(
            $newVersion->storage_path,
            $fileVersionContent
        );
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
}
