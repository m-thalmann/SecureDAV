<?php

namespace Tests\Unit\WebDav;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\WebDav;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthBackendTest extends TestCase {
    use LazilyRefreshDatabase;

    protected WebDav\AuthBackend $authBackend;

    protected function setUp(): void {
        parent::setUp();

        $this->authBackend = new WebDav\AuthBackend();
    }

    public function testValidateUserPassReturnsFalseIfUserDoesNotExist(): void {
        $this->assertFalse(
            $this->authBackend->validateUserPass('test', 'test')
        );
    }

    public function testValidateUserPassReturnsFalseIfPasswordIsIncorrect(): void {
        $user = AccessGroupUser::factory()->create();

        $this->assertFalse(
            $this->authBackend->validateUserPass($user->username, 'test')
        );
    }

    public function testValidateUserPassReturnsFalseIfAccessGroupIsNotActive(): void {
        $accessGroup = AccessGroup::factory()->create(['active' => false]);

        $user = AccessGroupUser::factory()
            ->for($accessGroup)
            ->create();

        $this->assertFalse(
            $this->authBackend->validateUserPass($user->username, 'password')
        );
    }

    public function testValidateUserPassReturnsTrueAndSetsAuthUserIfLoginSucceeds(): void {
        $user = AccessGroupUser::factory()->create();

        $this->assertTrue(
            $this->authBackend->validateUserPass($user->username, 'password')
        );

        $this->assertEquals(
            $user->id,
            $this->authBackend->getAuthenticatedAccessGroupUser()->id
        );
    }

    public function testGetAuthenticatedAccessGroupUserReturnsNullIfNoUserIsAuthenticated(): void {
        $this->assertNull(
            $this->authBackend->getAuthenticatedAccessGroupUser()
        );
    }

    public function testGetAuthenticatedAccessGroupReturnsGroupIfUserIsAuthenticated(): void {
        $user = AccessGroupUser::factory()->create();

        $this->authBackend->validateUserPass($user->username, 'password');

        $this->assertEquals(
            $user->accessGroup->id,
            $this->authBackend->getAuthenticatedAccessGroup()->id
        );
    }

    public function testGetAuthenticatedAccessGroupReturnsNullIfNoUserIsAuthenticated(): void {
        $this->assertNull($this->authBackend->getAuthenticatedAccessGroup());
    }

    public function testGetAuthenticatedUserReturnsUserIfUserIsAuthenticated(): void {
        $user = AccessGroupUser::factory()->create();

        $this->authBackend->validateUserPass($user->username, 'password');

        $this->assertEquals(
            $user->accessGroup->user->id,
            $this->authBackend->getAuthenticatedUser()->id
        );
    }

    public function testGetAuthenticatedUserReturnsNullIfNoUserIsAuthenticated(): void {
        $this->assertNull($this->authBackend->getAuthenticatedUser());
    }

    public function testGetAuthenticatedUserIdReturnsUserIdIfUserIsAuthenticated(): void {
        $user = AccessGroupUser::factory()->create();

        $this->authBackend->validateUserPass($user->username, 'password');

        $this->assertEquals(
            $user->accessGroup->user->id,
            $this->authBackend->getAuthenticatedUserId()
        );
    }

    public function testGetAuthenticatedUserIdReturnsNullIfNoUserIsAuthenticated(): void {
        $this->assertNull($this->authBackend->getAuthenticatedUserId());
    }
}
