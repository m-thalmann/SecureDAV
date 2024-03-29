<?php

namespace Tests\Unit\WebDav\Filesystem;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\WebDavUser;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;
use App\WebDav\Filesystem\VirtualFile;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Sabre\DAV;
use Tests\TestCase;

class VirtualFileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected VirtualFileTestClass $virtualFile;
    protected File $file;

    protected AuthBackend|MockInterface $authBackend;
    protected FileVersionService|MockInterface $fileVersionService;

    protected function setUp(): void {
        parent::setUp();

        /**
         * @var AuthBackend|MockInterface
         */
        $this->authBackend = Mockery::mock(AuthBackend::class);
        $this->fileVersionService = Mockery::mock(FileVersionService::class);

        $this->file = File::factory()
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $this->virtualFile = new VirtualFileTestClass(
            $this->authBackend,
            $this->fileVersionService,
            $this->file
        );
    }

    public function testConstructorInitializesFileVersion(): void {
        $this->assertEquals(
            $this->file->latestVersion->id,
            $this->virtualFile->getFileVersion()->id
        );
    }

    public function testGetNameReturnsTheFileName(): void {
        $this->assertEquals($this->file->name, $this->virtualFile->getName());
    }

    public function testGetReturnsAResourceWithTheContentsOfTheFile(): void {
        $contents = 'test contents';

        $this->fileVersionService
            ->shouldReceive('writeContentsToStream')
            ->withArgs(function (FileVersion $fileVersion, mixed $stream) {
                $this->assertEquals($this->file->latestVersion, $fileVersion);
                $this->assertIsResource($stream);

                return true;
            })
            ->once()
            ->andReturnUsing(function (
                FileVersion $fileVersion,
                mixed $stream
            ) use ($contents) {
                fwrite($stream, $contents);
            });

        $outputStream = $this->virtualFile->get();

        $this->assertEquals($contents, stream_get_contents($outputStream));

        fclose($outputStream);
    }

    public function testPutUpdatesTheFileOfTheVersionWithTheProvidedData(): void {
        $contents = 'test contents';

        $inputResource = $this->createStream($contents);

        $webDavUser = WebDavUser::factory()->create([
            'readonly' => false,
        ]);

        $this->authBackend
            ->shouldReceive('getAuthenticatedWebDavUser')
            ->once()
            ->andReturn($webDavUser);

        $this->fileVersionService
            ->shouldReceive('updateLatestVersion')
            ->withArgs(function (File $file, mixed $resource) use ($contents) {
                $this->assertEquals($this->file, $file);
                $this->assertIsResource($resource);

                // the resource is copied since the original resource will be an input stream (not seekable)
                $this->assertEquals($contents, stream_get_contents($resource));

                return true;
            })
            ->once();

        $this->virtualFile->put($inputResource);

        fclose($inputResource);
    }

    public function testPutFailsIfTheWebDavUserIsReadonly(): void {
        $contents = 'test contents';

        $resource = $this->createStream($contents);

        $webDavUser = WebDavUser::factory()->create([
            'readonly' => true,
        ]);

        $this->authBackend
            ->shouldReceive('getAuthenticatedWebDavUser')
            ->once()
            ->andReturn($webDavUser);

        $this->fileVersionService
            ->shouldReceive('updateLatestVersion')
            ->never();

        $this->expectException(DAV\Exception\Forbidden::class);

        try {
            $this->virtualFile->put($resource);
        } catch (Exception $e) {
            throw $e;
        } finally {
            fclose($resource);
        }
    }

    public function testPutFailsIfTheUploadedFileIsTooLarge(): void {
        config(['core.files.max_file_size_bytes' => 1]);

        $contents = 'test contents';

        $resource = $this->createStream($contents);

        $webDavUser = WebDavUser::factory()->create([
            'readonly' => false,
        ]);

        $this->authBackend
            ->shouldReceive('getAuthenticatedWebDavUser')
            ->once()
            ->andReturn($webDavUser);

        $this->fileVersionService
            ->shouldReceive('updateLatestVersion')
            ->never();

        $this->expectException(DAV\Exception\InsufficientStorage::class);

        try {
            $this->virtualFile->put($resource);
        } catch (Exception $e) {
            throw $e;
        } finally {
            fclose($resource);
        }
    }

    public function testGetSizeReturnsTheFileSizeOfTheVersion(): void {
        $this->assertEquals(
            $this->file->latestVersion->bytes,
            $this->virtualFile->getSize()
        );
    }

    public function testGetETagReturnsTheChecksumOfTheVersion(): void {
        $this->assertEquals(
            "\"{$this->file->latestVersion->checksum}\"",
            $this->virtualFile->getETag()
        );
    }

    public function testGetContentTypeReturnsTheMimeTypeOfTheFile(): void {
        $this->assertEquals(
            $this->file->latestVersion->mime_type,
            $this->virtualFile->getContentType()
        );
    }

    public function testGetLastModifiedReturnsTheLastModifiedTimeOfTheVersion(): void {
        $this->assertEqualsWithDelta(
            $this->file->latestVersion->created_at->timestamp,
            $this->virtualFile->getLastModified(),
            delta: 1
        );
    }
}

class VirtualFileTestClass extends VirtualFile {
    public function getFileVersion(): FileVersion {
        return $this->fileVersion;
    }
}
