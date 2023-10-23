<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
}

