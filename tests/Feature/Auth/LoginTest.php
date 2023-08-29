<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\LoginController;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testLoginScreenCanBeRendered(): void {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function testUserCanAuthenticateUsingTheLoginScreen(): void {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function testUserCantAuthenticateWithInvalidPassword(): void {
        $response = $this->post('/login', [
            'email' => 'wrong-email@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertInvalid();
    }

    public function testRateLimitsAfterCertainAmountOfBadRequests(): void {
        for ($i = 0; $i < LoginController::MAX_ATTEMPTS; $i++) {
            $response = $this->post('/login', [
                'email' => 'wrong-email@example.com',
                'password' => 'wrong-password',
            ]);

            $response->assertInvalid(['form' => __('auth.failed')]);
        }

        $response = $this->post('/login', [
            'email' => 'wrong-email@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertInvalid([
            'form' => __('auth.throttle', [
                'seconds' => 59,
                'minutes' => 0,
            ]),
        ]);
    }

    public function testRedirectsToHomeRouteIfIsAuthenticated(): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
