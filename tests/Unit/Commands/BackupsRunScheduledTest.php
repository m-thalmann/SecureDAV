<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunBackup;
use App\Models\BackupConfiguration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class BackupsRunScheduledTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testItDispatchesBackupJobsThatAreDue(): void {
        Queue::fake();

        $nextHour = now()->addHour()->hour;

        $dueBackup = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
            'cron_schedule' => '* * * * *',
        ]);

        $notDueBackup = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
            'cron_schedule' => "* {$nextHour} * * *",
        ]);

        $this->artisan('backups:run-scheduled')->assertExitCode(0);

        Queue::assertPushed(function (RunBackup $job) use ($dueBackup) {
            $this->assertEquals($dueBackup->id, $job->backupConfiguration->id);

            return true;
        });

        Queue::assertNotPushed(function (RunBackup $job) use ($notDueBackup) {
            return $notDueBackup->id === $job->backupConfiguration->id;
        });
    }

    public function testItDoesNotDispatchBackupJobsThatHaveNoSchedule(): void {
        Queue::fake();

        $backup = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $this->artisan('backups:run-scheduled')->assertExitCode(0);

        Queue::assertNotPushed(RunBackup::class);
    }
}
