<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\User;
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
}
