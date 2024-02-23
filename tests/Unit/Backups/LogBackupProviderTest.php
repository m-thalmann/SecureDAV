<?php

namespace Tests\Unit\Backups;

use App\Backups\LogBackupProvider;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LogBackupProviderTest extends TestCase {
    use LazilyRefreshDatabase;

    protected BackupConfiguration $backupConfiguration;
    protected FileVersionService|MockInterface $mockFileVersionService;

    protected LogBackupProviderTestClass|MockInterface $logBackupProvider;

    protected function setUp(): void {
        parent::setUp();

        $this->backupConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => LogBackupProvider::class,
            'config' => [],
        ]);
        $this->mockFileVersionService = Mockery::mock(
            FileVersionService::class
        );

        $this->logBackupProvider = Mockery::mock(
            LogBackupProviderTestClass::class,
            [$this->backupConfiguration, $this->mockFileVersionService]
        )
            ->makePartial(['getFileContentStream'])
            ->shouldAllowMockingProtectedMethods();
    }

    public function testGetDisplayInformationReturnsArrayWithProviderInformation(): void {
        $displayInformation = LogBackupProvider::getDisplayInformation();

        $this->assertIsArray($displayInformation);
        $this->assertArrayHasKey('name', $displayInformation);
        $this->assertArrayHasKey('icon', $displayInformation);
        $this->assertArrayHasKey('description', $displayInformation);
    }

    public function testGetConfigFormTemplateReturnsNull(): void {
        $this->assertNull(LogBackupProvider::getConfigFormTemplate());
    }

    public function testValidateConfigReturnsEmptyArray(): void {
        $this->assertSame(
            [],
            LogBackupProvider::validateConfig(['test' => '123'])
        );
    }

    public function testGetSensitiveConfigKeysReturnsEmptyArray(): void {
        $this->assertSame([], LogBackupProvider::getSensitiveConfigKeys());
    }

    public function testBackupFileLogsInformation(): void {
        $file = File::factory()
            ->has(FileVersion::factory(), 'versions')
            ->create();
        $targetName = 'test';

        $logMock = Log::partialMock();

        $logMock
            ->shouldReceive('info')
            ->once()
            ->withArgs(function (string $message) use ($file, $targetName) {
                $this->assertStringContainsString(
                    "Backup \"{$this->backupConfiguration->label}\" ({$this->backupConfiguration->id})",
                    $message
                );
                $this->assertStringContainsString(
                    "file \"{$file->name}\" ($file->id)",
                    $message
                );
                $this->assertStringContainsString(
                    "version {$file->latestVersion->version}",
                    $message
                );
                $this->assertStringContainsString(
                    "target \"{$targetName}\"",
                    $message
                );

                return true;
            });

        $this->mockFileVersionService->shouldNotHaveBeenCalled();

        $this->logBackupProvider->backupFile($file, $targetName);
    }
}

class LogBackupProviderTestClass extends LogBackupProvider {
    public function backupFile(File $file, string $targetName): void {
        parent::backupFile($file, $targetName);
    }
}
