<?php

namespace Tests\Unit\Casts;

use App\Casts\EncryptedBackupConfig;
use App\Models\BackupConfiguration;
use App\Services\EncryptionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\TestSupport\StubBackupProvider;

class EncryptedBackupConfigTest extends TestCase {
    use LazilyRefreshDatabase;

    protected EncryptedBackupConfig $cast;

    protected EncryptionService|MockInterface $mockEncryptionService;
    protected BackupConfiguration $configModel;

    protected function setUp(): void {
        parent::setUp();

        $this->mockEncryptionService = Mockery::mock(EncryptionService::class);

        $this->app->bind(EncryptionService::class, function () {
            return $this->mockEncryptionService;
        });

        $this->cast = new EncryptedBackupConfig();

        $this->configModel = BackupConfiguration::factory()->create([
            'provider_class' => StubBackupProvider::class,
        ]);
    }

    public function testGetThrowsExceptionWhenModelIsNotBackupConfiguration(): void {
        $this->expectException(InvalidArgumentException::class);

        $this->cast->get($this->configModel->user, 'config', null, []);
    }

    public function testGetReturnsNullWhenValueIsNull(): void {
        $this->mockEncryptionService->shouldNotReceive('decrypt');

        $result = $this->cast->get($this->configModel, 'config', null, []);

        $this->assertNull($result);
    }

    public function testGetReturnsDecryptedValue(): void {
        $encryptionKey = $this->configModel->user->encryption_key;

        $encryptedValue = 'encrypted value';
        $base64EncodedEncryptedValue = base64_encode($encryptedValue);

        $mockConfig = ['test' => 'test'];
        $mockConfigJson = json_encode($mockConfig);

        $this->mockEncryptionService
            ->shouldReceive('decrypt')
            ->once()
            ->withArgs(function (
                string $encryptionKeyArg,
                $inputArg,
                $outputArg
            ) use ($encryptionKey, $encryptedValue, $mockConfigJson) {
                $this->assertEquals($encryptionKey, $encryptionKeyArg);
                $this->assertEquals(
                    $encryptedValue,
                    stream_get_contents($inputArg)
                );

                return true;
            })
            ->andReturnUsing(function (
                string $encryptionKeyArg,
                $inputArg,
                $outputArg
            ) use ($mockConfigJson) {
                fwrite($outputArg, $mockConfigJson);
            });

        $result = $this->cast->get(
            $this->configModel,
            'config',
            $base64EncodedEncryptedValue,
            []
        );

        $this->assertEquals($mockConfig, $result);
    }

    public function testSetThrowsExceptionWhenModelIsNotBackupConfiguration(): void {
        $this->expectException(InvalidArgumentException::class);

        $this->cast->set($this->configModel->user, 'config', null, []);
    }

    public function testSetReturnsNullWhenValueIsNull(): void {
        $this->mockEncryptionService->shouldNotReceive('encrypt');

        $result = $this->cast->set($this->configModel, 'config', null, []);

        $this->assertNull($result);
    }

    public function testSetReturnsEncryptedAndBase64EncodedValue(): void {
        $encryptionKey = $this->configModel->user->encryption_key;

        $mockConfig = ['test' => 'test'];
        $mockConfigJson = json_encode($mockConfig);

        $encryptedValue = 'encrypted value';
        $base64EncodedEncryptedValue = base64_encode($encryptedValue);

        $this->mockEncryptionService
            ->shouldReceive('encrypt')
            ->once()
            ->withArgs(function (
                string $encryptionKeyArg,
                $inputArg,
                $outputArg
            ) use ($encryptionKey, $mockConfigJson) {
                $this->assertEquals($encryptionKey, $encryptionKeyArg);
                $this->assertEquals(
                    $mockConfigJson,
                    stream_get_contents($inputArg)
                );

                return true;
            })
            ->andReturnUsing(function (
                string $encryptionKeyArg,
                $inputArg,
                $outputArg
            ) use ($encryptedValue) {
                fwrite($outputArg, $encryptedValue);
            });

        $result = $this->cast->set(
            $this->configModel,
            'config',
            $mockConfig,
            []
        );

        $this->assertEquals($base64EncodedEncryptedValue, $result);
    }
}
