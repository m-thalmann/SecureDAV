<?php

namespace Tests\Feature\Files;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FileMoveTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testMoveFileViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $directories = Directory::factory(4)
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/files/{$file->uuid}/move");

        $response->assertOk();

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }
    }

    public function testMoveFileViewCanBeRenderedWithDirectory(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/move?directory={$directory->uuid}"
        );

        $response->assertOk();

        $response->assertSee($directory->name);
        $response->assertSee('Empty directory');
    }

    public function testMoveFileViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get("/files/{$file->uuid}/move");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testMoveFileViewCantBeRenderedIfUserCantEditFile(): void {
        $file = File::factory()->create();

        $response = $this->get("/files/{$file->uuid}/move");

        $response->assertNotFound();
    }

    public function testMoveFileViewCantBeRenderedIfUserCantEditDirectory(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()->create();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/files/{$file->uuid}/move?directory={$directory->uuid}"
        );

        $response->assertNotFound();
    }

    public function testFileCanBeMoved(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}/move", [
            'directory_uuid' => $directory->uuid,
        ]);

        $response->assertRedirectToRoute('files.show', ['file' => $file]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $file->refresh();

        $this->assertEquals($directory->id, $file->directory_id);
    }

    public function testFileCanBeMovedToRootDirectory(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}/move", [
            'directory_uuid' => null,
        ]);

        $response->assertRedirectToRoute('files.show', ['file' => $file]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $file->refresh();

        $this->assertNull($file->directory_id);
    }

    public function testMoveFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->put("/files/{$file->uuid}/move", [
            'directory_uuid' => null,
        ]);
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testFileCantBeMovedIfUserCantEditFile(): void {
        $file = File::factory()->create();

        $response = $this->put("/files/{$file->uuid}/move", [
            'directory_uuid' => null,
        ]);

        $response->assertNotFound();
    }

    public function testFileCantBeMovedIfUserCantEditDirectory(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()->create();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $response = $this->put("/files/{$file->uuid}/move", [
            'directory_uuid' => $directory->uuid,
        ]);

        $response->assertNotFound();
    }

    public function testFileCantBeMovedToRootDirectoryIfNameIsNotUnique(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->has(Directory::factory()->for($this->user))
            ->create();

        $otherFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null, 'name' => $file->name]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/files/{$file->uuid}/move",
            [
                'directory_uuid' => null,
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message'
        );

        $directoryId = $file->directory_id;

        $file->refresh();

        $this->assertEquals($directoryId, $file->directory_id);
    }

    public function testFileCantBeMovedToDirectoryIfNameIsNotUnique(): void {
        $this->passwordConfirmed();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $otherDirectory = Directory::factory()
            ->for($this->user)
            ->for($directory, 'parentDirectory')
            ->create(['name' => $file->name]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/files/{$file->uuid}/move",
            [
                'directory_uuid' => $directory->uuid,
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message'
        );

        $directoryId = $file->directory_id;

        $file->refresh();

        $this->assertEquals($directoryId, $file->directory_id);
    }
}

