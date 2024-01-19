<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase {
    use LazilyRefreshDatabase;

    protected const LOGIN_THROTTLE_LIMIT = 5;

    public function testLoginScreenCanBeRendered(): void {
        $response = $this->get('/login');

        $response->assertOk();
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
        $availableIn = 43;

        $rateLimiterMock = $this->mockRateLimiter(['availableIn']);
        $rateLimiterMock
            ->shouldReceive('availableIn')
            ->once()
            ->andReturn($availableIn);

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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR,
            key: 'session-message',
            additionalChecks: function (SessionMessage $message) use (
                $availableIn
            ) {
                $this->assertEquals(
                    __('auth.throttle', [
                        'seconds' => $availableIn,
                        'minutes' => ceil($availableIn / 60),
                    ]),
                    $message->message
                );
            }
        );
    }

    public function testRedirectsToHomeRouteIfIsAuthenticated(): void {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
