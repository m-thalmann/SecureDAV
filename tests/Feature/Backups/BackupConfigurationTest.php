<?php

namespace Tests\Feature\Backups;

use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Support\SessionMessage;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    protected function tearDown(): void {
        parent::tearDown();

        StubBackupProvider::$customConfigValidator = null;
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

    public function testIndexBackupsViewShowsOutdatedBackups(): void {
        $configurations = BackupConfiguration::factory(2)
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $outdatedFile = File::factory()
            ->for($this->user)
            ->has(FileVersion::factory(), 'versions')
            ->hasAttached($configurations->get(0))
            ->create();

        $fileWithNoVersion = File::factory()
            ->for($this->user)
            ->hasAttached($configurations->get(1))
            ->create();

        $response = $this->get('/backups');

        $response->assertOk();

        $response->assertSee('Outdated'); // outdated file
        $response->assertSee('Up to date'); // no version file
    }

    public function testIndexBackupsViewShowsRunningState(): void {
        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
                'started_at' => now(),
            ]);

        $response = $this->get('/backups');

        $response->assertOk();

        $response->assertSee('Running');
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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );
    }

    public function testCreateViewFailsIfProviderDoesNotExist(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->get(
            '/backups/create?provider=does-not-exist'
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
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

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'label' => $label,
            'provider_class' => StubBackupProvider::class,
            'active' => 1,
            'store_with_version' => 0,
        ]);
    }

    public function testNewConfigurationCanBeCreatedWithStoreWithVersion(): void {
        $label = 'Test label';

        $response = $this->post('/backups', [
            'label' => $label,
            'provider' => StubBackupProvider::class,
            'store_with_version' => 'true',
        ]);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'label' => $label,
            'provider_class' => StubBackupProvider::class,
            'active' => 1,
            'store_with_version' => 1,
        ]);
    }

    public function testNewConfigurationStoresConfigEncrypted(): void {
        $label = 'Test label';

        StubBackupProvider::$customConfigValidator = [
            'test' => ['required', 'string'],
        ];

        $config = ['test' => 'test'];

        $response = $this->post('/backups', [
            'label' => $label,
            'provider' => StubBackupProvider::class,
            ...$config,
        ]);

        $createdConfiguration = BackupConfiguration::query()
            ->where('label', $label)
            ->firstOrFail();

        $this->assertEquals($config, $createdConfiguration->config);

        $rawConfiguration = DB::table($createdConfiguration->getTable())
            ->where('id', $createdConfiguration->id)
            ->select('config')
            ->first()->config;

        $this->assertNotEquals($config, $rawConfiguration);
    }

    public function testCreateConfigurationFailsIfProviderDoesNotExist(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post('/backups', [
            'label' => 'Test label',
            'provider' => 'does-not-exist',
        ]);

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
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

    public function testShowConfigurationViewShowsInformationAboutSchedule(): void {
        $cronExpression = new CronExpression('0 * * * *');

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
                'cron_schedule' => $cronExpression->getExpression(),
            ]);

        $response = $this->get("/backups/{$configuration->uuid}");

        $response->assertOk();

        foreach ($cronExpression->getMultipleRunDates(4) as $runDate) {
            $response->assertSee(Carbon::createFromInterface($runDate));
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
            'active' => null,
        ]);

        $response->assertRedirect("/backups/{$configuration->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'id' => $configuration->id,
            'label' => $label,
            'active' => 0,
        ]);
    }

    public function testConfigurationCanBeUpdatedWithoutConfig(): void {
        $this->passwordConfirmed();

        $config = ['test' => 'test'];

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
                'config' => $config,
            ]);

        $label = 'Test label';

        $response = $this->put("/backups/{$configuration->uuid}", [
            'label' => $label,
            'edit-config' => 'false',
            'active' => 'true',
        ]);

        $response->assertRedirect("/backups/{$configuration->uuid}");

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'id' => $configuration->id,
            'label' => $label,
            'active' => 1,
        ]);

        $configuration->refresh();

        $this->assertEquals($config, $configuration->config);
    }

    public function testUpdateConfigurationStoresConfigEncrypted(): void {
        $this->passwordConfirmed();

        StubBackupProvider::$customConfigValidator = [
            'test' => ['required', 'string'],
        ];

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $label = 'Test label';

        $config = ['test' => 'test'];

        $response = $this->put("/backups/{$configuration->uuid}", [
            'label' => $label,
            ...$config,
        ]);

        $configuration->refresh();

        $this->assertEquals($config, $configuration->config);

        $rawConfiguration = DB::table($configuration->getTable())
            ->where('id', $configuration->id)
            ->select('config')
            ->first()->config;

        $this->assertNotEquals($config, $rawConfiguration);
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

        $this->assertResponseHasSessionMessage(
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
