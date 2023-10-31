<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Services\FileEncryptionService;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
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

        $response = $this->get("/files/{$file->uuid}/versions/create");

        $response->assertOk();

        $response->assertSee($file->name);
    }

    public function testCreateFileVersionViewShowsFileInputIfFileHasNoVersion(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/create");

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

        $response = $this->get("/files/{$file->uuid}/versions/create");

        $response->assertOk();

        $response->assertSee('data-file-input-is-shown="false"', escape: false);
        $response->assertSee('input type="checkbox"', escape: false);
    }

    public function testCreateFileVersionViewFailsIfFileDoesntExist(): void {
        $response = $this->get('/files/nonexistent/versions/create');

        $response->assertNotFound();
    }

    public function testCreateFileVersionViewFailsIfUserCantUpdateFile(): void {
        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/create");

        $response->assertForbidden();
    }

    public function testNewFileVersionCanBeCreatedWithUploadFile(): void {
        $uploadedFile = UploadedFile::fake()->create('new-version.txt');

        $file = File::factory()
            ->for($this->user)
            ->create();

        $label = 'New version';

        $response = $this->post("/files/{$file->uuid}/versions", [
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
            'mime_type' => $uploadedFile->getMimeType(),
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

        $response = $this->post("/files/{$file->uuid}/versions", [
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
            'mime_type' => $fileVersion->mime_type,
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

        $response = $this->post("/files/{$file->uuid}/versions");

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'version' => $trashedFileVersion->version + 1,
        ]);
    }

    public function testNewFileVersionCantBeCreatedIfFileDoesntExist(): void {
        $response = $this->post('/files/doesnt-exist/versions');

        $response->assertNotFound();
    }

    public function testNewFileVersionCantBeCreatedIfUserCantUpdateFile(): void {
        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post("/files/{$file->uuid}/versions");

        $response->assertForbidden();
    }

    public function testNewFileVersionCantBeCreatedWithoutAnUploadFileIfFileDoesntHaveAnyVersions(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->post("/files/{$file->uuid}/versions", [
            'label' => 'New version',
        ]);

        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testCreateFileVersionFailsIfCreateCallFails(): void {
        $this->mock(FileVersionService::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('createNewVersion')
                ->once()
                ->andThrow(new Exception('Test exception'));
        });

        $file = File::factory()
            ->for($this->user)
            ->create();

        $uploadedFile = UploadedFile::fake()->create('new-version.txt');

        $response = $this->post("/files/{$file->uuid}/versions", [
            'file' => $uploadedFile,
        ]);

        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testShowFileVersionDownloadsFile(): void {
        /**
         * @var FileVersionService|MockInterface
         */
        $fileVersionServiceSpy = $this->instance(
            FileVersionService::class,
            Mockery::spy(FileVersionService::class, [
                Mockery::mock(FileEncryptionService::class),
                $this->storage,
            ])
        )->makePartial();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $versions = FileVersion::factory(3)
            ->for($file)
            ->create();

        $selectedVersion = $versions->get(1);

        $response = $this->get(
            "/files/{$file->uuid}/versions/{$selectedVersion->version}"
        );

        $response->assertOk();

        $response->assertDownload($file->name);

        $this->assertEquals(
            $this->storage->get($selectedVersion->storage_path),
            $response->streamedContent()
        );

        $fileVersionServiceSpy
            ->shouldHaveReceived('createDownloadResponse')
            ->withArgs(function (File $file, FileVersion $fileVersion) use (
                $selectedVersion
            ) {
                $this->assertEquals($selectedVersion->id, $fileVersion->id);

                return true;
            });
    }

    public function testShowFileVersionFailsIfFileVersionDoesNotExist(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/1");

        $response->assertNotFound();
    }

    public function testShowFileVersionFailsIfFileVersionDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/versions/{$fileVersion->version}"
        );

        $response->assertForbidden();
    }

    public function testEditFileVersionViewCanBeRendered(): void {
        $version = 632;

        $file = File::factory()
            ->for($this->user)
            ->create(['next_version' => $version]);

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/versions/{$fileVersion->version}/edit"
        );

        $response->assertOk();

        $response->assertSee($file->name);
        $response->assertSee($version);
    }

    public function testEditFileVersionViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/versions/{$fileVersion->version}/edit"
        );

        $response->assertForbidden();
    }

    public function testEditFileVersionViewFailsIfFileDoesNotExist(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/1/edit");

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

        $response = $this->put(
            "/files/{$file->uuid}/versions/{$fileVersion->version}",
            [
                'label' => $newLabel,
            ]
        );

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

        $response = $this->put(
            "/files/{$file->uuid}/versions/{$fileVersion->version}",
            [
                'label' => 'New Label',
            ]
        );

        $response->assertForbidden();
    }

    public function testFileVersionCanBeMovedToTrash(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->delete(
            "/files/{$file->uuid}/versions/{$fileVersion->version}"
        );

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

        $response = $this->delete(
            "/files/{$file->uuid}/versions/{$fileVersion->version}"
        );

        $response->assertForbidden();
    }

    public function testFileVersionCannotBeMovedToTrashIfItDoesNotExist(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->delete("/files/{$file->uuid}/versions/1");

        $response->assertNotFound();
    }
}
