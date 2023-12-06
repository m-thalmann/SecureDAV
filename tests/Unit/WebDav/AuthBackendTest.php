<?php

namespace Tests\Unit\WebDav;

use App\Models\User;
use App\Models\WebDavUser;
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
        $webDavUser = WebDavUser::factory()->create();

        $this->assertFalse(
            $this->authBackend->validateUserPass($webDavUser->username, 'test')
        );
    }

    public function testValidateUserPassReturnsFalseIfWebDavUserIsNotActive(): void {
        $webDavUser = WebDavUser::factory()->create(['active' => false]);

        $this->assertFalse(
            $this->authBackend->validateUserPass(
                $webDavUser->username,
                'password'
            )
        );
    }

    public function testValidateUserPassReturnsFalseIfUserHasSuspendedWebDav(): void {
        $user = User::factory()->create(['is_webdav_suspended' => true]);

        $webDavUser = WebDavUser::factory()
            ->for($user)
            ->create();

        $this->assertFalse(
            $this->authBackend->validateUserPass(
                $webDavUser->username,
                'password'
            )
        );
    }

    public function testValidateUserPassReturnsTrueAndSetsAuthUserIfLoginSucceeds(): void {
        $webDavUser = WebDavUser::factory()->create();

        $this->assertTrue(
            $this->authBackend->validateUserPass(
                $webDavUser->username,
                'password'
            )
        );

        $this->assertEquals(
            $webDavUser->id,
            $this->authBackend->getAuthenticatedWebDavUser()->id
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
        $webDavUser = WebDavUser::factory()->create();

        for (
            $i = 0;
            $i < $this->authBackend->getRateLimiterAttempts() - 1;
            $i++
        ) {
            $this->authBackend->validateUserPass(
                $webDavUser->username,
                'wrong-password'
            );
        }

        $this->assertTrue(
            $this->authBackend->validateUserPass(
                $webDavUser->username,
                'password'
            )
        );

        $this->assertFalse(
            $this->authBackend->validateUserPass(
                $webDavUser->username,
                'wrong-password'
            )
        );
    }

    public function testGetAuthenticatedWebDavUserReturnsNullIfNoUserIsAuthenticated(): void {
        $this->assertNull($this->authBackend->getAuthenticatedWebDavUser());
    }

    public function testGetAuthenticatedUserReturnsUserIfUserIsAuthenticated(): void {
        $webDavUser = WebDavUser::factory()->create();

        $this->authBackend->validateUserPass($webDavUser->username, 'password');

        $this->assertEquals(
            $webDavUser->user->id,
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
