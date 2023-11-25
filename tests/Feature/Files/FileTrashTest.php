<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function testFileCanBeDeleted(): void {
        /**
         * @var FilesystemAdapter
         */
        $storageFake = Storage::fake('files');

        $file = File::factory()
            ->for($this->user)
            ->create();

        $versions = FileVersion::factory(2)
            ->for($file)
            ->create();

        $file->delete();

        $response = $this->delete("/files/trash/{$file->uuid}");

        $response->assertRedirect('/files/trash');

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
            $storageFake->assertMissing($version->storage_path);
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

