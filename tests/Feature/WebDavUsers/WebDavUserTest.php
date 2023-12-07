<?php

namespace Tests\Feature\WebDavUsers;

use App\Models\File;
use App\Models\User;
use App\Models\WebDavUser;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WebDavUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexWebDavUsersViewCanBeRendered(): void {
        $fileCount = 13;

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get('/web-dav-users');

        $response->assertOk();

        $response->assertSee($webDavUser->label);
        $response->assertSee($fileCount);
    }

    public function testIndexWebDavUsersViewDoesOnlyShowItemsOfTheAuthenticatedUser(): void {
        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $otherUser = $this->createUser();

        $otherWebDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get('/web-dav-users');

        $response->assertOk();

        $response->assertSee($webDavUser->label);
        $response->assertDontSee($otherWebDavUser->label);
    }

    public function testShowWebDavUserViewCanBeRendered(): void {
        $fileCount = 10;

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->has(File::factory($fileCount)->for($this->user))
            ->create();

        $response = $this->get("/web-dav-users/{$webDavUser->username}");

        $response->assertOk();

        $response->assertSee($webDavUser->label);
        $response->assertSee("({$fileCount})");
    }

    public function testShowWebDavUserViewFailsIfWebDavUserDoesNotBelongToUser(): void {
        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->get("/web-dav-users/{$webDavUser->username}");

        $response->assertNotFound();
    }

    public function testDeleteWebDavUserConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->delete(
            "/web-dav-users/{$webDavUser->username}"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testWebDavUserCanBeDeleted(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/web-dav-users/{$webDavUser->username}");

        $response->assertRedirect('/web-dav-users');

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('web_dav_users', [
            'id' => $webDavUser->id,
        ]);
        $this->assertDatabaseMissing('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
        ]);
    }

    public function testWebDavUserCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->has(File::factory(3)->for($this->user))
            ->create();

        $response = $this->delete("/web-dav-users/{$webDavUser->username}");

        $response->assertNotFound();

        $this->assertDatabaseHas('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
        ]);
    }
}
