<?php

namespace Tests\Unit\Jobs;

use App\Events\BackupFailed;
use App\Jobs\RunBackup;
use App\Models\BackupConfiguration;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class RunBackupTest extends TestCase {
    use LazilyRefreshDatabase;

    protected BackupConfiguration $backupConfiguration;

    protected function setUp(): void {
        parent::setUp();

        TestBackupProvider::$backupWasRun = false;
        TestBackupProvider::$shouldThrowException = false;

        $this->backupConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => TestBackupProvider::class,
        ]);
    }

    public function testBackupIsRunOnExecuteOfJob(): void {
        RunBackup::dispatchSync($this->backupConfiguration);

        $this->assertTrue(TestBackupProvider::$backupWasRun);
    }

    public function testEventIsDispatchedOnFailure(): void {
        Event::fake([BackupFailed::class]);

        TestBackupProvider::$shouldThrowException = true;

        RunBackup::dispatchSync($this->backupConfiguration);

        Event::assertDispatched(BackupFailed::class, function (
            BackupFailed $event
        ) {
            $this->assertEquals(
                $this->backupConfiguration->id,
                $event->backupConfiguration->id
            );

            return true;
        });
    }
}

class TestBackupProvider extends StubBackupProvider {
    public static bool $backupWasRun = false;

    public static bool $shouldThrowException = false;

    public function backup(): bool {
        if (static::$shouldThrowException) {
            throw new Exception('Test exception');
        }

        static::$backupWasRun = true;

        return true;
    }
}
