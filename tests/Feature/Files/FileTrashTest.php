<?php

namespace Tests\Feature\Files;

use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FileTrashTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexCanBeRendered(): void {
        $response = $this->get('/files/trash');

        $response->assertOk();
    }

    public function testIndexShowsFilesInTrash(): void {
        $files = File::factory(5)
            ->for($this->user)
            ->trashed()
            ->create();

        $response = $this->get('/files/trash');

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }
    }

    public function testIndexDoesNotShowFilesNotInTrash(): void {
        $files = File::factory(5)
            ->for($this->user)
            ->create();

        $response = $this->get('/files/trash');

        foreach ($files as $file) {
            $response->assertDontSee($file->name);
        }
    }

    public function testIndexDoesNotShowFilesInTrashOfOtherUsers(): void {
        $files = File::factory(5)
            ->trashed()
            ->create();

        $response = $this->get('/files/trash');

        foreach ($files as $file) {
            $response->assertDontSee($file->name);
        }
    }

    public function testFileCanBeRestored(): void {
        $file = File::factory()
            ->for($this->user)
            ->trashed()
            ->create();

        $response = $this->put("/files/trash/{$file->uuid}");

        $response->assertRedirect('/files/trash');

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'deleted_at' => null,
        ]);
    }

    public function testFileCanBeRenamedWhenRestored(): void {
        $file = File::factory()
            ->for($this->user)
            ->trashed()
            ->create();

        $response = $this->put("/files/trash/{$file->uuid}", [
            'rename' => 'new name',
        ]);

        $response->assertRedirect('/files/trash');

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'name' => 'new name',
            'deleted_at' => null,
        ]);
    }

    public function testFileCantBeRestoredIfUserDoesNotOwnIt(): void {
        $file = File::factory()
            ->trashed()
            ->create();

        $response = $this->put("/files/trash/{$file->uuid}");

        $response->assertNotFound();

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'deleted_at' => $file->deleted_at,
        ]);
    }

    public function testFileCantBeRestoredIfNameIsNotUniqueInDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->for($directory)
            ->trashed()
            ->create();

        $otherFile = File::factory()
            ->for($this->user)
            ->for($directory)
            ->create(['name' => $file->name]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/files/trash/{$file->uuid}"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'deleted_at' => $file->deleted_at,
        ]);
    }

    public function testFileCantBeRestoredIfIsRenamedAndNameIsNotUniqueInDirectory(): void {
        $file = File::factory()
            ->for($this->user)
            ->trashed()
            ->create(['directory_id' => null]);

        $otherFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/files/trash/{$file->uuid}",
            [
                'rename' => $otherFile->name,
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'deleted_at' => $file->deleted_at,
        ]);
    }

    public function testFileCanBeDeleted(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $versions = FileVersion::factory(2)
            ->for($file)
            ->create();

        $file->delete();

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/files/trash/{$file->uuid}"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);

        foreach ($versions as $version) {
            $this->storageFake->assertMissing($version->storage_path);
        }
    }

    public function testFileCantBeDeletedIfUserDoesNotOwnIt(): void {
        $file = File::factory()
            ->trashed()
            ->create();

        $response = $this->delete("/files/trash/{$file->uuid}");

        $response->assertNotFound();

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);
    }
}

