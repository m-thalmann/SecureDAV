<?php

namespace Tests\Unit\Support;

use App\Support\FileInfo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class FileInfoTest extends TestCase {
    protected FilesystemAdapter $storageFake;

    protected function setUp(): void {
        parent::setUp();

        $this->storageFake = Storage::fake('files');
    }

    public function testFromStorageCreatesNewInstance(): void {
        $path = 'test.txt';
        $content = 'Hello World';

        $this->storageFake->put($path, $content);

        $fileInfo = FileInfo::fromStorage($this->storageFake, $path);

        $this->assertSame($this->storageFake->path($path), $fileInfo->path);
        $this->assertSame('text/plain', $fileInfo->mimeType);
        $this->assertSame(strlen($content), $fileInfo->size);
        $this->assertSame(md5($content), $fileInfo->checksum);
    }

    public function testFromStorageThrowsExceptionIfFileDoesNotExist(): void {
        $this->expectException(InvalidArgumentException::class);

        FileInfo::fromStorage($this->storageFake, 'test.txt');
    }
}
