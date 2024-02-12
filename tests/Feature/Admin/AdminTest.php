<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->forceFill(['is_admin' => true])->save();

        $this->actingAs($this->user);
    }

    public function testIndexAdminViewConfirmsPassword(): void {
        $response = $this->get('/admin');

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testIndexAdminViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertViewHas([
            'amountUsers',
            'amountFiles',
            'amountVersions',
            'amountWebDavUsers',
            'amountConfiguredBackups',
        ]);
    }

    public function testIndexAdminViewCantBeRenderedForNonAdmins(): void {
        $this->user->forceFill(['is_admin' => false])->save();

        $this->passwordConfirmed();

        $response = $this->get('/admin');

        $response->assertForbidden();
    }
}
