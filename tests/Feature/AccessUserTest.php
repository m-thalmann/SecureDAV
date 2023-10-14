<?php

namespace Tests\Feature;

use App\Models\AccessUser;
use App\Models\File;
use App\Models\User;
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
}
