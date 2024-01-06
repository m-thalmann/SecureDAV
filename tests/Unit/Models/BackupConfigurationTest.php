<?php

namespace Tests\Unit\Models;

use App\Models\BackupConfiguration;
use App\Support\BackupSchedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class BackupConfigurationTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testScheduleAttributeReturnsBackupScheduleInstance(): void {
        $configuration = BackupConfiguration::make([
            'cron_schedule' => BackupSchedule::DAILY,
        ]);

        /**
         * @var BackupSchedule
         */
        $schedule = $configuration->schedule;

        $this->assertInstanceOf(BackupSchedule::class, $schedule);

        $this->assertSame(BackupSchedule::DAILY, $schedule->getValue());
    }

    public function testScheduleAttributeReturnsNullIfCronScheduleIsNull(): void {
        $configuration = BackupConfiguration::make([
            'cron_schedule' => null,
        ]);

        $schedule = $configuration->schedule;

        $this->assertNull($schedule);
    }

    public function testMaskedConfigAttributeReturnsConfigWithSensitiveKeysMasked(): void {
        $sensitiveKey = 'sensitive_key';

        StubBackupProvider::$customSensitiveConfigKeys = [$sensitiveKey];

        $config = [
            'key1' => 'value1',
            'key2' => 'value2',
            $sensitiveKey => 'sensitive_value',
        ];

        $configuration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
            'config' => $config,
        ]);

        $maskedConfig = $configuration->maskedConfig;

        foreach ($config as $key => $value) {
            if ($key === $sensitiveKey) {
                $this->assertSame('********', $maskedConfig[$key]);
            } else {
                $this->assertSame($value, $maskedConfig[$key]);
            }
        }

        StubBackupProvider::$customSensitiveConfigKeys = [];
    }

    public function testBuildProviderReturnsProviderInstance(): void {
        /**
         * @var BackupConfiguration
         */
        $configuration = BackupConfiguration::make()->forceFill([
            'provider_class' => StubBackupProvider::class,
        ]);

        $provider = $configuration->buildProvider();

        $this->assertInstanceOf(StubBackupProvider::class, $provider);
    }
}
