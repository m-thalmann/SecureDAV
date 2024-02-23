<?php

namespace Tests\Unit\Jobs;

use App\Events\BackupFailed;
use App\Jobs\RunBackup as BaseRunBackup;
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

            $this->assertFalse($event->rateLimited);

            return true;
        });
    }

    public function testBackupFailsIfInactive(): void {
        Event::fake([BackupFailed::class]);

        $this->backupConfiguration->update(['active' => false]);

        RunBackup::dispatchSync($this->backupConfiguration);

        Event::assertDispatched(BackupFailed::class, function (
            BackupFailed $event
        ) {
            $this->assertEquals(
                $this->backupConfiguration->id,
                $event->backupConfiguration->id
            );

            $this->assertFalse($event->rateLimited);

            return true;
        });
    }

    public function testBackupIsRateLimited(): void {
        for ($i = 0; $i < RunBackup::RATE_LIMITER_ATTEMPTS; $i++) {
            $this->assertFalse(
                RunBackup::isRateLimited($this->backupConfiguration)
            );

            RunBackup::dispatchSync($this->backupConfiguration);
        }

        $this->assertTrue(RunBackup::isRateLimited($this->backupConfiguration));
    }

    public function testGetRateLimitedAvailableInReturnsCorrectValue(): void {
        for ($i = 0; $i < RunBackup::RATE_LIMITER_ATTEMPTS; $i++) {
            $this->assertEquals(
                0,
                RunBackup::getRateLimitedAvailableIn($this->backupConfiguration)
            );

            RunBackup::dispatchSync($this->backupConfiguration);
        }

        $this->assertGreaterThan(
            0,
            RunBackup::getRateLimitedAvailableIn($this->backupConfiguration)
        );
    }

    public function testEventIsDispatchedWhenRateLimited(): void {
        Event::fake([BackupFailed::class]);

        for ($i = 0; $i < RunBackup::RATE_LIMITER_ATTEMPTS + 1; $i++) {
            RunBackup::dispatchSync($this->backupConfiguration);
        }

        Event::assertDispatched(BackupFailed::class, function (
            BackupFailed $event
        ) {
            $this->assertEquals(
                $this->backupConfiguration->id,
                $event->backupConfiguration->id
            );

            $this->assertTrue($event->rateLimited);

            return true;
        });
    }
}

class RunBackup extends BaseRunBackup {
    public static function getRateLimiterKey(
        BackupConfiguration $backupConfiguration
    ): string {
        return parent::getRateLimiterKey($backupConfiguration);
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

        return parent::backup();
    }
}
