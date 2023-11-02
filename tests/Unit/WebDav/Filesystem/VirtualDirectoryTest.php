<?php

namespace Tests\Unit\WebDav\Filesystem;

use App\Models\AccessGroup;
use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;
use App\WebDav\Filesystem\VirtualDirectory;
use App\WebDav\Filesystem\VirtualFile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class VirtualDirectoryTest extends TestCase {
    use LazilyRefreshDatabase;

    protected VirtualDirectoryTestClass|MockInterface $virtualDirectory;

    protected AuthBackend|MockInterface $authBackend;
    protected FileVersionService|MockInterface $fileVersionService;

    protected AccessGroup $accessGroup;

    protected function setUp(): void {
        parent::setUp();

        /**
         * @var AuthBackend|MockInterface
         */
        $this->authBackend = Mockery::mock(AuthBackend::class);
        $this->fileVersionService = Mockery::mock(FileVersionService::class);

        $this->accessGroup = AccessGroup::factory()->create();

        $this->authBackend
            ->shouldReceive('getAuthenticatedUser')
            ->andReturn($this->accessGroup->user);

        $this->authBackend
            ->shouldReceive('getAuthenticatedAccessGroup')
            ->andReturn($this->accessGroup);
    }

    public function testGetNameReturnsTheDirectoryName(): void {
        $directory = Directory::factory()->create();

        $this->createVirtualDirectory($directory);

        $this->assertEquals(
            $directory->name,
            $this->virtualDirectory->getName()
        );
    }

    public function testGetNameReturnsBaseNameWhenDirectoryIsNull(): void {
        $this->createVirtualDirectory(null);

        $this->assertEquals(
            VirtualDirectory::BASE_NAME,
            $this->virtualDirectory->getName()
        );
    }

    public function testLoadChildrenReturnsDirectoriesAndFiles(): void {
        $this->createVirtualDirectory(null);

        // these should be actual VirtualDirectory and VirtualFile instances
        $directories = ['dir1', 'dir2'];

        $files = ['file1', 'file2'];
        $this->virtualDirectory
            ->shouldReceive('loadDirectories')
            ->andReturn($directories);

        $this->virtualDirectory->shouldReceive('loadFiles')->andReturn($files);

        $children = $this->virtualDirectory->loadChildren();

        $this->assertEquals([...$directories, ...$files], $children);
    }

    public function testLoadDirectoriesReturnsDirectoriesInRootDirectory(): void {
        $this->createVirtualDirectory(null);

        $directories = Directory::factory(5)
            ->for($this->accessGroup->user)
            ->create();

        // should not be included
        $otherDirectories = Directory::factory(5)
            ->for($this->accessGroup->user)
            ->for($directories->first(), 'parentDirectory')
            ->create();

        $directoryIds = $directories->map(
            fn(Directory $directory) => $directory->id
        );

        $loadedDirectories = $this->virtualDirectory->loadDirectories();

        $this->assertCount(count($directories), $loadedDirectories);

        foreach ($loadedDirectories as $directory) {
            $this->assertInstanceOf(VirtualDirectory::class, $directory);

            $this->assertContains($directory->directory->id, $directoryIds);
        }
    }

    public function testLoadDirectoriesReturnsDirectoriesInGivenDirectory(): void {
        $directory = Directory::factory()
            ->for($this->accessGroup->user)
            ->create();

        $this->createVirtualDirectory($directory);

        $directories = Directory::factory(5)
            ->for($this->accessGroup->user)
            ->for($directory, 'parentDirectory')
            ->create();

        // should not be included
        $otherDirectory = Directory::factory()
            ->for($this->accessGroup->user)
            ->create();

        // should not be included
        $otherDirectories = Directory::factory(5)
            ->for($this->accessGroup->user)
            ->for($otherDirectory, 'parentDirectory')
            ->create();

        $directoryIds = $directories->map(
            fn(Directory $directory) => $directory->id
        );

        $loadedDirectories = $this->virtualDirectory->loadDirectories();

        $this->assertCount(count($directories), $loadedDirectories);

        foreach ($loadedDirectories as $directory) {
            $this->assertInstanceOf(VirtualDirectory::class, $directory);

            $this->assertContains($directory->directory->id, $directoryIds);
        }
    }

    public function testLoadFilesReturnsFilesInRootDirectory(): void {
        $this->createVirtualDirectory(null);

        $files = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->create(['directory_id' => null]);

        $otherDirectory = Directory::factory()
            ->for($this->accessGroup->user)
            ->create();

        // should not be included
        $otherFiles = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->for($otherDirectory)
            ->create();

        $fileIds = $files->map(fn(File $file) => $file->id);

        $loadedFiles = $this->virtualDirectory->loadFiles();

        $this->assertCount(count($files), $loadedFiles);

        foreach ($loadedFiles as $file) {
            $this->assertInstanceOf(VirtualFile::class, $file);

            $this->assertContains($file->file->id, $fileIds);
        }
    }

    public function testLoadFilesReturnsFilesInGivenDirectory(): void {
        $directory = Directory::factory()
            ->for($this->accessGroup->user)
            ->create();

        $this->createVirtualDirectory($directory);

        $files = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->for($directory)
            ->create();

        $otherDirectory = Directory::factory()
            ->for($this->accessGroup->user)
            ->create();

        // should not be included
        $otherFiles = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->for($otherDirectory)
            ->create();

        $fileIds = $files->map(fn(File $file) => $file->id);

        $loadedFiles = $this->virtualDirectory->loadFiles();

        $this->assertCount(count($files), $loadedFiles);

        foreach ($loadedFiles as $file) {
            $this->assertInstanceOf(VirtualFile::class, $file);

            $this->assertContains($file->file->id, $fileIds);
        }
    }

    public function testLoadFilesReturnsOnlyFilesWithVersions(): void {
        $this->createVirtualDirectory(null);

        $files = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->create(['directory_id' => null]);

        // should not be included
        $noVersionFiles = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->create(['directory_id' => null]);

        $fileIds = $files->map(fn(File $file) => $file->id);

        $loadedFiles = $this->virtualDirectory->loadFiles();

        $this->assertCount(count($files), $loadedFiles);

        foreach ($loadedFiles as $file) {
            $this->assertInstanceOf(VirtualFile::class, $file);

            $this->assertContains($file->file->id, $fileIds);
        }
    }

    public function testLoadFilesReturnsOnlyFilesWhichTheAccessGroupCanSee(): void {
        $this->createVirtualDirectory(null);

        $files = File::factory(5)
            ->hasAttached($this->accessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->create(['directory_id' => null]);

        $otherAccessGroup = AccessGroup::factory()
            ->for($this->accessGroup->user)
            ->create();

        // should not be included
        $otherFiles = File::factory(5)
            ->hasAttached($otherAccessGroup)
            ->has(FileVersion::factory(), 'versions')
            ->create(['directory_id' => null]);

        $fileIds = $files->map(fn(File $file) => $file->id);

        $loadedFiles = $this->virtualDirectory->loadFiles();

        $this->assertCount(count($files), $loadedFiles);

        foreach ($loadedFiles as $file) {
            $this->assertInstanceOf(VirtualFile::class, $file);

            $this->assertContains($file->file->id, $fileIds);
        }
    }

    protected function createVirtualDirectory(?Directory $directory): void {
        /**
         * @var VirtualDirectoryTestClass|MockInterface
         */
        $this->virtualDirectory = Mockery::mock(
            VirtualDirectoryTestClass::class,
            [$this->authBackend, $this->fileVersionService, $directory]
        );

        $this->virtualDirectory->makePartial();
    }
}

class VirtualDirectoryTestClass extends VirtualDirectory {
    public function loadChildren(): array {
        return parent::loadChildren();
    }

    public function loadDirectories(): array {
        return parent::loadDirectories();
    }

    public function loadFiles(): array {
        return parent::loadFiles();
    }
}
