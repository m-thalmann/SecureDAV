<?php

namespace Tests\Feature\AccessGroups;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JumpToAccessGroupUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testJumpsToAccessGroupUserIfIsFound(): void {
        $group = AccessGroup::factory()
            ->for($this->user)
            ->create();

        $user = AccessGroupUser::factory()
            ->for($group)
            ->create();

        $response = $this->post('/access-group-users/jump-to', [
            'username' => $user->username,
        ]);

        $response->assertRedirect("/access-groups/{$group->uuid}");
    }

    public function testFailsIfAccessGroupUserIsNotFound(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            '/access-group-users/jump-to',
            [
                'username' => 'nonexistent',
            ]
        );

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);
    }

    public function testFailsIfAccessGroupUserIsNotOwnedByUser(): void {
        $group = AccessGroup::factory()->create();

        $user = AccessGroupUser::factory()
            ->for($group)
            ->create();

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            '/access-group-users/jump-to',
            [
                'username' => $user->username,
            ]
        );

        $response->assertSessionHas('snackbar', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);

            return true;
        });

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);
    }
}
