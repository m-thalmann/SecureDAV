<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Services\FileEncryptionService;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LatestFileVersionTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testShowLatestFileVersionDownloadsFile(): void {
        /**
         * @var FileVersionService|MockInterface
         */
        $fileVersionServiceSpy = $this->instance(
            FileVersionService::class,
            Mockery::spy(FileVersionService::class, [
                Mockery::mock(FileEncryptionService::class),
                $this->storageFake,
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
            $this->storageFake->get($latestVersion->storage_path),
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
        $file = File::factory()
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest");

        $response->assertNotFound();
    }

    public function testShowLatestFileVersionViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist/versions/latest');

        $response->assertNotFound();
    }

    public function testEditLatestFileVersionViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $confirmResponse = $this->get(
            "/files/{$file->uuid}/versions/latest/edit"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testEditLatestFileVersionViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest/edit");

        $response->assertOk();

        $response->assertSee($file->name);
    }

    public function testEditLatestFileVersionViewFailsIfFileHasNoVersions(): void {
        $this->passwordConfirmed();

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
        $file = File::factory()
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $response = $this->get("/files/{$file->uuid}/versions/latest/edit");

        $response->assertNotFound();
    }

    public function testEditLatestFileVersionViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist/versions/latest/edit');

        $response->assertNotFound();
    }

    public function testUpdateLatestFileVersionConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $uploadedFile = UploadedFile::fake()->create('new-version.txt');

        $file = File::factory()
            ->for($this->user)
            ->has(
                FileVersion::factory()->state([
                    'mime_type' => 'application/json',
                ]),
                'versions'
            )
            ->create();

        $confirmResponse = $this->put("/files/{$file->uuid}/versions/latest", [
            'file' => $uploadedFile,
        ]);
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testLatestFileVersionCanBeUpdated(): void {
        $this->passwordConfirmed();

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

        $this->storageFake->assertExists($fileVersion->storage_path, $content);
    }

    public function testLatestFileVersionCantBeUpdatedIfFileHasNoLatestVersion(): void {
        $this->passwordConfirmed();

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
        $this->passwordConfirmed();

        $this->mock(FileVersionService::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('updateLatestVersion')
                ->once()
                ->andThrow(new Exception('Test exception'));
        });

        $file = File::factory()
            ->for($this->user)
            ->has(FileVersion::factory(), 'versions')
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

