<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->forceFill(['is_admin' => true])->save();

        $this->actingAs($this->user);
    }

    public function testIndexAdminUsersViewConfirmsPassword(): void {
        $response = $this->get('/admin/users');

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testIndexAdminUsersViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $users = User::factory()
            ->count(5)
            ->create();

        $response = $this->get('/admin/users');

        $response->assertOk();

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }

        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    public function testIndexAdminUsersViewCanBeSearched(): void {
        $this->passwordConfirmed();

        $users = User::factory()
            ->count(2)
            ->create();

        $response = $this->get('/admin/users?q=' . $users[0]->name);

        $response->assertOk();
        $response->assertSee($users[0]->name);
        $response->assertDontSee($users[1]->name);
    }

    public function testIndexAdminUsersViewCantBeRenderedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $response = $this->get('/admin/users');

        $response->assertForbidden();
    }
}
