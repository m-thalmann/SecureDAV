<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Services\FileVersionService;
use App\View\Helpers\SessionMessage;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function testCreateFileVersionViewCanBeRendered(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/file-versions/create");

        $response->assertOk();

        $response->assertSee($file->fileName);
    }

    public function testCreateFileVersionViewShowsFileInputIfFileHasNoVersion(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/file-versions/create");

        $response->assertOk();

        $response->assertSee('data-file-input-is-shown="true"', escape: false);
        $response->assertDontSee('input type="checkbox"', escape: false);
    }

    public function testCreateFileVersionViewDoesNotShowFileInputIfFileHasVersion(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/files/{$file->uuid}/file-versions/create");

        $response->assertOk();

        $response->assertSee('data-file-input-is-shown="false"', escape: false);
        $response->assertSee('input type="checkbox"', escape: false);
    }

    public function testCreateFileVersionViewFailsIfFileDoesntExist(): void {
        $response = $this->get('/files/nonexistent/file-versions/create');

        $response->assertNotFound();
    }

    public function testCreateFileVersionViewFailsIfUserCantUpdateFile(): void {
        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/files/{$file->uuid}/file-versions/create");

        $response->assertForbidden();
    }

    public function testNewFileVersionCanBeCreatedWithUploadFile(): void {
        $uploadedFile = UploadedFile::fake()->create('new-version.txt');

        $file = File::factory()
            ->for($this->user)
            ->create(['mime_type' => $uploadedFile->getClientMimeType()]);

        $label = 'New version';

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'label' => $label,
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'label' => $label,
            'version' => 1,
        ]);

        $newVersion = $file->latestVersion;

        $this->assertNotNull($newVersion->storage_path);

        $this->storage->assertExists(
            $newVersion->storage_path,
            $uploadedFile->getContent()
        );
    }

    public function testNewFileVersionCanBeCreatedFromLatestVersion(): void {
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

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

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

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'version' => $trashedFileVersion->version + 1,
        ]);
    }

    public function testNewFileVersionCantBeCreatedIfFileDoesntExist(): void {
        $response = $this->post('/files/doesnt-exist/file-versions');

        $response->assertNotFound();
    }

    public function testNewFileVersionCantBeCreatedIfUserCantUpdateFile(): void {
        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post("/files/{$file->uuid}/file-versions");

        $response->assertForbidden();
    }

    public function testNewFileVersionCantBeCreatedIfMimeTypesMismatch(): void {
        $file = File::factory()
            ->for($this->user)
            ->create(['mime_type' => 'text/plain']);

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'file' => UploadedFile::fake()->create(
                'new-version.jpg',
                'image/jpeg'
            ),
        ]);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testNewFileVersionCantBeCreatedWithoutAnUploadFileIfFileDoesntHaveAnyVersions(): void {
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

    public function testCreateFileVersionFailsIfCreateCallFails(): void {
        $this->mock(FileVersionService::class, function ($mock) {
            $mock
                ->shouldReceive('createNewVersion')
                ->andThrow(new Exception('Test exception'));
        });

        $file = File::factory()
            ->for($this->user)
            ->create();

        $uploadedFile = UploadedFile::fake()->create('new-version.txt');

        $response = $this->post("/files/{$file->uuid}/file-versions", [
            'file' => $uploadedFile,
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

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

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

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

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
