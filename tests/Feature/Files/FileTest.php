<?php

namespace Tests\Feature\Files;

use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

class FileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testCreateFileViewCanBeRendered(): void {
        $response = $this->get('/files/create');

        $response->assertOk();
    }

    public function testCreateFileViewCanBeRenderedWithDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/create?directory={$directory->uuid}");

        $response->assertOk();

        $response->assertSee($directory->name);
    }

    public function testCreateFileViewFailsIfDirectoryDoesntExist(): void {
        $response = $this->get('/files/create?directory=nonexistent');

        $response->assertNotFound();
    }

    public function testCreateFileViewFailsIfUserCantUpdateDirectory(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/files/create?directory={$directory->uuid}");

        $response->assertNotFound();
    }

    public function testFileCanBeCreated(): void {
        $fileName = 'NewFile.txt';
        $content = fake()->text();

        $uploadFile = UploadedFile::fake()->createWithContent(
            $fileName,
            $content
        );

        $description = 'New Description';

        $this->mock(
            FileVersionService::class,
            fn(MockInterface $mock) => $mock
                ->shouldReceive('createNewVersion')
                ->withArgs(function (
                    File $createdFile,
                    mixed $receivedResource,
                    bool $encrypt
                ) use ($fileName, $content) {
                    $this->assertEquals($fileName, $createdFile->name);
                    $this->assertIsResource($receivedResource);
                    $this->assertTrue($encrypt);

                    $receivedContent = stream_get_contents($receivedResource);
                    rewind($receivedResource);

                    $this->assertEquals($content, $receivedContent);

                    return true;
                })
                ->once()
        );

        $response = $this->post('/files', [
            'file' => $uploadFile,
            'name' => $fileName,
            'encrypt' => true,
            'description' => $description,
        ]);

        $this->assertDatabaseHas('files', [
            'user_id' => $this->user->id,
            'name' => $fileName,
            'description' => $description,
        ]);

        $createdFile = File::query()
            ->where('name', $fileName)
            ->first();

        // version will not be created since the `createNewVersion` call is mocked

        $response->assertRedirect("/files/{$createdFile->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testFileCanBeCreatedWithDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $fileName = 'NewFile.txt';
        $uploadFile = UploadedFile::fake()->create($fileName);

        $response = $this->post('/files', [
            'file' => $uploadFile,
            'name' => $fileName,
            'directory_uuid' => $directory->uuid,
        ]);

        $this->assertDatabaseHas('files', [
            'name' => $fileName,
            'directory_id' => $directory->id,
            'user_id' => $this->user->id,
        ]);

        $createdFile = File::query()
            ->where('name', $fileName)
            ->first();

        $response->assertRedirect("/files/{$createdFile->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testFileCanBeCreatedWithoutInitialization(): void {
        $fileName = 'NewFile.txt';

        $this->mock(
            FileVersionService::class,
            fn(MockInterface $mock) => $mock->shouldNotReceive(
                'createNewVersion'
            )
        );

        $response = $this->post('/files', [
            'name' => $fileName,
            'initialize' => 'false',
        ]);

        $this->assertDatabaseHas('files', [
            'name' => $fileName,
            'user_id' => $this->user->id,
        ]);

        $createdFile = File::query()
            ->where('name', $fileName)
            ->first();

        $this->assertDatabaseMissing('file_versions', [
            'file_id' => $createdFile->id,
        ]);

        $response->assertRedirect("/files/{$createdFile->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testFileCantBeCreatedIfDirectoryDoesntExist(): void {
        $uploadFile = UploadedFile::fake()->create('test-file.txt');

        $response = $this->post('/files', [
            'file' => $uploadFile,
            'directory_uuid' => 'nonexistent',
        ]);

        $response->assertNotFound();
    }

    public function testFileCantBeCreatedIfUserCantUpdateDirectory(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $uploadFile = UploadedFile::fake()->create('test-file.txt');

        $response = $this->post('/files', [
            'file' => $uploadFile,
            'directory_uuid' => $directory->uuid,
        ]);

        $response->assertNotFound();
    }

    public function testFileCantBeCreatedIfNameIsNotUniqueInDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $uploadFile = UploadedFile::fake()->create($file->name);

        $response = $this->from('/files/create')->post('/files', [
            'file' => $uploadFile,
            'name' => $file->name,
            'directory_uuid' => $directory->uuid,
        ]);

        $response->assertRedirect('/files/create');

        $response->assertSessionHasErrors('name');
    }

    public function testFileCantBeCreatedIfDirectoryWithSameNameExistsInDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $childDirectory = Directory::factory()
            ->for($this->user)
            ->for($directory, 'parentDirectory')
            ->create();

        $uploadFile = UploadedFile::fake()->create($childDirectory->name);

        $response = $this->from('/files/create')->post('/files', [
            'file' => $uploadFile,
            'name' => $childDirectory->name,
            'directory_uuid' => $directory->uuid,
        ]);

        $response->assertRedirect('/files/create');

        $response->assertSessionHasErrors('name');
    }

    public function testCreateFileFailsAndDoesNotStoreFileAndVersionIfVersionCantBeCreated(): void {
        $fileName = 'NewFile.txt';

        $uploadFile = UploadedFile::fake()->create($fileName);

        $this->mock(
            FileVersionService::class,
            fn(MockInterface $mock) => $mock
                ->shouldReceive('createNewVersion')
                ->once()
                ->andThrow(new Exception('Test exception'))
        );

        $response = $this->from('/files/create')->post('/files', [
            'file' => $uploadFile,
            'name' => $fileName,
            'encrypt' => true,
        ]);

        $this->assertDatabaseEmpty('files');
        $this->assertDatabaseEmpty('file_versions');

        $response->assertRedirect('/files/create');

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message'
        );
    }

    public function testShowFileViewCanBeRendered(): void {
        $versionCount = 4;

        $file = File::factory()
            ->for($this->user)
            ->has(FileVersion::factory($versionCount), 'versions')
            ->create(['description' => fake()->text]);

        $response = $this->get("/files/{$file->uuid}");

        $response->assertOk();

        $response->assertSee($file->name);
        $response->assertSee($file->description);
        $response->assertSee($file->latestVersion->mime_type);
        $response->assertSee($file->webdavUrl);
        $response->assertSee("($versionCount)");
    }

    public function testShowFileViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->get("/files/{$file->uuid}");

        $response->assertNotFound();
    }

    public function testShowFileViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist');

        $response->assertNotFound();
    }

    public function testEditFileViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get("/files/{$file->uuid}/edit");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testEditFileViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/edit");

        $response->assertOk();

        $response->assertSee($file->name);
    }

    public function testEditFileViewFailsIfFileDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->get("/files/{$file->uuid}/edit");

        $response->assertNotFound();
    }

    public function testEditFileViewFailsIfFileDoesNotExist(): void {
        $response = $this->get('/files/does-not-exist/edit');

        $response->assertNotFound();
    }

    public function testEditFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->put("/files/{$file->uuid}", [
            'name' => 'New name',
        ]);
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testFileCanBeEdited(): void {
        $this->passwordConfirmed();

        $newName = 'New Name.txt';
        $newDescription = 'New Description';

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}", [
            'name' => $newName,
            'description' => $newDescription,
        ]);

        $response->assertRedirect("/files/{$file->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $file->refresh();

        $this->assertEquals($newName, $file->name);
        $this->assertEquals($newDescription, $file->description);
    }

    public function testFileCannotBeEditedIfItDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->put("/files/{$file->uuid}", [
            'name' => 'New Name.txt',
            'description' => 'New Description',
        ]);

        $response->assertNotFound();
    }

    public function testFileCantBeRenamedIfNameAlreadyExistsInSameDirectoryForUser(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $otherFile = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $response = $this->put("/files/{$file->uuid}", [
            'name' => $otherFile->name,
            'description' => 'New Description',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function testFileCantBeRenamedIfDirectoryWithSameNameAlreadyExistsInSameDirectoryForUser(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $childDirectory = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $response = $this->put("/files/{$file->uuid}", [
            'name' => $childDirectory->name,
            'description' => 'New Description',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function testUpdateAutoVersionHoursConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/files/{$file->uuid}/auto-version-hours",
            [
                'hours' => 2,
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testAutoVersionHoursCanBeUpdated(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $hours = 23.5;

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/files/{$file->uuid}/auto-version-hours",
            [
                'hours' => $hours,
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $file->refresh();

        $this->assertEquals($hours, $file->auto_version_hours);
    }

    public function testUpdateAutoVersionHoursFailsIfFileDoesNotBelongToUser(): void {
        $this->passwordConfirmed();

        $file = File::factory()->create();

        $response = $this->put("/files/{$file->uuid}/auto-version-hours", [
            'hours' => 23.5,
        ]);

        $response->assertNotFound();
    }

    public function testMoveFileToTrashConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->delete("/files/{$file->uuid}");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testFileCanBeMovedToTrash(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create();

        $response = $this->delete("/files/{$file->uuid}");

        $response->assertRedirect("/browse/{$directory->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertSoftDeleted($file);
    }

    public function testFileCannotBeMovedToTrashIfItDoesNotBelongToUser(): void {
        $file = File::factory()->create();

        $response = $this->delete("/files/{$file->uuid}");

        $response->assertNotFound();
    }

    public function testFileCannotBeMovedToTrashIfItDoesNotExist(): void {
        $response = $this->delete('/files/does-not-exist');

        $response->assertNotFound();
    }

    public function testTrashedFileIsPrunedAfterAutoDeleteDays(): void {
        $amountDays = 2;

        config(['core.files.trash.auto_delete_days' => $amountDays]);

        $file = File::factory()
            ->for($this->user)
            ->create([
                'deleted_at' => now()->subDays($amountDays),
            ]);

        $file->pruneAll();

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);
    }

    public function testTrashedFileIsNotPrunedBeforeAutoDeleteDays(): void {
        $amountDays = 2;

        config(['core.files.trash.auto_delete_days' => $amountDays]);

        $file = File::factory()
            ->for($this->user)
            ->create([
                'deleted_at' => now()->subDays($amountDays - 1),
            ]);

        $file->pruneAll();

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);
    }
}
