<?php

namespace Tests\Feature\WebDav;

use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Models\WebDavUser;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WebDavUserFileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testCreateWebDavUserFileViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testCreateWebDavUserFileViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $files = File::factory(3)
            ->for($this->user)
            ->create(['directory_id' => null]);

        $directories = Directory::factory(2)
            ->for($this->user)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create"
        );

        $response->assertOk();

        $response->assertSee($webDavUser->label);

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }
    }

    public function testCreateWebDavUserFileViewCanBeRenderedWithDirectory(): void {
        $this->passwordConfirmed();

        $otherFiles = File::factory(3)
            ->for($this->user)
            ->create(['directory_id' => null]);

        $otherDirectories = Directory::factory(2)
            ->for($this->user)
            ->create();

        $directory = Directory::factory()
            ->for($this->user)
            ->create();

        $files = File::factory(3)
            ->for($this->user)
            ->for($directory)
            ->create();

        $directories = Directory::factory(2)
            ->for($this->user)
            ->for($directory);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create?directory={$directory->uuid}"
        );

        $response->assertOk();

        $response->assertSee($webDavUser->label);

        $response->assertSee($directory->name);

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }

        foreach ($otherDirectories as $directory) {
            $response->assertDontSee($directory->name);
        }
    }

    public function testCreateWebDavUserFileViewOnlyShowsFilesNotYetAccessibleByTheWebDavUser(): void {
        $this->passwordConfirmed();

        $nonUserFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $userFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->hasAttached($userFile)
            ->create();

        $response = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create"
        );

        $response->assertOk();

        $response->assertSee($nonUserFile->name);
        $response->assertDontSee($userFile->name);
    }

    public function testCreateWebDavUserFileViewDoesNotShowDirectoriesAndFilesOfOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $otherFiles = File::factory(3)
            ->for($otherUser)
            ->create(['directory_id' => null]);

        $otherDirectories = Directory::factory(2)
            ->for($otherUser)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create"
        );

        $response->assertOk();

        $response->assertSee($webDavUser->label);

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }

        foreach ($otherDirectories as $directory) {
            $response->assertDontSee($directory->name);
        }
    }

    public function testCreateWebDavUserFileViewCantBeRenderedWithDirectoryOfOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create?directory={$directory->uuid}"
        );

        $response->assertNotFound();
    }

    public function testCreateWebDavUserFileViewCantBeRenderedWithDirectoryIfDirectoryDoesntExist(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->get(
            "/web-dav-users/{$webDavUser->username}/files/create?directory=non-existent"
        );

        $response->assertNotFound();
    }

    public function testCreateWebDavUserFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $confirmResponse = $this->post(
            "/web-dav-users/{$webDavUser->username}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testWebDavUserFileCanBeCreated(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->post(
            "/web-dav-users/{$webDavUser->username}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );

        $response->assertRedirect(
            "/web-dav-users/{$webDavUser->username}#files"
        );

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
            'file_id' => $file->id,
        ]);
    }

    public function testWebDavUserFileCantBeCreatedIfUserCantUpdateWebDavUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($otherUser)
            ->create();

        $response = $this->post(
            "/web-dav-users/{$webDavUser->username}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );

        $response->assertNotFound();

        $this->assertDatabaseMissing('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
            'file_id' => $file->id,
        ]);
    }

    public function testWebDavUserFileCantBeCreatedIfUserCantUpdateFile(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->post(
            "/web-dav-users/{$webDavUser->username}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );

        $response->assertNotFound();

        $this->assertDatabaseMissing('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
            'file_id' => $file->id,
        ]);
    }

    public function testWebDavUserFileCantBeCreatedIfFileDoesNotExist(): void {
        $this->passwordConfirmed();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->create();

        $response = $this->from(
            "/web-dav-users/{$webDavUser->username}/files/create"
        )->post("/web-dav-users/{$webDavUser->username}/files", [
            'file_uuid' => 'non-existent',
        ]);

        $response->assertRedirect(
            "/web-dav-users/{$webDavUser->username}/files/create"
        );

        $response->assertSessionHasErrors('file_uuid');

        $this->assertDatabaseMissing('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
        ]);
    }

    public function testWebDavUserFileWillNotBeCreatedIfAlreadyPresent(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $webDavUser = WebDavUser::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create();

        $response = $this->post(
            "/web-dav-users/{$webDavUser->username}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );

        $response->assertRedirect(
            "/web-dav-users/{$webDavUser->username}#files"
        );

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_INFO
        );

        $this->assertDatabaseHas('web_dav_user_files', [
            'web_dav_user_id' => $webDavUser->id,
            'file_id' => $file->id,
        ]);
    }
}
