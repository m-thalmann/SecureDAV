<?php

namespace Tests\Unit\Services;

use App\Exceptions\FileAlreadyExistsException;
use App\Exceptions\FileWriteException;
use App\Exceptions\NoVersionFoundException;
use App\Exceptions\StreamWriteException;
use App\Models\File;
use App\Models\FileVersion;
use App\Services\EncryptionService;
use App\Services\FileVersionService;
use App\Support\FileInfo;
use Closure;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class FileVersionServiceTest extends TestCase {
    use LazilyRefreshDatabase;

    protected FileVersionServiceTestClass|MockInterface $service;

    protected EncryptionService|MockInterface $encryptionServiceMock;

    protected function setUp(): void {
        parent::setUp();

        $this->encryptionServiceMock = Mockery::mock(EncryptionService::class);

        /**
         * @var FileVersionServiceTestClass|MockInterface
         */
        $this->service = Mockery::mock(FileVersionServiceTestClass::class, [
            $this->encryptionServiceMock,
            $this->storageFake,
        ]);

        $this->service->makePartial();
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCopyLatestVersionCreatesANewUnencryptedVersionByCopyingTheLatestOne(
        bool $latestVersionEncrypted
    ): void {
        $file = File::factory()->create();

        $latestVersionFactory = FileVersion::factory()->for($file);

        if ($latestVersionEncrypted) {
            $latestVersionFactory = $latestVersionFactory->encrypted();
        }

        $latestVersion = $latestVersionFactory->create();

        $newLabel = 'test-label';

        $createdVersion = FileVersion::make();

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $file,
                Closure $storeFileAction,
                ?string $encryptionKey,
                ?string $label
            ) use ($latestVersion, $newLabel, $latestVersionEncrypted) {
                $this->assertEquals($latestVersion->file_id, $file->id);
                $this->assertEquals($newLabel, $label);

                $newPath = 'test-path';

                $storedFileInfo = $storeFileAction($newPath, $encryptionKey);

                $this->storageFake->assertExists($newPath);

                $this->assertNull($encryptionKey);

                if ($latestVersionEncrypted) {
                    $this->assertNotEquals(
                        $this->storageFake->get($latestVersion->storage_path),
                        $this->storageFake->get($newPath)
                    );
                } else {
                    $this->assertEquals(
                        $this->storageFake->get($latestVersion->storage_path),
                        $this->storageFake->get($newPath)
                    );
                }

                $this->assertEquals(
                    $latestVersion->mime_type,
                    $storedFileInfo->mimeType
                );
                $this->assertEquals(
                    $latestVersion->checksum,
                    $storedFileInfo->checksum
                );
                $this->assertEquals(
                    $latestVersion->bytes,
                    $storedFileInfo->size
                );

                return true;
            })
            ->once()
            ->andReturn($createdVersion);

        if ($latestVersionEncrypted) {
            $this->encryptionServiceMock
                ->shouldReceive('decrypt')
                ->withArgs(function (
                    string $key,
                    mixed $inputResource,
                    mixed $outputResource
                ) use ($latestVersion) {
                    $this->assertEquals($latestVersion->encryption_key, $key);

                    $this->assertIsResource($inputResource);
                    $this->assertIsResource($outputResource);

                    fwrite($outputResource, 'test-content');

                    return true;
                })
                ->once();
        } else {
            $this->encryptionServiceMock->shouldReceive('decrypt')->never();
        }

        $returnedVersion = $this->service->copyLatestVersion(
            $file,
            encrypted: false,
            label: $newLabel
        );

        $this->assertEquals($createdVersion, $returnedVersion);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCopyLatestVersionCreatesANewEncryptedVersionByCopyingTheLatestOne(
        bool $latestVersionEncrypted
    ): void {
        $file = File::factory()->create();

        $latestVersionFactory = FileVersion::factory()->for($file);

        if ($latestVersionEncrypted) {
            $latestVersionFactory = $latestVersionFactory->encrypted();
        }

        $latestVersion = $latestVersionFactory->create();

        $testContent = 'test-content';

        if ($latestVersionEncrypted) {
            $this->storageFake->put(
                $latestVersion->storage_path,
                '==encrypted-content=='
            );
        } else {
            $this->storageFake->put($latestVersion->storage_path, $testContent);
        }

        $newLabel = 'test-label';

        $createdVersion = FileVersion::make();

        $encryptionKey = null;

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $file,
                Closure $storeFileAction,
                ?string $receivedEncryptionKey,
                ?string $label
            ) use ($latestVersion, $newLabel, &$encryptionKey) {
                $encryptionKey = $receivedEncryptionKey;

                $this->assertEquals($latestVersion->file_id, $file->id);
                $this->assertEquals($newLabel, $label);

                $newPath = 'test-path';

                $storedFileInfo = $storeFileAction(
                    $newPath,
                    $receivedEncryptionKey
                );

                $this->storageFake->assertExists($newPath);

                $this->assertNotNull($receivedEncryptionKey);

                $this->assertNotEquals(
                    $this->storageFake->get($latestVersion->storage_path),
                    $this->storageFake->get($newPath)
                );

                $this->assertEquals(
                    $latestVersion->mime_type,
                    $storedFileInfo->mimeType
                );
                $this->assertEquals(
                    $latestVersion->checksum,
                    $storedFileInfo->checksum
                );
                $this->assertEquals(
                    $latestVersion->bytes,
                    $storedFileInfo->size
                );

                return true;
            })
            ->once()
            ->andReturn($createdVersion);

        if ($latestVersionEncrypted) {
            $this->encryptionServiceMock
                ->shouldReceive('decrypt')
                ->withArgs(function (
                    string $key,
                    mixed $inputResource,
                    mixed $outputResource
                ) use ($latestVersion, $testContent) {
                    $this->assertEquals($latestVersion->encryption_key, $key);

                    $this->assertIsResource($inputResource);
                    $this->assertIsResource($outputResource);

                    fwrite($outputResource, $testContent);

                    return true;
                })
                ->once();
        } else {
            $this->encryptionServiceMock->shouldReceive('decrypt')->never();
        }

        $this->encryptionServiceMock
            ->shouldReceive('encrypt')
            ->withArgs(function (
                string $key,
                mixed $inputResource,
                mixed $outputResource
            ) use (&$encryptionKey, $testContent) {
                $this->assertEquals($encryptionKey, $key);

                $this->assertIsResource($inputResource);
                $this->assertIsResource($outputResource);

                $this->assertEquals(
                    $testContent,
                    stream_get_contents($inputResource)
                );

                fwrite($outputResource, 'test-encrypted-content');

                return true;
            })
            ->once();

        $returnedVersion = $this->service->copyLatestVersion(
            $file,
            encrypted: true,
            label: $newLabel
        );

        $this->assertEquals($createdVersion, $returnedVersion);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCopyLatestVersionFailsIfFileHasNoVersions(
        bool $encrypted
    ): void {
        $this->expectException(NoVersionFoundException::class);

        $file = File::factory()->create();

        $this->service->copyLatestVersion($file, $encrypted);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCopyLatestVersionFailsIfFileCantBeCopied(
        bool $encrypted
    ): void {
        $file = File::factory()->create();
        $latestVersion = FileVersion::factory()
            ->for($file)
            ->create();

        $this->storageFake->delete($latestVersion->storage_path);

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $file,
                Closure $storeFileAction,
                ?string $encryptionKey,
                ?string $label
            ) {
                $newPath = 'test-path';

                try {
                    $storeFileAction($newPath, $encryptionKey);
                } catch (FileWriteException $e) {
                    $this->storageFake->assertMissing($newPath);
                    $this->storageFake->assertMissing(
                        $newPath . $this->service->getTmpSuffix()
                    );

                    return true;
                }

                $this->fail('FileWriteException was not thrown');
            })
            ->once();

        $this->service->copyLatestVersion($file, $encrypted);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateNewVersionCreatesANewVersionFromTheGivenResource(
        bool $isEncrypted
    ): void {
        $content = fake()->text();

        $resource = $this->createStream($content);

        $file = File::factory()->create();

        $newLabel = 'test-label';
        $newPath = 'test-path';

        $createdVersion = FileVersion::make();

        $fileInfo = new FileInfo(
            $newPath,
            'text/plain',
            strlen($content),
            md5($content)
        );

        $foundEncryptionKey = null;

        $this->service
            ->shouldReceive('createVersion')
            ->withArgs(function (
                File $receivedFile,
                Closure $storeFileAction,
                ?string $encryptionKey,
                ?string $label
            ) use (
                $file,
                $newLabel,
                $newPath,
                $fileInfo,
                $isEncrypted,
                &$foundEncryptionKey
            ) {
                $foundEncryptionKey = $encryptionKey;

                $this->assertEquals($file->id, $receivedFile->id);
                $this->assertEquals($newLabel, $label);

                if ($isEncrypted) {
                    $this->assertNotNull($encryptionKey);
                } else {
                    $this->assertNull($encryptionKey);
                }

                $storedFileInfo = $storeFileAction($newPath, $encryptionKey);

                $this->assertEquals($fileInfo, $storedFileInfo);

                return true;
            })
            ->once()
            ->andReturn($createdVersion);

        $this->service
            ->shouldReceive('storeFile')
            ->withArgs(function (
                mixed $receivedResource,
                string $receivedPath,
                ?string $receivedEncryptionKey,
                bool $useTemporaryFile
            ) use ($resource, $newPath, $isEncrypted, &$foundEncryptionKey) {
                $this->assertEquals($resource, $receivedResource);
                $this->assertEquals($newPath, $receivedPath);

                if ($isEncrypted) {
                    $this->assertNotNull($receivedEncryptionKey);
                    $this->assertEquals(
                        $foundEncryptionKey,
                        $receivedEncryptionKey
                    );
                } else {
                    $this->assertNull($receivedEncryptionKey);
                }

                $this->assertFalse($useTemporaryFile);

                return true;
            })
            ->once()
            ->andReturn($fileInfo);

        $returnedVersion = $this->service->createNewVersion(
            $file,
            $resource,
            $isEncrypted,
            $newLabel
        );

        $this->assertEquals($createdVersion, $returnedVersion);

        fclose($resource);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateVersionCreatesANewVersion(
        bool $isEncrypted
    ): void {
        $encryptionKey = $isEncrypted ? 'test-key' : null;

        $nextVersion = 55;

        $file = File::factory()->create(['next_version' => $nextVersion]);

        $newLabel = 'test-label';

        $storeFileActionCalled = false;

        $foundNewPath = null;

        $uploadContent = 'test content';
        $uploadFile = UploadedFile::fake()->createWithContent(
            'test.txt',
            $uploadContent
        );

        $fileInfo = new FileInfo(
            'test-path',
            $uploadFile->getMimeType(),
            $uploadFile->getSize(),
            md5($uploadContent)
        );

        $storeFileAction = function (
            string $newPath,
            ?string $receivedEncryptionKey
        ) use (
            &$storeFileActionCalled,
            &$foundNewPath,
            $uploadFile,
            $fileInfo,
            $encryptionKey
        ) {
            $storeFileActionCalled = true;

            $foundNewPath = $newPath;

            $this->storageFake->putFileAs('', $uploadFile, $foundNewPath);

            $this->assertEquals($encryptionKey, $receivedEncryptionKey);

            return $fileInfo;
        };

        $createdVersion = $this->service->createVersion(
            $file,
            $storeFileAction,
            $encryptionKey,
            $newLabel
        );

        $this->assertTrue($storeFileActionCalled);

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id,
            'label' => $newLabel,
            'version' => $nextVersion,
            'mime_type' => $fileInfo->mimeType,
            'storage_path' => $foundNewPath,
            'checksum' => $fileInfo->checksum,
            'bytes' => $fileInfo->size,
        ]);

        $this->assertInstanceOf(FileVersion::class, $createdVersion);
        $this->assertEquals($file->id, $createdVersion->file_id);
        $this->assertEquals($newLabel, $createdVersion->label);
        $this->assertEquals($encryptionKey, $createdVersion->encryption_key);

        $file->refresh();

        $this->assertEquals($nextVersion + 1, $file->next_version);
    }

    public function testCreateVersionFailsIfFileAlreadyExists(): void {
        $this->expectException(FileAlreadyExistsException::class);

        $newPath = Str::freezeUuids()->toString();
        $this->storageFake->put($newPath, 'test');

        $file = File::factory()->create();

        try {
            $this->service->createVersion($file, function (string $path) {
                $this->storageFake->put($path, 'test');

                return new FileInfo($path, 'text/plain', 4, md5('test'));
            });
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
            $this->service->createVersion($file, function (string $path) {
                $this->storageFake->put($path, 'test');

                return new FileInfo($path, 'text/plain', 4, md5('test'));
            });
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
            $this->service->createVersion($file, fn() => throw $exception);
        } catch (Exception $e) {
            $this->assertDatabaseMissing('file_versions', [
                'file_id' => $file->id,
            ]);

            throw $e;
        }
    }

    public function testUpdateLatestVersionReplacesTheFileForTheLatestVersion(): void {
        $content = fake()->text();

        $resource = $this->createStream($content);

        $file = File::factory()->create();

        $version = FileVersion::factory()
            ->for($file)
            ->encrypted()
            ->create(['mime_type' => 'application/json']);

        $fileInfo = new FileInfo(
            $version->storage_path,
            'text/plain',
            strlen($content),
            md5($content)
        );

        $this->service
            ->shouldReceive('storeFile')
            ->withArgs([
                $resource,
                $version->storage_path,
                $version->encryption_key,
                true, // useTemporaryFile
            ])
            ->once()
            ->andReturn($fileInfo);

        $this->storageFake->put($version->storage_path, $content);

        $result = $this->service->updateLatestVersion($file, $resource);

        $this->assertTrue($result);

        $this->assertDatabaseHas('file_versions', [
            'id' => $version->id,
            'file_id' => $file->id,
            'version' => $version->version,
            'mime_type' => $fileInfo->mimeType,
            'storage_path' => $version->storage_path,
            'checksum' => $fileInfo->checksum,
            'bytes' => $fileInfo->size,
        ]);

        fclose($resource);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testUpdateLatestVersionCreatesANewVersionIfAutoVersioningIsEnabledAndTheDelayHasPassed(
        bool $isEncrypted
    ): void {
        $content = fake()->text();
        $autoVersionHours = 1.5;

        $resource = $this->createStream($content);

        $file = File::factory()->create([
            'auto_version_hours' => $autoVersionHours,
        ]);

        $versionFactory = FileVersion::factory()->for($file);

        if ($isEncrypted) {
            $versionFactory = $versionFactory->encrypted();
        }

        $version = $versionFactory->create([
            'mime_type' => 'application/json',
            'created_at' => now()->subRealHours($autoVersionHours + 0.01),
        ]);

        $this->service
            ->shouldReceive('createNewVersion')
            ->withArgs([$file, $resource, $isEncrypted])
            ->once();

        $result = $this->service->updateLatestVersion($file, $resource);

        $this->assertFalse($result);

        fclose($resource);
    }

    public function testUpdateLatestVersionReplacesTheFileForTheLatestVersionIfAutoVersioningIsEnabledAndTheDelayHasNotPassed(): void {
        $content = fake()->text();
        $autoVersionHours = 1.5;

        $resource = $this->createStream($content);

        $file = File::factory()->create([
            'auto_version_hours' => $autoVersionHours,
        ]);

        $version = FileVersion::factory()
            ->for($file)
            ->encrypted()
            ->create([
                'mime_type' => 'application/json',
                'created_at' => now()->subRealHours($autoVersionHours - 0.01),
            ]);

        $this->service
            ->shouldReceive('storeFile')
            ->withArgs([
                $resource,
                $version->storage_path,
                $version->encryption_key,
                true, // useTemporaryFile
            ])
            ->once()
            ->andReturn(
                new FileInfo(
                    $version->storage_path,
                    'text/plain',
                    strlen($content),
                    md5($content)
                )
            );

        $this->storageFake->put($version->storage_path, $content);

        $result = $this->service->updateLatestVersion($file, $resource);

        $this->assertTrue($result);

        fclose($resource);
    }

    public function testUpdateLatestVersionFailsIfFileHasNoVersions(): void {
        $this->expectException(NoVersionFoundException::class);

        $resource = $this->createStream('test');

        $file = File::factory()->create();

        try {
            $this->service->updateLatestVersion($file, $resource);
        } catch (Exception $e) {
            fclose($resource);

            throw $e;
        }

        fclose($resource);
    }

    public function testUpdateLatestVersionFailsAndDoesntStoreTheUpdateIfStoreFileFails(): void {
        $this->expectException(FileWriteException::class);

        $resource = $this->createStream('test');

        $file = File::factory()->create();

        $version = FileVersion::factory()
            ->for($file)
            ->create([
                'mime_type' => 'application/json',
            ]);

        $this->service
            ->shouldReceive('storeFile')
            ->once()
            ->andThrow(new FileWriteException());

        try {
            $this->service->updateLatestVersion($file, $resource);
        } catch (Exception $e) {
            $this->assertDatabaseHas('file_versions', [
                'id' => $version->id,
                'file_id' => $file->id,
                'version' => $version->version,
                'mime_type' => $version->mime_type,
                'storage_path' => $version->storage_path,
                'checksum' => $version->checksum,
                'bytes' => $version->bytes,
            ]);

            fclose($resource);

            throw $e;
        }

        fclose($resource);
    }

    public function testStoreFileSavesTheGivenFileToTheGivenPathAndReturnsTheFileInfo(): void {
        $path = 'test-path';

        $content = fake()->text();
        $resource = $this->createStream($content);

        $this->storageFake->assertMissing($path);

        $fileInfo = $this->service->storeFile(
            $resource,
            $path,
            encryptionKey: null,
            useTemporaryFile: false
        );

        $this->storageFake->assertExists($path);

        $this->assertEquals($content, $this->storageFake->get($path));

        $this->assertEquals($path, $fileInfo->path);
        $this->assertEquals('text/plain', $fileInfo->mimeType);
        $this->assertEquals(strlen($content), $fileInfo->size);
        $this->assertEquals(md5($content), $fileInfo->checksum);

        fclose($resource);
    }

    public function testStoreFileSavesAndEncryptsTheGivenFileToTheGivenPathAndReturnsTheFileInfo(): void {
        $encryptionKey = 'test-key';

        $path = 'test-path';

        $content = fake()->text();
        $resource = $this->createStream($content);

        $this->encryptionServiceMock
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

        $fileInfo = $this->service->storeFile(
            $resource,
            $path,
            encryptionKey: $encryptionKey,
            useTemporaryFile: false
        );

        $expectedFileInfo = FileInfo::fromResource($path, $resource);

        $this->assertEquals($expectedFileInfo->path, $fileInfo->path);
        $this->assertEquals($expectedFileInfo->mimeType, $fileInfo->mimeType);
        $this->assertEquals($expectedFileInfo->size, $fileInfo->size);
        $this->assertEquals($expectedFileInfo->checksum, $fileInfo->checksum);

        fclose($resource);
    }

    public function testStoreFileUsesATemporaryFileAndThenMovesItToTheDestinationIfRequested(): void {
        $path = 'test-path';

        $content = fake()->text();

        $resource = $this->createStream($content);

        $fileInfo = $this->service->storeFile(
            $resource,
            $path,
            encryptionKey: null,
            useTemporaryFile: true
        );

        $this->storageFake->assertExists($path);
        $this->storageFake->assertMissing(
            $path . $this->service->getTmpSuffix()
        );

        $this->assertEquals($content, $this->storageFake->get($path));

        $this->assertEquals($path, $fileInfo->path);

        fclose($resource);
    }

    public function testStoreFileWithEncryptionUsesATemporaryFileAndThenMovesItToTheDestinationIfRequested(): void {
        $path = 'test-path';

        $content = fake()->text();
        $encryptedContent = fake()->text();

        $resource = $this->createStream($content);

        $this->encryptionServiceMock
            ->shouldReceive('encrypt')
            ->withArgs(function (
                string $key,
                mixed $inputResource,
                mixed $outputResource
            ) use ($path, $encryptedContent, &$tmpPath) {
                $tmpPath = stream_get_meta_data($outputResource)['uri'];

                $this->assertNotEquals(
                    $this->storageFake->path($path),
                    $tmpPath
                );

                fwrite($outputResource, $encryptedContent);

                return true;
            })
            ->once();

        $fileInfo = $this->service->storeFile(
            $resource,
            $path,
            encryptionKey: 'test-key',
            useTemporaryFile: true
        );

        $this->storageFake->assertExists($path);
        $this->assertFileDoesNotExist($tmpPath);

        $this->assertEquals($encryptedContent, $this->storageFake->get($path));

        $this->assertEquals($path, $fileInfo->path);

        fclose($resource);
    }

    public function testStoreFileFailsIfTheEncryptionFails(): void {
        $this->expectException(FileWriteException::class);

        $path = 'test-path';

        $resource = $this->createStream(fake()->text());

        $this->encryptionServiceMock
            ->shouldReceive('encrypt')
            ->once()
            ->andThrow(new StreamWriteException());

        try {
            $this->service->storeFile(
                $resource,
                $path,
                encryptionKey: 'test-key',
                useTemporaryFile: false
            );
        } catch (Exception $e) {
            fclose($resource);

            throw $e;
        }

        fclose($resource);
    }

    public function testStoreFileFailsIfTemporaryFileCantBeMoved(): void {
        $this->expectException(FileWriteException::class);

        $this->storageFake->shouldReceive('move')->andReturnFalse();

        $path = 'test-path';
        $tmpPath = $path . $this->service->getTmpSuffix();

        $resource = $this->createStream(fake()->text());

        try {
            $this->service->storeFile(
                $resource,
                $path,
                encryptionKey: null,
                useTemporaryFile: true
            );
        } catch (Exception $e) {
            $this->storageFake->assertMissing($tmpPath);

            fclose($resource);

            $this->assertFalse($this->storageFake->exists($path));

            throw $e;
        }

        fclose($resource);
    }

    public function testStoreFileFailsIfFileCantBeStoredWithoutEncryption(): void {
        $this->expectException(FileWriteException::class);

        $this->storageFake->shouldReceive('writeStream')->andReturnFalse();

        $path = 'test-path';

        $resource = $this->createStream(fake()->text());

        try {
            $this->service->storeFile(
                $resource,
                $path,
                encryptionKey: null,
                useTemporaryFile: false
            );
        } catch (Exception $e) {
            $this->storageFake->assertMissing($path);
            fclose($resource);

            throw $e;
        }

        fclose($resource);
    }

    public function testWriteContentsToStreamWritesTheContentsOfTheUnencryptedFileVersionToTheGivenStream(): void {
        $content = fake()->text();

        $version = FileVersion::factory()->create([
            'bytes' => strlen($content),
        ]);

        $this->storageFake->put($version->storage_path, $content);

        $stream = fopen('php://memory', 'r+');

        $this->service->writeContentsToStream($version, $stream);

        rewind($stream);

        $this->assertEquals($content, stream_get_contents($stream));

        fclose($stream);
    }

    public function testWriteContentsToStreamWritesTheContentsOfTheEncryptedFileVersionToTheGivenStream(): void {
        $content = fake()->text();

        $file = File::factory()->create();

        $version = FileVersion::factory()
            ->for($file)
            ->encrypted()
            ->create(['bytes' => strlen($content)]);

        $this->storageFake->put(
            $version->storage_path,
            '==encrypted-content=='
        );

        $this->encryptionServiceMock
            ->shouldReceive('decrypt')
            ->withArgs(function (
                string $key,
                mixed $inputResource,
                mixed $outputResource
            ) use ($version, $content) {
                $this->assertEquals($version->encryption_key, $key);

                fwrite($outputResource, $content);

                return true;
            })
            ->once();

        $stream = fopen('php://memory', 'r+');

        $this->service->writeContentsToStream($version, $stream);

        rewind($stream);

        $this->assertEquals($content, stream_get_contents($stream));

        fclose($stream);
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
                FileVersion $receivedVersion,
                mixed $resource
            ) use ($version, $content) {
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
            $version->mime_type,
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
        ?string $encryptionKey = null,
        ?string $label = null
    ): FileVersion {
        return parent::createVersion(
            $file,
            $storeFileAction,
            $encryptionKey,
            $label
        );
    }

    public function storeFile(
        mixed $resource,
        string $path,
        ?string $encryptionKey,
        bool $useTemporaryFile
    ): FileInfo {
        return parent::storeFile(
            $resource,
            $path,
            $encryptionKey,
            $useTemporaryFile
        );
    }

    public function getTmpSuffix(): string {
        return static::TMP_SUFFIX;
    }
}
