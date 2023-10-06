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

    public function testEditLatestFileVersionViewCanBeRendered(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/file-versions/latest/edit"
        );

        $response->assertOk();

        $response->assertSee($file->fileName);
    }

    public function testEditLatestFileVersionViewFailsIfFileHasNoVersions(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/file-versions/latest/edit"
        );

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

        $response = $this->get(
            "/files/{$file->uuid}/file-versions/latest/edit"
        );

        $response->assertForbidden();
    }

    public function testEditLatestFileVersionViewFailsIfFileDoesNotExist(): void {
        $response = $this->get(
            '/files/does-not-exist/file-versions/latest/edit'
        );

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
            ->create(['mime_type' => $uploadedFile->getClientMimeType()]);

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->put("/files/{$file->uuid}/file-versions/latest", [
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

        $this->storage->assertExists($fileVersion->storage_path, $content);
    }

    public function testLatestFileVersionCantBeUpdatedIfMimeTypesMismatch(): void {
        $file = File::factory()
            ->for($this->user)
            ->create(['mime_type' => 'text/plain']);

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $response = $this->from(
            "/files/{$file->uuid}/file-versions/latest/edit"
        )->put("/files/{$file->uuid}/file-versions/latest", [
            'file' => UploadedFile::fake()->create(
                'new-version.jpg',
                'image/jpeg'
            ),
        ]);

        $response->assertRedirect(
            "/files/{$file->uuid}/file-versions/latest/edit"
        );

        $response->assertSessionHasErrors(['file']);
    }

    public function testLatestFileVersionCantBeUpdatedIfFileHasNoLatestVersion(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}/file-versions/latest", [
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
        $this->mock(FileVersionService::class, function ($mock) {
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
            "/files/{$file->uuid}/file-versions/latest/edit"
        )->put("/files/{$file->uuid}/file-versions/latest", [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect(
            "/files/{$file->uuid}/file-versions/latest/edit"
        );

        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
    }
}
