<?php

namespace Tests\Unit\Services;

use App\Exceptions\FileAlreadyExistsException;
use App\Exceptions\FileWriteException;
use App\Exceptions\MimeTypeMismatchException;
use App\Exceptions\NoVersionFoundException;
use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileEncryptionService;
use App\Services\FileVersionService;
use Closure;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class FileVersionServiceTest extends TestCase {
    use LazilyRefreshDatabase;

    protected FileVersionServiceTestClass|MockInterface $service;

    protected FilesystemAdapter $storageFake;
    protected FileEncryptionService|MockInterface $fileEncryptionServiceMock;

    protected function setUp(): void {
        parent::setUp();

        $this->fileEncryptionServiceMock = Mockery::mock(
            FileEncryptionService::class
        );

        $this->storageFake = Storage::fake('files');

        /**
         * @var FileVersionServiceTestClass|MockInterface
         */
        $this->service = Mockery::mock(FileVersionServiceTestClass::class, [
            $this->fileEncryptionServiceMock,
            $this->storageFake,
        ]);

        $this->service->makePartial();
    }

    public function testCopyLatestVersionCreatesANewVersionByCopyingTheLatestOne(): void {
        $file = File::factory()->create();
        $latestVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $newLabel = 'test-label';

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $file,
                Closure $storeFileAction,
                string $checksum,
                int $bytes,
                string $label
            ) use ($latestVersion, $newLabel) {
                $this->assertEquals($latestVersion->file_id, $file->id);
                $this->assertEquals($latestVersion->checksum, $checksum);
                $this->assertEquals($latestVersion->bytes, $bytes);
                $this->assertEquals($newLabel, $label);

                $newPath = 'test-path';

                $storeFileAction($newPath);

                $this->storageFake->assertExists($newPath);

                $this->assertEquals(
                    $this->storageFake->get($latestVersion->storage_path),
                    $this->storageFake->get($newPath)
                );

                return true;
            })
            ->once();

        $this->service->copyLatestVersion($file, $newLabel);
    }

    public function testCopyLatestVersionFailsIfFileHasNoVersions(): void {
        $this->expectException(NoVersionFoundException::class);

        $file = File::factory()->create();

        $this->service->copyLatestVersion($file);
    }

    public function testCopyLatestVersionFailsIfFileCantBeCopied(): void {
        $file = File::factory()->create();
        $latestVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $file,
                Closure $storeFileAction,
                string $checksum,
                int $bytes,
                ?string $label
            ) use ($latestVersion) {
                $newPath = 'test-path';

                $this->storageFake->delete($latestVersion->storage_path);

                try {
                    $storeFileAction($newPath);
                } catch (FileWriteException $e) {
                    $this->storageFake->assertMissing($newPath);
                    return true;
                }

                $this->fail('FileWriteException was not thrown');
            })
            ->once();

        $this->service->copyLatestVersion($file);
    }

    public function testCreateNewVersionCreatesANewVersionFromTheGivenFile(): void {
        $content = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $content
        );

        $file = File::factory()
            ->encrypted()
            ->create(['mime_type' => $uploadedFile->getClientMimeType()]);

        $expectedBytes = $uploadedFile->getSize();

        $newLabel = 'test-label';

        $newPath = 'test-path';

        $this->service
            ->shouldReceive('storeFile')
            ->withArgs([$uploadedFile, $newPath, $file->encryption_key, false])
            ->once();

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $receivedFile,
                Closure $storeFileAction,
                string $checksum,
                int $bytes,
                string $label
            ) use ($file, $content, $expectedBytes, $newLabel, $newPath) {
                $this->assertEquals($file->id, $receivedFile->id);
                $this->assertEquals(md5($content), $checksum);
                $this->assertEquals($expectedBytes, $bytes);
                $this->assertEquals($newLabel, $label);

                $storeFileAction($newPath);

                return true;
            })
            ->once();

        $this->service->createNewVersion($file, $uploadedFile, $newLabel);
    }

    public function testCreateNewVersionFailsIfMimeTypesDontMatch(): void {
        $this->expectException(MimeTypeMismatchException::class);

        $file = File::factory()->create(['mime_type' => 'application/json']);

        try {
            $this->service->createNewVersion(
                $file,
                UploadedFile::fake()->create(
                    'test.pdf',
                    mimeType: 'application/pdf'
                )
            );
        } catch (Exception $e) {
            $this->assertDatabaseMissing('file_versions', [
                'file_id' => $file->id,
            ]);

            throw $e;
        }
    }

    public function testCreateVersionCreatesANewVersion(): void {
        $nextVersion = 55;

        $file = File::factory()->create(['next_version' => $nextVersion]);

        $expectedBytes = 100;
        $expectedChecksum = md5('test');

        $newLabel = 'test-label';

        $storeFileActionCalled = Mockery::mock('invokedTest');
        $storeFileActionCalled->shouldReceive('invoked')->once();

        $foundNewPath = null;

        $storeFileAction = function (string $newPath) use (
            $storeFileActionCalled,
            &$foundNewPath
        ) {
            $storeFileActionCalled->invoked();

            $foundNewPath = $newPath;
        };

        $this->service->createVersion(
            $file,
            $storeFileAction,
            $expectedChecksum,
            $expectedBytes,
            $newLabel
        );

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'label' => $newLabel,
            'version' => $nextVersion,
            'storage_path' => $foundNewPath,
            'checksum' => $expectedChecksum,
            'bytes' => $expectedBytes,
        ]);

        $file->refresh();

        $this->assertEquals($nextVersion + 1, $file->next_version);
    }

    public function testCreateVersionFailsIfFileAlreadyExists(): void {
        $this->expectException(FileAlreadyExistsException::class);

        $newPath = Str::freezeUuids()->toString();
        $this->storageFake->put($newPath, 'test');

        $file = File::factory()->create();

        try {
            $this->service->createVersion($file, fn() => null, 'test', 4);
        } catch (Exception $e) {
            Str::createUuidsNormally();

            $this->assertDatabaseMissing('file_versions', [
                'file_id' => $file->id,
            ]);

            throw $e;
        }

        Str::createUuidsNormally();
    }

    public function testCreateVersionFailsAndDoesNotStoreTheVersionIfNextVersionCantBeSetOnFile(): void {
        $this->expectException(RuntimeException::class);

        /**
         * @var File
         */
        $file = File::factory()->create();

        // this line is used to force a fail when saving the model
        $file->updating(fn() => false);

        try {
            $this->service->createVersion($file, fn() => null, 'test', 4);
        } catch (Exception $e) {
            $this->assertDatabaseMissing('file_versions', [
                'file_id' => $file->id,
            ]);

            throw $e;
        }
    }

    public function testCreateVersionFailsAndDoesNotStoreTheVersionIfTheActionClosureThrowsException(): void {
        $exception = new RuntimeException('test-exception');

        $this->expectExceptionObject($exception);

        $file = File::factory()->create();

        try {
            $this->service->createVersion(
                $file,
                fn() => throw $exception,
                'test',
                4
            );
        } catch (Exception $e) {
            $this->assertDatabaseMissing('file_versions', [
                'file_id' => $file->id,
            ]);

            throw $e;
        }
    }

    public function testUpdateLatestVersionReplacesTheFileForTheLatestVersion(): void {
        $content = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $content
        );

        $file = File::factory()
            ->encrypted()
            ->create(['mime_type' => $uploadedFile->getClientMimeType()]);

        $version = FileVersion::factory()
            ->for($file)
            ->create();

        $expectedBytes = $uploadedFile->getSize();

        $this->service
            ->shouldReceive('storeFile')
            ->withArgs([
                $uploadedFile,
                $version->storage_path,
                $file->encryption_key,
                true,
            ])
            ->once();

        $this->service->updateLatestVersion($file, $uploadedFile);

        $this->assertDatabaseHas('file_versions', [
            'id' => $version->id,
            'file_id' => $file->id,
            'version' => $version->version,
            'storage_path' => $version->storage_path,
            'checksum' => md5($content),
            'bytes' => $expectedBytes,
        ]);
    }

    public function testUpdateLatestVersionFailsIfFileHasNoVersions(): void {
        $this->expectException(NoVersionFoundException::class);

        $uploadedFile = UploadedFile::fake()->create('test.txt');

        $file = File::factory()->create([
            'mime_type' => $uploadedFile->getClientMimeType(),
        ]);

        $this->service->updateLatestVersion($file, $uploadedFile);
    }

    public function testUpdateLatestVersionFailsAndDoesntStoreTheUpdateIfStoreFileFails(): void {
        $this->expectException(FileWriteException::class);

        $content = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $content
        );

        $file = File::factory()->create([
            'mime_type' => $uploadedFile->getClientMimeType(),
        ]);

        $version = FileVersion::factory()
            ->for($file)
            ->create();

        $this->service
            ->shouldReceive('storeFile')
            ->once()
            ->andThrow(new FileWriteException());

        try {
            $this->service->updateLatestVersion($file, $uploadedFile);
        } catch (Exception $e) {
            $this->assertDatabaseHas('file_versions', [
                'id' => $version->id,
                'file_id' => $file->id,
                'version' => $version->version,
                'storage_path' => $version->storage_path,
                'checksum' => $version->checksum,
                'bytes' => $version->bytes,
            ]);

            throw $e;
        }
    }

    public function testUpdateLatestVersionFailsIfMimeTypesDontMatch(): void {
        $this->expectException(MimeTypeMismatchException::class);

        $file = File::factory()->create(['mime_type' => 'application/json']);

        $version = FileVersion::factory()
            ->for($file)
            ->create();

        try {
            $this->service->updateLatestVersion(
                $file,
                UploadedFile::fake()->create(
                    'test.pdf',
                    mimeType: 'application/pdf'
                )
            );
        } catch (Exception $e) {
            $this->assertDatabaseHas('file_versions', [
                'id' => $version->id,
                'file_id' => $file->id,
                'version' => $version->version,
                'storage_path' => $version->storage_path,
                'checksum' => $version->checksum,
                'bytes' => $version->bytes,
            ]);

            throw $e;
        }
    }

    public function testStoreFileSavesTheGivenFileToTheGivenPath(): void {
        $path = 'test-path';

        $content = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $content
        );

        $this->storageFake->assertMissing($path);

        $this->service->storeFile(
            $uploadedFile,
            $path,
            encryptionKey: null,
            useTemporaryFile: false
        );

        $this->storageFake->assertExists($path);

        $this->assertEquals($content, $this->storageFake->get($path));
    }

    public function testStoreFileSavesAndEncryptsTheGivenFileToTheGivenPath(): void {
        $encryptionKey = 'test-key';

        $path = 'test-path';

        $content = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $content
        );

        $this->fileEncryptionServiceMock
            ->shouldReceive('encrypt')
            ->withArgs(function (
                string $key,
                mixed $inputResource,
                mixed $outputResource
            ) use ($encryptionKey, $content) {
                $this->assertEquals($encryptionKey, $key);
                $this->assertIsResource($inputResource);
                $this->assertIsResource($outputResource);

                $this->assertEquals(
                    $content,
                    stream_get_contents($inputResource)
                );

                return true;
            })
            ->once();

        $this->service->storeFile(
            $uploadedFile,
            $path,
            encryptionKey: $encryptionKey,
            useTemporaryFile: false
        );
    }

    public function testStoreFileUsesATemporaryFileAndThenMovesItToTheDestinationIfRequested(): void {
        $path = 'test-path';

        $content = fake()->text();
        $encryptedContent = fake()->text();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $content
        );

        $this->fileEncryptionServiceMock
            ->shouldReceive('encrypt')
            ->withArgs(function (
                string $key,
                mixed $inputResource,
                mixed $outputResource
            ) use ($path, $encryptedContent) {
                $tmpPath = stream_get_meta_data($outputResource)['uri'];

                $this->assertNotEquals(
                    $this->storageFake->path($path),
                    $tmpPath
                );

                file_put_contents($tmpPath, $encryptedContent);

                return true;
            })
            ->once();

        $this->service->storeFile(
            $uploadedFile,
            $path,
            encryptionKey: 'test-key',
            useTemporaryFile: true
        );

        $this->storageFake->assertExists($path);

        $this->assertEquals($encryptedContent, $this->storageFake->get($path));
    }

    public function testStoreFileFailsIfTheEncryptionFails(): void {
        $this->expectException(FileWriteException::class);

        $path = 'test-path';

        $this->fileEncryptionServiceMock
            ->shouldReceive('encrypt')
            ->once()
            ->andThrow(new FileWriteException());

        try {
            $this->service->storeFile(
                UploadedFile::fake()->create('test.txt'),
                $path,
                encryptionKey: 'test-key',
                useTemporaryFile: false
            );
        } catch (Exception $e) {
            $this->storageFake->assertMissing($path);

            throw $e;
        }
    }

    public function testStoreFileFailsIfTemporaryFileCantBeMoved(): void {
        $this->expectException(FileWriteException::class);

        $path = 'test-path';
        $tmpPath = $path . $this->service->getTmpSuffix();

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            fake()->text()
        );

        $lockFile = fopen($this->storageFake->path($path), 'w');

        $this->fileEncryptionServiceMock->shouldReceive('encrypt')->once();

        try {
            $this->service->storeFile(
                $uploadedFile,
                $path,
                encryptionKey: 'test-key',
                useTemporaryFile: true
            );
        } catch (Exception $e) {
            $this->storageFake->assertMissing($tmpPath);

            fclose($lockFile);

            $this->assertEmpty(
                file_get_contents($this->storageFake->path($path))
            );

            throw $e;
        }

        fclose($lockFile);
    }

    public function testStoreFileFailsIfFileCantBeStoredWithoutEncryption(): void {
        $this->expectException(FileWriteException::class);

        $path = '<illegal-path>';

        try {
            $this->service->storeFile(
                UploadedFile::fake()->create('test.txt'),
                $path,
                encryptionKey: null,
                useTemporaryFile: false
            );
        } catch (Exception $e) {
            $this->storageFake->assertMissing($path);

            throw $e;
        }
    }

    public function testWriteContentsToStreamWritesTheContentsOfTheUnencryptedFileVersionToTheGivenStream(): void {
        $content = fake()->text();

        $file = File::factory()->create();

        $version = FileVersion::factory()
            ->for($file)
            ->create(['bytes' => strlen($content)]);

        $this->storageFake->put($version->storage_path, $content);

        $stream = fopen('php://memory', 'r+');

        $this->service->writeContentsToStream($file, $version, $stream);

        rewind($stream);

        $this->assertEquals($content, stream_get_contents($stream));

        fclose($stream);
    }

    public function testWriteContentsToStreamWritesTheContentsOfTheEncryptedFileVersionToTheGivenStream(): void {
        $content = fake()->text();

        $file = File::factory()
            ->encrypted()
            ->create();

        $version = FileVersion::factory()
            ->for($file)
            ->create(['bytes' => strlen($content)]);

        $this->storageFake->put(
            $version->storage_path,
            '==encrypted-content=='
        );

        $this->fileEncryptionServiceMock
            ->shouldReceive('decrypt')
            ->withArgs(function (
                string $key,
                mixed $inputResource,
                mixed $outputResource
            ) use ($file, $content) {
                $this->assertEquals($file->encryption_key, $key);

                fwrite($outputResource, $content);

                return true;
            })
            ->once();

        $stream = fopen('php://memory', 'r+');

        $this->service->writeContentsToStream($file, $version, $stream);

        rewind($stream);

        $this->assertEquals($content, stream_get_contents($stream));

        fclose($stream);
    }

    public function testWriteContentsToStreamFailsIfTheFileVersionDoesNotBelongToTheFile(): void {
        $this->expectException(InvalidArgumentException::class);

        $file = File::factory()->create();

        $version = FileVersion::factory()->create();

        $this->service->writeContentsToStream($file, $version, null);
    }

    public function testCreateDownloadResponseDownloadsFileVersion(): void {
        $content = fake()->text();

        $file = File::factory()->create();

        $version = FileVersion::factory()
            ->for($file)
            ->create(['bytes' => strlen($content)]);

        $this->service
            ->shouldReceive('writeContentsToStream')
            ->withArgs(function (
                File $receivedFile,
                FileVersion $receivedVersion,
                mixed $resource
            ) use ($file, $version, $content) {
                $this->assertEquals($file, $receivedFile);
                $this->assertEquals($version, $receivedVersion);
                $this->assertIsResource($resource);

                fwrite($resource, $content);

                return true;
            })
            ->once();

        $response = $this->service->createDownloadResponse($file, $version);

        $this->assertEquals(
            $content,
            $this->getStreamedResponseContent($response)
        );

        $this->assertEquals(
            $file->mime_type,
            $response->headers->get('Content-Type')
        );

        $this->assertEquals(
            $version->bytes,
            $response->headers->get('Content-Length')
        );

        $this->assertEquals(
            "\"$version->checksum\"",
            $response->headers->get('ETag')
        );
    }

    public function testCreateDownloadResponseFailsIfTheFileVersionDoesNotBelongToTheFile(): void {
        $this->expectException(InvalidArgumentException::class);

        $file = File::factory()->create();

        $version = FileVersion::factory()->create();

        $this->service->createDownloadResponse($file, $version);
    }
}

class FileVersionServiceTestClass extends FileVersionService {
    public function createVersion(
        File $file,
        Closure $storeFileAction,
        string $checksum,
        int $bytes,
        ?string $label = null
    ): void {
        parent::createVersion(
            $file,
            $storeFileAction,
            $checksum,
            $bytes,
            $label
        );
    }

    public function storeFile(
        UploadedFile $file,
        string $path,
        ?string $encryptionKey,
        bool $useTemporaryFile
    ): void {
        parent::storeFile($file, $path, $encryptionKey, $useTemporaryFile);
    }

    public function getTmpSuffix(): string {
        return static::TMP_SUFFIX;
    }
}
