<?php

namespace Tests\Unit\WebDav;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Models\User;
use App\WebDav;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class AuthBackendTest extends TestCase {
    use LazilyRefreshDatabase;

    protected AuthBackendTestClass $authBackend;

    protected function setUp(): void {
        parent::setUp();

        $this->authBackend = new AuthBackendTestClass();
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

    public function testValidateUserPassReturnsFalseIfUserHasSuspendedWebDav(): void {
        $user = User::factory()->create(['is_webdav_suspended' => true]);

        $accessUser = AccessGroupUser::factory()
            ->for(AccessGroup::factory()->for($user))
            ->create();

        $this->assertFalse(
            $this->authBackend->validateUserPass(
                $accessUser->username,
                'password'
            )
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

    public function testValidateUserPassIsRateLimited(): void {
        $username = 'wrong-user';

        $availableIn = 43;

        $rateLimiterMock = $this->mockRateLimiter(['availableIn']);
        $rateLimiterMock
            ->shouldReceive('availableIn')
            ->once()
            ->andReturn($availableIn);

        for ($i = 0; $i < $this->authBackend->getRateLimiterAttempts(); $i++) {
            $this->assertFalse(
                $this->authBackend->validateUserPass($username, 'password')
            );
        }

        try {
            $this->authBackend->validateUserPass($username, 'password');
        } catch (WebDav\Exceptions\TooManyRequestsException $e) {
            $this->assertEquals(
                __('auth.throttle', [
                    'seconds' => $availableIn,
                    'minutes' => ceil($availableIn / 60),
                ]),
                $e->getMessage()
            );

            $this->assertEquals(
                Response::HTTP_TOO_MANY_REQUESTS,
                $e->getHTTPCode()
            );

            $this->assertEquals(
                ['Retry-After' => $availableIn],
                $e->getHTTPHeaders(null)
            );

            return;
        }

        $this->fail('TooManyRequestsException was not thrown');
    }

    public function testValidateUserPassIsRateLimitedPerUsername(): void {
        $username = 'wrong-user';

        for ($i = 0; $i < $this->authBackend->getRateLimiterAttempts(); $i++) {
            $this->authBackend->validateUserPass($username, 'password');
        }

        try {
            $this->authBackend->validateUserPass($username, 'password');
        } catch (WebDav\Exceptions\TooManyRequestsException $e) {
            $this->assertFalse(
                $this->authBackend->validateUserPass('another-user', 'password')
            );

            return;
        }

        $this->fail('TooManyRequestsException was not thrown');
    }

    public function testValidateUserPassRateLimitingIsResetAfterSuccessfulLogin(): void {
        $user = AccessGroupUser::factory()->create();

        for (
            $i = 0;
            $i < $this->authBackend->getRateLimiterAttempts() - 1;
            $i++
        ) {
            $this->authBackend->validateUserPass(
                $user->username,
                'wrong-password'
            );
        }

        $this->assertTrue(
            $this->authBackend->validateUserPass($user->username, 'password')
        );

        $this->assertFalse(
            $this->authBackend->validateUserPass(
                $user->username,
                'wrong-password'
            )
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
}

class AuthBackendTestClass extends WebDav\AuthBackend {
    public function getRateLimiterAttempts(): int {
        return static::RATE_LIMITER_ATTEMPTS;
    }
}
