<?php

namespace Tests\Feature;

use App\Backups\WebDavBackupProvider;
use App\Models\BackupConfiguration;
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

        $response->assertStatus(200);

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

        $response->assertStatus(200);

        foreach ($otherConfigurations as $configuration) {
            $response->assertDontSee($configuration->label);
        }
    }
}
