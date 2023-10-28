<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;

    protected const REDIRECT_TEST_ROUTE = '/redirect-test-route';

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

    protected function getStreamedResponseContent(
        StreamedResponse $response
    ): string|false {
        ob_start();

        $response->send();

        return ob_get_clean();
    }

    protected function assertHasSubArray(array $subarray, array $array): void {
        foreach ($subarray as $key => $value) {
            $this->assertArrayHasKey($key, $array);
            $this->assertEquals($value, $array[$key]);
        }
    }
}

