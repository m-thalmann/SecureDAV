<?php

namespace Tests\Feature\Backups;

use App\Jobs\RunBackup;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
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
        Queue::fake();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached(
                File::factory()
                    ->for($this->user)
                    ->has(FileVersion::factory(), 'versions')
            )
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/backup"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        Queue::assertPushed(function (RunBackup $job) use ($configuration) {
            $this->assertEquals(
                $configuration->id,
                $job->backupConfiguration->id
            );

            return true;
        });
    }

    public function testShowsErrorWhenBackupIsRateLimited(): void {
        Queue::fake();

        $configuration = BackupConfiguration::factory()
            ->for($this->user)
            ->hasAttached(
                File::factory()
                    ->for($this->user)
                    ->has(FileVersion::factory(), 'versions')
            )
            ->create([
                'provider_class' => StubBackupProvider::class,
            ]);

        for ($i = 0; $i < RunBackup::RATE_LIMITER_ATTEMPTS; $i++) {
            RateLimiter::hit(RunBackup::getRateLimiterKey($configuration));
        }

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/backup"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_ERROR
        );

        Queue::assertNothingPushed();
    }

    public function testBackupCannotBeRunForOtherUser(): void {
        Queue::fake();

        $configuration = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->post(
            "/backups/{$configuration->uuid}/backup"
        );

        $response->assertNotFound();

        Queue::assertNothingPushed();
    }
}
