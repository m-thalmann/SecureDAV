<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testShowFileViewCanBeRendered(): void {
        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}");

        $response->assertOk();

        $response->assertSee($file->name);
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
