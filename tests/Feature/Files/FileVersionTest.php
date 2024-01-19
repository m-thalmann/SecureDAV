<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Services\EncryptionService;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FileVersionTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
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

        $response->assertNotFound();
    }

    public function testNewFileVersionCanBeCreatedWithUploadFile(): void {
        $uploadedFile = UploadedFile::fake()->createWithContent(
            'new-version.txt',
            'Test content'
        );

        $file = File::factory()
            ->for($this->user)
            ->create();

        $label = 'New version';

        $response = $this->post("/files/{$file->uuid}/versions", [
            'label' => $label,
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'mime_type' => $uploadedFile->getMimeType(),
            'label' => $label,
            'version' => 1,
        ]);

        $newVersion = $file->latestVersion;

        $this->assertNotNull($newVersion->storage_path);

        $this->storageFake->assertExists(
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
        $fileVersionContent = $this->storageFake->get(
            $fileVersion->storage_path
        );

        $response = $this->post("/files/{$file->uuid}/versions", [
            'label' => 'New version',
        ]);

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'mime_type' => $fileVersion->mime_type,
            'label' => 'New version',
            'version' => $fileVersion->version + 1,
        ]);

        $newVersion = $file->latestVersion;

        $this->assertNotNull($newVersion->storage_path);

        $this->storageFake->assertExists(
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

        $deletedFileVersion = FileVersion::factory()
            ->for($file)
            ->create(['version' => $fileVersion->version + 1]);

        $deletedFileVersion->delete();

        $response = $this->post("/files/{$file->uuid}/versions");

        $response->assertRedirect("/files/{$file->uuid}#file-versions");

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'version' => $deletedFileVersion->version + 1,
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

        $response->assertNotFound();
    }

    public function testNewFileVersionCantBeCreatedWithoutAnUploadFileIfFileDoesntHaveAnyVersions(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->post("/files/{$file->uuid}/versions", [
            'label' => 'New version',
        ]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message'
        );
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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message'
        );
    }

    /**
     * @dataProvider isEncryptedProvider
     */
    public function testShowFileVersionDownloadsFile(bool $isEncrypted): void {
        /**
         * @var FileVersionService|MockInterface
         */
        $fileVersionServiceSpy = $this->instance(
            FileVersionService::class,
            Mockery::spy(FileVersionService::class, [
                Mockery::mock(EncryptionService::class),
                $this->storageFake,
            ])
        )->makePartial();

        $fileFactory = File::factory()->for($this->user);

        if ($isEncrypted) {
            $fileFactory->encrypted();
        }

        $file = $fileFactory->create();

        $content = 'Test content';
        $resource = $this->createStream($content);

        $version = $fileVersionServiceSpy->createNewVersion($file, $resource);

        $otherVersions = FileVersion::factory(3)
            ->for($file)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/versions/{$version->version}"
        );

        $response->assertOk();

        $response->assertDownload($file->name);

        $this->assertEquals($content, $response->streamedContent());

        $fileVersionServiceSpy
            ->shouldHaveReceived('createDownloadResponse')
            ->withArgs(function (File $file, FileVersion $fileVersion) use (
                $version
            ) {
                $this->assertEquals($version->id, $fileVersion->id);

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

        $response->assertNotFound();
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

        $response->assertNotFound();
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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

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

        $response->assertNotFound();
    }

    public function testMoveFileVersionToTrashConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $fileVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $confirmResponse = $this->delete(
            "/files/{$file->uuid}/versions/{$fileVersion->version}"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testFileVersionCanBeMovedToTrash(): void {
        $this->passwordConfirmed();

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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('file_versions', [
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

        $response->assertNotFound();
    }

    public function testFileVersionCannotBeMovedToTrashIfItDoesNotExist(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->delete("/files/{$file->uuid}/versions/1");

        $response->assertNotFound();
    }

    public static function isEncryptedProvider(): array {
        return [[false], [true]];
    }
}
