<?php

namespace Tests\Unit\WebDav\Filesystem;

use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;
use App\WebDav\Filesystem\VirtualFile;
use App\WebDav\Filesystem\VirtualUuidFileContainer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class VirtualUuidFileContainerTest extends TestCase {
    use LazilyRefreshDatabase;

    protected VirtualUuidFileContainerTestClass $virtualUuidFileContainer;
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

        $this->virtualUuidFileContainer = new VirtualUuidFileContainerTestClass(
            $this->authBackend,
            $this->fileVersionService,
            $this->file
        );
    }

    public function testGetNameReturnsTheUuidOfTheFileAsName(): void {
        $this->assertEquals(
            $this->file->uuid,
            $this->virtualUuidFileContainer->getName()
        );
    }

    public function testLoadChildrenReturnsArrayWithTheSingleVirtualFile(): void {
        $children = $this->virtualUuidFileContainer->loadChildren();

        $this->assertCount(1, $children);

        $file = $children[0];

        $this->assertInstanceOf(VirtualFile::class, $file);

        $this->assertEquals($this->file->id, $file->file->id);
    }
}

class VirtualUuidFileContainerTestClass extends VirtualUuidFileContainer {
    public function loadChildren(): array {
        return parent::loadChildren();
    }
}
