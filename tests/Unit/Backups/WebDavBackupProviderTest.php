<?php

namespace Tests\Unit\Backups;

use App\Backups\WebDavBackupProvider;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class WebDavBackupProviderTest extends TestCase {
    use LazilyRefreshDatabase;

    protected array $webDavConfig = [
        'targetUrl' => 'https://example.com/remote.php/dav/files/username/',
        'username' => 'username',
        'password' => 'password',
        'method' => 'PUT',
    ];

    protected BackupConfiguration $backupConfiguration;
    protected FileVersionService|MockInterface $mockFileVersionService;
    protected HttpClient|MockInterface $mockHttpClient;

    protected WebDavBackupProviderTestClass|MockInterface $webDavBackupProvider;

    protected function setUp(): void {
        parent::setUp();

        $this->backupConfiguration = BackupConfiguration::factory()->create([
            'provider_class' => WebDavBackupProvider::class,
            'config' => $this->webDavConfig,
        ]);
        $this->mockFileVersionService = Mockery::mock(
            FileVersionService::class
        );
        $this->mockHttpClient = Mockery::mock(HttpClient::class);

        $this->webDavBackupProvider = Mockery::mock(
            WebDavBackupProviderTestClass::class,
            [
                $this->backupConfiguration,
                $this->mockFileVersionService,
                $this->mockHttpClient,
            ]
        )
            ->makePartial(['getFileContentStream'])
            ->shouldAllowMockingProtectedMethods();
    }

    public function testGetDisplayInformationReturnsArrayWithProviderInformation(): void {
        $displayInformation = WebDavBackupProvider::getDisplayInformation();

        $this->assertIsArray($displayInformation);
        $this->assertArrayHasKey('name', $displayInformation);
        $this->assertArrayHasKey('icon', $displayInformation);
        $this->assertArrayHasKey('description', $displayInformation);
    }

    public function testGetConfigFormTemplateReturnsThePathToTheTemplate(): void {
        $view = view(WebDavBackupProvider::getConfigFormTemplate());

        $this->assertInstanceOf(View::class, $view);
    }

    public function testValidateConfigReturnsArrayWithDataIfConfigIsValid(): void {
        $config = [
            'method' => 'PUT',
            'targetUrl' => 'https://example.com/remote.php/dav/files/username/',
            'username' => 'username',
            'password' => 'password',
        ];

        $this->assertEquals(
            $config,
            WebDavBackupProvider::validateConfig($config)
        );
    }

    public function testValidateConfigThrowsExceptionIfConfigIsNotValid(): void {
        $config = [
            'method' => 'INVALID',
            'targetUrl' => 'https://example.com/remote.php/dav/files/username/',
            'username' => 'username',
            'password' => 'password',
        ];

        $this->expectException(ValidationException::class);

        WebDavBackupProvider::validateConfig($config);
    }

    public function testGetSensitiveConfigKeysReturnsArrayWithSensitiveKeys(): void {
        $this->assertContains(
            'password',
            WebDavBackupProvider::getSensitiveConfigKeys()
        );
    }

    public function testBackupFileUploadsTheLatestVersionToTheTargetUrl(): void {
        $file = File::factory()
            ->for($this->backupConfiguration->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $targetName = 'test-target-name.txt';

        $testContentStream = $this->createStream('test content');

        $this->webDavBackupProvider
            ->shouldReceive('getFileContentStream')
            ->once()
            ->with($file)
            ->andReturn($testContentStream);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $this->mockHttpClient
            ->shouldReceive('request')
            ->once()
            ->with(
                $this->webDavConfig['method'],
                $this->webDavConfig['targetUrl'] . $targetName,
                [
                    'auth' => [
                        $this->webDavConfig['username'],
                        $this->webDavConfig['password'],
                    ],
                    'body' => $testContentStream,
                ]
            )
            ->andReturn($response);

        $this->webDavBackupProvider->backupFile($file, $targetName);

        $this->assertIsClosedResource($testContentStream);
    }

    public function testBackupFileThrowsExceptionIfTheResponseStatusCodeIsNotBetween200And299(): void {
        $file = File::factory()
            ->for($this->backupConfiguration->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $testContentStream = $this->createStream('test content');

        $this->webDavBackupProvider
            ->shouldReceive('getFileContentStream')
            ->once()
            ->with($file)
            ->andReturn($testContentStream);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(401);
        $response->shouldReceive('getReasonPhrase')->andReturn('Unauthorized');

        $this->mockHttpClient
            ->shouldReceive('request')
            ->once()
            ->with(
                $this->webDavConfig['method'],
                $this->webDavConfig['targetUrl'] . $file->name,
                [
                    'auth' => [
                        $this->webDavConfig['username'],
                        $this->webDavConfig['password'],
                    ],
                    'body' => $testContentStream,
                ]
            )
            ->andReturn($response);

        $this->expectExceptionMessage('Error: Unauthorized');

        try {
            $this->webDavBackupProvider->backupFile($file, $file->name);
        } catch (Exception $e) {
            $this->assertIsClosedResource($testContentStream);

            throw $e;
        }
    }

    public function testGetWebDavConfigReturnsTheConfigOfTheConfiguration(): void {
        $this->assertEquals(
            $this->webDavConfig,
            $this->webDavBackupProvider->getWebDavConfig()
        );
    }

    public function testGetWebDavConfigAddsTrailingSlashToTargetUrlIfMissing(): void {
        $config = $this->backupConfiguration->config;
        $config['targetUrl'] =
            'https://example.com/remote.php/dav/files/username';

        $this->backupConfiguration->update([
            'config' => $config,
        ]);

        $this->assertStringEndsWith(
            '/',
            $this->webDavBackupProvider->getWebDavConfig()['targetUrl']
        );
    }

    public function testGetWebDavConfigThrowsExceptionIfTargetUrlIsNotConfigured(): void {
        $config = $this->backupConfiguration->config;
        $config['targetUrl'] = null;

        $this->backupConfiguration->update([
            'config' => $config,
        ]);

        $this->expectException(Exception::class);

        $this->webDavBackupProvider->getWebDavConfig();
    }

    public function testGetWebDavConfigThrowsExceptionIfUsernameIsNotConfigured(): void {
        $config = $this->backupConfiguration->config;
        $config['username'] = null;

        $this->backupConfiguration->update([
            'config' => $config,
        ]);

        $this->expectException(Exception::class);

        $this->webDavBackupProvider->getWebDavConfig();
    }
}

class WebDavBackupProviderTestClass extends WebDavBackupProvider {
    public function backupFile(File $file, string $targetName): void {
        parent::backupFile($file, $targetName);
    }

    public function getWebDavConfig(): array {
        return parent::getWebDavConfig();
    }
}
