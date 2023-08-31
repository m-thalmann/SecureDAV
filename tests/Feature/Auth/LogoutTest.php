<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testLogoutWorks(): void {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/logout');

        $this->assertGuest();

        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
