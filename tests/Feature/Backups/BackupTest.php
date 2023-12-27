<?php

namespace Tests\Feature\Backups;

use App\Backups\AbstractBackupProvider;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class BackupTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testBackupCanBeRun(): void {
        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached(
                File::factory()
                    ->for($this->user)
                    ->has(FileVersion::factory(), 'versions')
            )
            ->create([
                'provider_class' => TestBackupProvider::class,
            ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/backup"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertTrue(TestBackupProvider::$backupRun);

        TestBackupProvider::$backupRun = false;
    }

    public function testBackupCanBeRunWithFailure(): void {
        TestBackupProvider::$backupShouldFail = true;

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached(
                File::factory()
                    ->for($this->user)
                    ->has(FileVersion::factory(), 'versions')
            )
            ->create([
                'provider_class' => TestBackupProvider::class,
            ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/backup"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertRequestHasSessionMessage(
            $response,
            SessionMessage::TYPE_WARNING
        );

        TestBackupProvider::$backupShouldFail = false;
    }

    public function testBackupCannotBeRunForOtherUser(): void {
        $configuration = BackupConfiguration::factory()->create([
            'provider_class' => TestBackupProvider::class,
        ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/backup"
        );

        $response->assertNotFound();
    }
}

class TestBackupProvider extends AbstractBackupProvider {
    public static bool $backupRun = false;

    public static bool $backupShouldFail = false;

    public static function getDisplayInformation(): array {
        return [
            'name' => 'Test',
            'icon' => 'fa-solid fa-hard-drive',
            'description' => 'Test',
        ];
    }

    public static function getConfigFormTemplate(): ?string {
        return null;
    }

    public static function validateConfig(array $config): array {
        return [];
    }

    public function backupFile(File $file): void {
        if (static::$backupShouldFail) {
            throw new \Exception('Backup failed.');
        }

        static::$backupRun = true;
    }
}
