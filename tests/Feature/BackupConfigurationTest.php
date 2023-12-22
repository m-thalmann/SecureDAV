<?php

namespace Tests\Feature;

use App\Backups\WebDavBackupProvider;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BackupConfigurationTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexBackupsViewCanBeRendered() {
        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => WebDavBackupProvider::class,
            ]);

        $response = $this->get('/backups');

        $response->assertOk();
        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach (config('backups.providers') as $provider => $options) {
            $response->assertSee($provider::getDisplayInformation()['name']);
        }
    }

    public function testIndexBackupsViewOnlyShowsConfiguredBackupsOfUser() {
        $otherConfigurations = BackupConfiguration::factory(3)->create([
            'provider_class' => WebDavBackupProvider::class,
        ]);

        $response = $this->get('/backups');

        $response->assertOk();

        foreach ($otherConfigurations as $configuration) {
            $response->assertDontSee($configuration->label);
        }
    }

    public function testShowConfigurationViewCanBeRendered() {
        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached(File::factory(3)->for($this->user))
            ->create([
                'provider_class' => WebDavBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}");

        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach ($configuration->files as $file) {
            $response->assertSee($file->name);
        }
    }

    public function testShowConfigurationViewOnlyShowsFilesOfConfiguration() {
        $otherFiles = File::factory(3)
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => WebDavBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}");

        $response->assertOk();
        $response->assertOk();

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }
    }

    public function testShowConfigurationViewFailsIfConfigurationDoesNotBelongToUser() {
        $otherConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => WebDavBackupProvider::class,
        ]);

        $response = $this->get("/backups/{$otherConfiguration->uuid}");

        $response->assertNotFound();
    }

    public function testDeleteConfigurationConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => WebDavBackupProvider::class,
            ]);

        $confirmResponse = $this->delete("/backups/{$configuration->uuid}");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testConfigurationCanBeDeleted(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->has(File::factory(3)->for($this->user))
            ->create([
                'provider_class' => WebDavBackupProvider::class,
            ]);

        $response = $this->delete("/backups/{$configuration->uuid}");

        $response->assertRedirect('/backups');

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseMissing('backup_configurations', [
            'id' => $configuration->id,
        ]);
        $this->assertDatabaseMissing('backup_configuration_files', [
            'backup_configuration_id' => $configuration->id,
        ]);
    }

    public function testConfigurationCantBeDeletedForOtherUser(): void {
        $otherUser = $this->createUser();

        $configuration = BackupConfiguration::factory()
            ->for($otherUser)
            ->create([
                'provider_class' => WebDavBackupProvider::class,
            ]);

        $response = $this->delete("/backups/{$configuration->uuid}");

        $response->assertNotFound();

        $this->assertDatabaseHas('backup_configurations', [
            'id' => $configuration->id,
        ]);
    }
}
