<?php

namespace Tests;

use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;

    protected const REDIRECT_TEST_ROUTE = '/redirect-test-route';

    protected FilesystemAdapter $storageFake;

    protected function setUp(): void {
        parent::setUp();

        $this->storageFake = Storage::fake('files');
    }

    protected function createUser(bool $emailVerified = true): User {
        $user = User::factory();

        if (!$emailVerified) {
            $user = $user->unverified();
        }

        return $user->create();
    }

    protected function passwordConfirmed(): void {
        $this->session(['auth.password_confirmed_at' => time()]);
    }

    protected function mockRateLimiter(
        array $mockedMethods
    ): CacheRateLimiter|MockInterface {
        $rateLimiterMock = Mockery::mock(
            CacheRateLimiter::class . '[' . join(',', $mockedMethods) . ']',
            [$this->app->make('cache.store')]
        );

        RateLimiter::swap($rateLimiterMock);

        return $rateLimiterMock;
    }

    protected function getStreamedResponseContent(
        StreamedResponse $response
    ): string|false {
        ob_start();

        $response->send();

        return ob_get_clean();
    }

    protected function createStream(string $content): mixed {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    protected function assertHasSubArray(array $subarray, array $array): void {
        foreach ($subarray as $key => $value) {
            $this->assertArrayHasKey($key, $array);
            $this->assertEquals($value, $array[$key]);
        }
    }

    protected function assertRequestHasSessionMessage(
        TestResponse $response,
        string $expectedType,
        string $key = 'snackbar',
        callable $additionalChecks = null
    ): void {
        $response->assertSessionHas($key, function (
            SessionMessage $message
        ) use ($expectedType, $additionalChecks) {
            $this->assertEquals($expectedType, $message->type);

            if ($additionalChecks) {
                $additionalChecks($message);
            }

            return true;
        });
    }
}

