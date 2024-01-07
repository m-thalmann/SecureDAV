<?php

namespace Tests\Unit\Support;

use App\Support\FileInfo;
use PHPUnit\Framework\TestCase;

class FileInfoTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
    }

    public function testFromResourceCreatesNewInstance(): void {
        $path = 'test.txt';
        $content = 'Hello World';

        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        $fileInfo = FileInfo::fromResource($path, $resource);

        $this->assertSame($path, $fileInfo->path);
        $this->assertSame('text/plain', $fileInfo->mimeType);
        $this->assertSame(strlen($content), $fileInfo->size);
        $this->assertSame(md5($content), $fileInfo->checksum);
    }
}
