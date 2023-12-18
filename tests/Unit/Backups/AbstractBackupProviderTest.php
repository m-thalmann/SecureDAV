<?php

namespace Tests\Unit\Backups;

use App\Backups\AbstractBackupProvider;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Services\FileVersionService;
use Closure;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AbstractBackupProviderTest extends TestCase {
    use LazilyRefreshDatabase;

    protected BackupConfiguration $backupConfiguration;
    protected FileVersionService|MockInterface $mockFileVersionService;

    protected TestBackupProvider $abstractBackupProvider;

    protected function setUp(): void {
        parent::setUp();

        $this->backupConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => TestBackupProvider::class,
        ]);
        $this->mockFileVersionService = Mockery::mock(
            FileVersionService::class
        );

        $this->abstractBackupProvider = new TestBackupProvider(
            $this->backupConfiguration,
            $this->mockFileVersionService
        );
    }

    public function testBackupCallsBackupFileForAllFilesInTheConfigurationAndReturnsTrueOnSuccess(): void {
        $files = File::factory(5)
            ->for($this->backupConfiguration->user)
            ->hasAttached($this->backupConfiguration)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $otherFiles = File::factory(2)
            ->for($this->backupConfiguration->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $success = $this->abstractBackupProvider->backup();

        $this->assertTrue($success);

        foreach ($files as $file) {
            $this->assertContains(
                $file->id,
                $this->abstractBackupProvider->backedUpFileIds
            );
        }

        foreach ($otherFiles as $file) {
            $this->assertNotContains(
                $file->id,
                $this->abstractBackupProvider->backedUpFileIds
            );
        }
    }

    public function testBackupUpdatesConfigurationFilesPivotTableOnSuccess(): void {
        $file = File::factory()
            ->for($this->backupConfiguration->user)
            ->hasAttached($this->backupConfiguration)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $this->abstractBackupProvider->backup();

        $pivotFile = $this->backupConfiguration
            ->files()
            ->get()
            ->first();

        $this->assertEquals(
            $file->latestVersion->checksum,
            $pivotFile->pivot->last_backup_checksum
        );

        $this->assertEqualsWithDelta(
            now()->getTimestamp(),
            $pivotFile->pivot->last_backup_at->getTimestamp(),
            1
        );
    }

    public function testBackupDoesNotBackupFileWithNoVersion(): void {
        $file = File::factory()
            ->for($this->backupConfiguration->user)
            ->hasAttached($this->backupConfiguration)
            ->create();

        $this->abstractBackupProvider->backup();

        $this->assertEmpty($this->abstractBackupProvider->backedUpFileIds);
    }

    public function testBackupDoesNotBackupFileWithSameChecksumAsPreviousBackup(): void {
        $file = File::factory()
            ->for($this->backupConfiguration->user)
            ->hasAttached($this->backupConfiguration)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $this->backupConfiguration->files()->updateExistingPivot($file->id, [
            'last_backup_checksum' => $file->latestVersion->checksum,
        ]);

        $this->abstractBackupProvider->backup();

        $this->assertEmpty($this->abstractBackupProvider->backedUpFileIds);
    }

    public function testBackupReturnsFalseIfOneFileBackupFailsAndUpdatesPivotTable(): void {
        $files = File::factory(4)
            ->for($this->backupConfiguration->user)
            ->hasAttached($this->backupConfiguration)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $exception = new Exception('Test');

        $this->abstractBackupProvider->backupFileCallback = function (
            File $file
        ) use ($files, $exception) {
            if ($file->id === $files->last()->id) {
                throw $exception;
            }
        };

        $success = $this->abstractBackupProvider->backup();

        $this->assertFalse($success);

        foreach ($files as $file) {
            if ($file->id === $files->last()->id) {
                continue;
            }

            $this->assertContains(
                $file->id,
                $this->abstractBackupProvider->backedUpFileIds
            );

            $this->assertNull(
                $this->backupConfiguration
                    ->files()
                    ->get()
                    ->firstWhere('id', $file->id)->pivot->last_error
            );
        }

        $this->assertNotContains(
            $files->last()->id,
            $this->abstractBackupProvider->backedUpFileIds
        );

        $this->assertEquals(
            $exception->getMessage(),
            $this->backupConfiguration
                ->files()
                ->get()
                ->firstWhere('id', $files->last()->id)->pivot->last_error
        );
    }

    public function testGetFileContentsStreamReturnsStreamWithLatestFileVersionContents(): void {
        $file = File::factory()
            ->for($this->backupConfiguration->user)
            ->hasAttached($this->backupConfiguration)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $testContent = 'test content';

        $this->mockFileVersionService
            ->shouldReceive('writeContentsToStream')
            ->once()
            ->withArgs(function (
                File $receivedFile,
                FileVersion $receivedFileVersion,
                mixed $resource
            ) use ($file, $testContent) {
                $this->assertEquals($file->id, $receivedFile->id);
                $this->assertEquals(
                    $file->latestVersion->id,
                    $receivedFileVersion->id
                );

                $this->assertIsResource($resource);

                fwrite($resource, $testContent);

                return true;
            });

        $stream = $this->abstractBackupProvider->getFileContentStream($file);

        $this->assertEquals($testContent, stream_get_contents($stream));

        fclose($stream);
    }

    public function testGetConfigReturnsConfigValueForProvider(): void {
        $testKey = 'test';
        $testValue = 'test value';

        $configKey =
            'backups.providers.' . TestBackupProvider::class . ".$testKey";

        config([$configKey => $testValue]);

        $this->assertEquals(
            $testValue,
            TestBackupProvider::getConfig($testKey)
        );
    }

    public function testGetConfigReturnsDefaultValueIfConfigKeyDoesNotExist(): void {
        $testKey = 'test';
        $default = 'default value';

        $this->assertEquals(
            $default,
            TestBackupProvider::getConfig($testKey, default: $default)
        );
    }

    public function testCreateConfigurationReturnsNewConfigurationForProviderClass(): void {
        $user = User::factory()->create();
        $config = [
            'test' => 'test',
        ];
        $label = 'test label';

        $configuration = TestBackupProvider::createConfiguration(
            $user,
            $config,
            $label
        );

        $this->assertEquals(
            TestBackupProvider::class,
            $configuration->provider_class
        );
        $this->assertEquals($config, $configuration->config);
        $this->assertEquals($label, $configuration->label);
        $this->assertEquals($user->id, $configuration->user_id);
    }
}

class TestBackupProvider extends AbstractBackupProvider {
    public array $backedUpFileIds = [];

    public ?Closure $backupFileCallback = null;

    public static function getDisplayInformation(): array {
        return [
            'name' => 'Test',
            'icon' => 'fa-solid fa-hard-drive',
            'description' => 'Test',
        ];
    }

    public function backupFile(File $file): void {
        if ($this->backupFileCallback !== null) {
            call_user_func($this->backupFileCallback, $file);
        }

        $this->backedUpFileIds[] = $file->id;
    }

    public function getFileContentStream(File $file): mixed {
        return parent::getFileContentStream($file);
    }

    public static function getConfig(
        string $key,
        mixed $default = null
    ): mixed {
        return parent::getConfig($key, $default);
    }
}
