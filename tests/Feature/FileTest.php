<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Services\FileVersionService;
use App\View\Helpers\SessionMessage;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class FileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);

        Storage::fake('files');
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

        $response->assertForbidden();
    }

    public function testFileCanBeCreated(): void {
        $fileName = 'NewFile';
        $fileExtension = 'txt';
        $content = fake()->text();

        $uploadFile = UploadedFile::fake()->createWithContent(
            "$fileName.$fileExtension",
            $content
        );

        $description = 'New Description';

        $this->mock(
            FileVersionService::class,
            fn(MockInterface $mock) => $mock
                ->shouldReceive('createNewVersion')
                ->withArgs(function (
                    File $createdFile,
                    UploadedFile $receivedFile
                ) use ($fileName, $uploadFile) {
                    $this->assertEquals($fileName, $createdFile->name);
                    $this->assertEquals($uploadFile, $receivedFile);

                    $this->assertIsString($createdFile->encryption_key);

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
            'extension' => $fileExtension,
            'description' => $description,
        ]);

        $createdFile = File::query()
            ->where('name', $fileName)
            ->first();

        $this->assertIsString($createdFile->encryption_key);

        $response->assertRedirect("/files/{$createdFile->uuid}");

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });
    }

    public function testFileCanBeCreatedWithDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $fileName = 'NewFile';
        $fileExtension = 'txt';
        $uploadFile = UploadedFile::fake()->create("$fileName.$fileExtension");

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

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });
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

        $response->assertForbidden();
    }

    public function testFileCantBeCreatedIfNameIsNotUniqueInFileDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->create(['directory_id' => $directory->id]);

        $uploadFile = UploadedFile::fake()->create('test-file.txt');

        $response = $this->from('/files/create')->post('/files', [
            'file' => $uploadFile,
            'name' => $file->name,
            'directory_uuid' => $directory->uuid,
        ]);

        $response->assertRedirect('/files/create');

        $response->assertSessionHasErrors('name');
    }

    public function testCreateFileFailsAndDoesNotStoreFileAndVersionIfVersionCantBeCreated(): void {
        $fileName = 'NewFile';
        $fileExtension = 'txt';

        $uploadFile = UploadedFile::fake()->create("$fileName.$fileExtension");

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

        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });
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
