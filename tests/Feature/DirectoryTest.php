<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testEditDirectoryViewCanBeRendered(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $response = $this->get("/directories/{$directory->uuid}/edit");

        $response->assertOk();

        $response->assertSee($directory->name);
    }

    public function testEditDirectoryViewCantBeRenderedForOtherUser(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/directories/{$directory->uuid}/edit");

        $response->assertForbidden();
    }

    public function testDirectoryNameCanBeEdited(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $otherDirectory = Directory::factory()
            ->for($this->user)
            ->create(['parent_directory_id' => $directory->id]);

        $newName = $otherDirectory->name;

        $response = $this->put("/directories/{$directory->uuid}", [
            'name' => $newName,
        ]);

        $response->assertRedirect("/browse/{$directory->uuid}");
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseHas('directories', [
            'id' => $directory->id,
            'name' => $newName,
        ]);
    }

    public function testDirectoryCantBeRenamedIfNameAlreadyExistsInSameDirectoryForUser(): void {
        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $otherDirectory = Directory::factory()
            ->for($this->user)
            ->create([
                'parent_directory_id' => $directory->parent_directory_id,
            ]);

        $response = $this->from("/directories/{$directory->uuid}/edit")->put(
            "/directories/{$directory->uuid}",
            [
                'name' => $otherDirectory->name,
            ]
        );

        $response->assertRedirect("/directories/{$directory->uuid}/edit");
        $response->assertSessionHasErrors('name');
    }

    public function testDirectoryCantBeRenamedForOtherUser(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->put("/directories/{$directory->uuid}", [
            'name' => 'NewName',
        ]);

        $response->assertForbidden();
    }
}
