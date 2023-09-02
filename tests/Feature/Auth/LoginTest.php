<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\View\Helpers\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase {
    use LazilyRefreshDatabase;

    protected const LOGIN_THROTTLE_LIMIT = 5;

    public function testLoginScreenCanBeRendered(): void {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function testUserCanAuthenticateUsingTheLoginScreen(): void {
        $user = $this->createUser();

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
        $response->assertSessionHasErrors(['email' => __('auth.failed')]);
    }

    public function testRateLimitsAfterCertainAmountOfBadRequests(): void {
        // TODO: enable when https://github.com/laravel/framework/issues/48248 is fixed (and use it below in assert -> 'auth.throttle')
        // RateLimiter::partialMock()
        //     ->shouldReceive('availableIn')
        //     ->once()
        //     ->andReturn(60);

        for ($i = 0; $i < static::LOGIN_THROTTLE_LIMIT; $i++) {
            $response = $this->post('/login', [
                'email' => 'wrong-email@example.com',
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $response = $this->post('/login', [
            'email' => 'wrong-email@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHas('session-message', function (
            SessionMessage $message
        ) {
            $this->assertEquals(SessionMessage::TYPE_ERROR, $message->type);
            $this->assertNotEquals(__('auth.failed'), $message->message);

            return true;
        });
    }

    public function testRedirectsToHomeRouteIfIsAuthenticated(): void {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
