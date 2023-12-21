<?php

namespace Tests\Feature;

use App\Backups\WebDavBackupProvider;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\User;
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
}
