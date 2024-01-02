<?php

namespace Tests\Feature\Backups;

use App\Models\BackupConfiguration;
use App\Models\User;
use App\Support\BackupSchedule;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class BackupConfigurationScheduleTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testEditBackupConfigurationScheduleViewConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->get(
            "/backups/{$configuration->uuid}/schedule"
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testEditBackupConfigurationScheduleViewCanBeRendered(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->get("/backups/{$configuration->uuid}/schedule");

        $response->assertOk();

        $response->assertSee($configuration->label);

        foreach (BackupSchedule::AVAILABLE_SCHEDULES as $schedule) {
            $response->assertSee($schedule);
        }

        $response->assertDontSee('Custom');
    }

    public function testEditBackupConfigurationScheduleViewShowsInformationAboutCustomSchedule(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
                'cron_schedule' => '* * * * *',
            ]);

        $response = $this->get("/backups/{$configuration->uuid}/schedule");

        $response->assertOk();

        $response->assertSee('Custom');
    }

    public function testEditBackupConfigurationScheduleViewCantBeRenderedForOtherUser(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->get("/backups/{$configuration->uuid}/schedule");

        $response->assertNotFound();
    }

    public function testUpdateBackupConfigurationScheduleConfirmsPassword(): void {
        $this->session(['auth.password_confirmed_at' => null]);

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $confirmResponse = $this->put(
            "/backups/{$configuration->uuid}/schedule",
            [
                'schedule' => BackupSchedule::DAILY,
            ]
        );
        $confirmResponse->assertRedirectToRoute('password.confirm');
    }

    public function testBackupConfigurationScheduleCanBeUpdated(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->put("/backups/{$configuration->uuid}/schedule", [
            'schedule' => BackupSchedule::DAILY,
        ]);

        $response->assertRedirect("/backups/{$configuration->uuid}");

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertDatabaseHas('backup_configurations', [
            'uuid' => $configuration->uuid,
            'cron_schedule' => BackupSchedule::DAILY,
        ]);
    }

    public function testBackupConfigurationScheduleCantBeUpdatedWithNotAvailableSchedule(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/backups/{$configuration->uuid}/schedule",
            [
                'schedule' => '* * * * *',
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $response->assertSessionHasErrors('schedule');

        $this->assertDatabaseHas('backup_configurations', [
            'uuid' => $configuration->uuid,
            'cron_schedule' => null,
        ]);
    }

    public function testBackupConfigurationScheduleCantBeUpdatedForOtherUser(): void {
        $this->passwordConfirmed();

        $configuration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/backups/{$configuration->uuid}/schedule",
            [
                'schedule' => BackupSchedule::DAILY,
            ]
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('backup_configurations', [
            'uuid' => $configuration->uuid,
            'cron_schedule' => null,
        ]);
    }
}
