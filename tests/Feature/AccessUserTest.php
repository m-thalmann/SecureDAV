<?php

namespace Tests\Feature;

use App\Models\AccessUser;
use App\Models\File;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccessUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexAccessUsersViewCanBeRendered(): void {
        $fileCount = 13;

        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get('/access-users');

        $response->assertOk();

        $response->assertSee($accessUser->username);
        $response->assertSee($accessUser->label);
        $response->assertSee($fileCount);
    }

    public function testIndexAccessUsersViewDoesOnlyShowItemsOfTheAuthenticatedUser(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->create();

        $otherUser = $this->createUser();

        $otherAccessUser = AccessUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get('/access-users');

        $response->assertOk();

        $response->assertSee($accessUser->username);
        $response->assertDontSee($otherAccessUser->username);
    }

    public function testAccessUserCanBeDeleted(): void {
        $accessUser = AccessUser::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/access-users/{$accessUser->username}");

        $response->assertRedirect('/access-users');
        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_SUCCESS, $message->type);

            return true;
        });

        $this->assertDatabaseMissing('access_users', [
            'id' => $accessUser->id,
        ]);
        $this->assertDatabaseMissing('access_user_files', [
            'access_user_id' => $accessUser->id,
        ]);
    }

    public function testAccessUserCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $accessUser = AccessUser::factory()
            ->for($otherUser)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/access-users/{$accessUser->username}");

        $response->assertForbidden();

        $this->assertDatabaseHas('access_user_files', [
            'access_user_id' => $accessUser->id,
        ]);
    }
}
