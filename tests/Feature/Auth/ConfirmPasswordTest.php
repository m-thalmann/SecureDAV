<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ConfirmPasswordTest extends TestCase {
    use LazilyRefreshDatabase;

    protected string $route = '/test-route';

    protected function setUp(): void {
        parent::setUp();

        Route::get($this->route, fn() => 'OK')->middleware(
            'web',
            'auth',
            'password.confirm'
        );
    }

    public function testConfirmPasswordScreenCanBeRendered(): void {
        $this->actingAs($this->createUser());

        $response = $this->get($this->route);

        $response->assertRedirect('/user/confirm-password');
    }

    public function testPasswordCanBeConfirmed(): void {
        $this->actingAs($this->createUser());

        $this->get($this->route);

        $response = $this->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertRedirect($this->route);
    }

    public function testPasswordCantBeConfirmedWithWrongPassword(): void {
        $this->actingAs($this->createUser());

        $this->get($this->route);

        $response = $this->post('/user/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
