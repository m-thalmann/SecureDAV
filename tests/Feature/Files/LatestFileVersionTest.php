<?php

namespace Tests\Feature\Files;

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

class LatestFileVersionTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;
    protected FilesystemAdapter $storage;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);

        $this->storage = Storage::fake('files');
    }

    public function testShowLatestFileVersionDownloadsFile(): void {
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

        FileVersion::factory(2)
            ->for($file)
            ->create();

        $latestVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest");

        $response->assertOk();

        $response->assertDownload($file->name);

        $this->assertEquals(
            $this->storage->get($latestVersion->storage_path),
            $response->streamedContent()
        );

        $fileVersionServiceSpy
            ->shouldHaveReceived('createDownloadResponse')
            ->withArgs(function (File $file, FileVersion $fileVersion) use (
                $latestVersion
            ) {
                $this->assertEquals($latestVersion->id, $fileVersion->id);

                return true;
            });
    }

    public function testShowLatestFileVersionFailsIfFileHasNoVersions(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest");

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testShowLatestFileVersionFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest");

        $response->assertForbidden();
    }

    public function testShowLatestFileVersionViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist/versions/latest');

        $response->assertNotFound();
    }

    public function testEditLatestFileVersionViewCanBeRendered(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest/edit");

        $response->assertOk();

        $response->assertSee($file->name);
    }

    public function testEditLatestFileVersionViewFailsIfFileHasNoVersions(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest/edit");

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testEditLatestFileVersionViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest/edit");

        $response->assertForbidden();
    }

    public function testEditLatestFileVersionViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist/versions/latest/edit');

        $response->assertNotFound();
    }

    public function testLatestFileVersionCanBeUpdated(): void {
        $content = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'new-version.txt',
            $content
        );

        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create(['mime_type' => 'application/json']);

        $response = $this->put("/files/{$file->uuid}/versions/latest", [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $fileVersion->refresh();

        $this->assertEquals(
            $fileVersion->id,
            $file->refresh()->latestVersion->id
        );

        $this->assertEquals(
            $uploadedFile->getClientMimeType(),
            $fileVersion->mime_type
        );

        $this->storage->assertExists($fileVersion->storage_path, $content);
    }

    public function testLatestFileVersionCantBeUpdatedIfFileHasNoLatestVersion(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}/versions/latest", [
            'file' => UploadedFile::fake()->create('new-version.txt'),
        ]);

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }

    public function testLatestFileVersionCantBeUpdatedIfCreateCallFails(): void {
        $this->mock(FileVersionService::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('updateLatestVersion')
                ->once()
                ->andThrow(new Exception('Test exception'));
        });

        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $uploadedFile = UploadedFile::fake()->create('new-version.txt');

        $response = $this->from(
            "/files/{$file->uuid}/versions/latest/edit"
        )->put("/files/{$file->uuid}/versions/latest", [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect("/files/{$file->uuid}/versions/latest/edit");

        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }
}
