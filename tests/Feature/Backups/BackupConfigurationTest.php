<?php

namespace Tests\Feature\Backups;

use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class BackupConfigurationTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexBackupsViewCanBeRendered(): void {
        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get('/backups');

        $response->assertOk();
        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach (config('backups.providers') as $provider => $options) {
            $response->assertSee($provider::getDisplayInformation()['name']);
        }
    }

    public function testIndexBackupsViewOnlyShowsConfiguredBackupsOfUser(): void {
        $otherConfigurations = BackupConfiguration::factory(3)->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->get('/backups');

        $response->assertOk();

        foreach ($otherConfigurations as $configuration) {
            $response->assertDontSee($configuration->label);
        }
    }

    public function testCreateViewCanBeRenderedWithProvider(): void {
        $response = $this->get(
            '/backups/create?provider=' . StubBackupProvider::class
        );

        $response->assertOk();

        $response->assertSee(
            StubBackupProvider::getDisplayInformation()['name']
        );
        $response->assertSee(
            StubBackupProvider::getDisplayInformation()['description']
        );
    }

    public function testCreateViewCanBeRenderedWithAliasProvider(): void {
        $alias = 'stub';

        config(["backups.aliases.$alias" => StubBackupProvider::class]);

        $response = $this->get("/backups/create?provider=$alias");

        $response->assertOk();
    }

    public function testCreateViewFailsIfProviderIsNotSet(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->get(
            '/backups/create'
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );
    }

    public function testCreateViewFailsIfProviderDoesNotExist(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->get(
            '/backups/create?provider=does-not-exist'
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );
    }

    public function testNewConfigurationCanBeCreated(): void {
        StubBackupProvider::$customConfigValidator = [
            'test' => ['required', 'string'],
        ];

        $label = 'Test label';

        $response = $this->post('/backups', [
            'label' => $label,
            'provider' => StubBackupProvider::class,
            'test' => 'test',
        ]);

        $createdConfiguration = BackupConfiguration::query()
            ->where('label', $label)
            ->firstOrFail();

        $response->assertRedirect("/backups/{$createdConfiguration->uuid}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'label' => $label,
            'provider_class' => StubBackupProvider::class,
            'config' => json_encode([
                'test' => 'test',
            ]),
        ]);

        StubBackupProvider::$customConfigValidator = null;
    }

    public function testCreateConfigurationFailsIfProviderDoesNotExist(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post('/backups', [
            'label' => 'Test label',
            'provider' => 'does-not-exist',
        ]);

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );
    }

    public function testShowConfigurationViewCanBeRendered(): void {
        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached(File::factory(3)->for($this->user))
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}");

        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach ($configuration->files as $file) {
            $response->assertSee($file->name);
        }
    }

    public function testShowConfigurationViewOnlyShowsFilesOfConfiguration(): void {
        $otherFiles = File::factory(3)
            ->for($this->user)
            ->create();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}");

        $response->assertOk();
        $response->assertOk();

        foreach ($otherFiles as $file) {
            $response->assertDontSee($file->name);
        }
    }

    public function testShowConfigurationViewFailsIfConfigurationDoesNotBelongToUser(): void {
        $otherConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->get("/backups/{$otherConfiguration->uuid}");

        $response->assertNotFound();
    }

    public function testEditConfigurationViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->get("/backups/{$configuration->uuid}/edit");
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testEditConfigurationViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}/edit");

        $response->assertOk();

        $response->assertSee(
            StubBackupProvider::getDisplayInformation()['name']
        );

        $response->assertSee($configuration->label);
    }

    public function testEditConfigurationViewFailsIfConfigurationDoesNotBelongToUser(): void {
        $otherConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->get("/backups/{$otherConfiguration->uuid}/edit");

        $response->assertNotFound();
    }

    public function testUpdateConfigurationConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->put("/backups/{$configuration->uuid}", [
            'label' => 'Test label',
        ]);
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testConfigurationCanBeUpdated(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $label = 'Test label';

        $response = $this->put("/backups/{$configuration->uuid}", [
            'label' => $label,
        ]);

        $response->assertRedirect("/backups/{$configuration->uuid}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'id' => $configuration->id,
            'label' => $label,
        ]);
    }

    public function testConfigurationCantBeUpdatedForOtherUser(): void {
        $configuration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->put("/backups/{$configuration->uuid}", [
            'label' => 'Test label',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('backup_configurations', [
            'id' => $configuration->id,
            'label' => $configuration->label,
        ]);
    }

    public function testDeleteConfigurationConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
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
                'provider_class' => StubBackupProvider::class,
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
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->delete("/backups/{$configuration->uuid}");

        $response->assertNotFound();

        $this->assertDatabaseHas('backup_configurations', [
            'id' => $configuration->id,
        ]);
    }
}
