<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BrowseTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testBrowseViewCanBeRendered(): void {
        $response = $this->get('/browse');

        $response->assertSee(route('webdav.directories'));

        $response->assertOk();
    }

    public function testBrowseViewCanBeRenderedWithDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/browse/$directory->uuid");

        $response->assertSee(
            route('webdav.directories', [
                collect($directory->breadcrumbs)
                    ->map(fn(Directory $directory) => $directory->name)
                    ->join('/'),
            ])
        );

        $response->assertOk();
    }

    public function testViewShowsAllFilesAndDirectoriesInBaseDirectoryForUser(): void {
        $files = File::factory(10)
            ->for($this->user)
            ->create(['directory_id' => null]);

        $directories = Directory::factory(10)
            ->for($this->user)
            ->create(['parent_directory_id' => null]);

        $response = $this->get('/browse');

        $response->assertOk();

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }
    }

    public function testViewDoesNotShowFilesAndDirectoriesOfOtherUsers(): void {
        $otherUser = $this->createUser();

        $files = File::factory(5)
            ->for($otherUser)
            ->sequence(
                fn(Sequence $sequence) => [
                    'name' => 'FileName ' . $sequence->index,
                ]
            )
            ->create(['directory_id' => null]);

        $directories = Directory::factory(5)
            ->for($otherUser)
            ->sequence(
                fn(Sequence $sequence) => [
                    'name' => 'DirectoryName ' . $sequence->index,
                ]
            )
            ->create(['parent_directory_id' => null]);

        $response = $this->get('/browse');

        $response->assertOk();

        foreach ($files as $file) {
            $response->assertDontSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertDontSee($directory->name);
        }
    }

    public function testViewShowsAllFilesAndDirectoriesInDirectoryForUser(): void {
        $currentDirectory = Directory::factory()
            ->for($this->user)
            ->sequence(
                fn(Sequence $sequence) => [
                    'name' => 'DirectoryName ' . $sequence->index,
                ]
            )
            ->create();

        $files = File::factory(5)
            ->for($this->user)
            ->sequence(
                fn(Sequence $sequence) => [
                    'name' => 'FileName ' . $sequence->index,
                ]
            )
            ->create(['directory_id' => $currentDirectory->id]);

        $directories = Directory::factory(5)
            ->for($this->user)
            ->create(['parent_directory_id' => $currentDirectory->id]);

        $response = $this->get("/browse/{$currentDirectory->uuid}");

        $response->assertOk();

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }
    }

    public function testDirectoryOfOtherUserCantBeBrowsed(): void {
        $otherUser = $this->createUser();

        $currentDirectory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/browse/{$currentDirectory->uuid}");

        $response->assertForbidden();
    }

    public function testViewReceivesBreadcrumbs(): void {
        $parentDirectory = Directory::factory()
            ->for($this->user)
            ->create(['parent_directory_id' => null]);

        $currentDirectory = Directory::factory()
            ->for($this->user)
            ->create(['parent_directory_id' => $parentDirectory->id]);

        $response = $this->get("/browse/{$currentDirectory->uuid}");

        $response->assertOk();

        $response->assertViewHas('breadcrumbs', function (array $breadcrumbs) {
            $this->assertCount(2, $breadcrumbs);

            return true;
        });
    }
}
