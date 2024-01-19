<?php

namespace Tests\Feature\Backups;

use App\Models\BackupConfiguration;
use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class BackupConfigurationFileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testCreateBackupConfigurationFileViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->get(
            "/backups/{$configuration->uuid}/files/create"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testCreateBackupConfigurationFileViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $files = File::factory(3)
            ->for($this->user)
            ->create(['directory_id' => null]);

        $directories = Directory::factory(2)
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}/files/create");

        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach ($files as $file) {
            $response->assertSee($file->name);
        }

        foreach ($directories as $directory) {
            $response->assertSee($directory->name);
        }
    }

    public function testCreateBackupConfigurationFileViewCanBeRenderedWithDirectory(): void {
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

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get(
            "/backups/{$configuration->uuid}/files/create?directory={$directory->uuid}"
        );

        $response->assertOk();

        $response->assertSee($configuration->label);

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

    public function testCreateBackupConfigurationFileViewOnlyShowsFilesNotYetBackedUpByTheConfiguration(): void {
        $this->passwordConfirmed();

        $nonUsedFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $usedFile = File::factory()
            ->for($this->user)
            ->create(['directory_id' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached($usedFile)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}/files/create");

        $response->assertOk();

        $response->assertSee($nonUsedFile->name);
        $response->assertDontSee($usedFile->name);
    }

    public function testCreateBackupConfigurationFileViewDoesNotShowDirectoriesAndFilesOfOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $otherFiles = File::factory(3)
            ->for($otherUser)
            ->create(['directory_id' => null]);

        $otherDirectories = Directory::factory(2)
            ->for($otherUser)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}/files/create");

        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }

        foreach ($otherDirectories as $directory) {
            $response->assertDontSee($directory->name);
        }
    }

    public function testCreateBackupConfigurationFileViewCantBeRenderedWithDirectoryOfOtherUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get(
            "/backups/{$configuration->uuid}/files/create?directory={$directory->uuid}"
        );

        $response->assertNotFound();
    }

    public function testCreateBackupConfigurationFileViewCantBeRenderedWithDirectoryIfDirectoryDoesntExist(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get(
            "/backups/{$configuration->uuid}/files/create?directory=non-existent"
        );

        $response->assertNotFound();
    }

    public function testCreateBackupConfigurationFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->post(
            "/backups/{$configuration->uuid}/files",
            [
                'file_uuid' => $file->uuid,
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testBackupConfigurationFileCanBeCreated(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->post("/backups/{$configuration->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertRedirect("/backups/{$configuration->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
            'file_id' => $file->id,
        ]);
    }

    public function testBackupConfigurationFileCantBeCreatedIfUserCantUpdateBackupConfiguration(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($otherUser)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->post("/backups/{$configuration->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
            'file_id' => $file->id,
        ]);
    }

    public function testBackupConfigurationFileCantBeCreatedIfUserCantUpdateFile(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->post("/backups/{$configuration->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
            'file_id' => $file->id,
        ]);
    }

    public function testBackupConfigurationFileCantBeCreatedIfFileDoesNotExist(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/files",
            [
                'file_uuid' => 'non-existent',
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $response->assertSessionHasErrors('file_uuid');

        $this->assertDatabaseMissing('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
        ]);
    }

    public function testBackupConfigurationFileWillNotBeCreatedIfAlreadyPresent(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->post("/backups/{$configuration->uuid}/files", [
            'file_uuid' => $file->uuid,
        ]);

        $response->assertRedirect("/backups/{$configuration->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_INFO
        );

        $this->assertDatabaseHas('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
            'file_id' => $file->id,
        ]);
    }

    public function testDeleteBackupConfigurationFileConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $file = File::factory()
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/backups/{$configuration->uuid}/files/{$file->uuid}"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testBackupConfigurationFileCanBeDeleted(): void {
        $this->passwordConfirmed();

        $file = File::factory()
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached($file)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/backups/{$configuration->uuid}/files/{$file->uuid}"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
            'file_id' => $file->id,
        ]);
    }

    public function testBackupConfigurationFileCantBeDeletedIfUserCantUpdateWebDavUser(): void {
        $this->passwordConfirmed();

        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($otherUser)
            ->hasAttached($file)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->delete(
            "/backups/{$configuration->uuid}/files/{$file->uuid}"
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
            'file_id' => $file->id,
        ]);
    }
}
