<?php

namespace Tests\Unit\WebDav\Filesystem;

use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\WebDavUser;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;
use App\WebDav\Filesystem\VirtualAllFilesDirectory;
use App\WebDav\Filesystem\VirtualUuidFileContainer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class VirtualAllFilesDirectoryTest extends TestCase {
    use LazilyRefreshDatabase;

    protected VirtualAllFilesDirectoryTestClass $virtualDirectory;

    protected AuthBackend|MockInterface $authBackend;
    protected FileVersionService|MockInterface $fileVersionService;

    protected WebDavUser $webDavUser;

    protected function setUp(): void {
        parent::setUp();

        /**
         * @var AuthBackend|MockInterface
         */
        $this->authBackend = Mockery::mock(AuthBackend::class);
        $this->fileVersionService = Mockery::mock(FileVersionService::class);

        $this->virtualDirectory = new VirtualAllFilesDirectoryTestClass(
            $this->authBackend,
            $this->fileVersionService
        );

        $this->webDavUser = WebDavUser::factory()->create();

        $this->authBackend
            ->shouldReceive('getAuthenticatedWebDavUser')
            ->andReturn($this->webDavUser);
    }

    public function testGetNameReturnsTheBaseName(): void {
        $this->assertEquals(
            VirtualAllFilesDirectory::BASE_NAME,
            $this->virtualDirectory->getName()
        );
    }

    public function testLoadChildrenReturnsTheFilesOfTheAuthenticatedUserIgnoringAnyDirectoryStructure(): void {
        $directory = Directory::factory()
            ->for($this->webDavUser->user)
            ->create();

        $files = collect();

        $files->push(
            ...File::factory(5)
                ->hasAttached($this->webDavUser)
                ->has(FileVersion::factory(), 'versions')
                ->create()
                ->all()
        );

        $files->push(
            ...File::factory(5)
                ->hasAttached($this->webDavUser)
                ->for($directory)
                ->has(FileVersion::factory(), 'versions')
                ->create()
                ->all()
        );

        $fileIds = $files->map(fn(File $file) => $file->id);

        $children = $this->virtualDirectory->loadChildren();

        $this->assertCount(count($files), $children);

        foreach ($children as $child) {
            $this->assertInstanceOf(VirtualUuidFileContainer::class, $child);

            $this->assertContains($child->file->id, $fileIds);
        }
    }

    public function testLoadChildrenReturnsOnlyFilesWithVersions(): void {
        $noVersionFiles = File::factory(5)
            ->hasAttached($this->webDavUser)
            ->create();

        $children = $this->virtualDirectory->loadChildren();

        $this->assertCount(0, $children);
    }

    public function testLoadChildrenReturnsOnlyFilesWhichTheWebDavUserCanSee(): void {
        $otherWebDavUser = WebDavUser::factory()
            ->for($this->webDavUser->user)
            ->create();

        $otherFiles = File::factory(5)
            ->hasAttached($otherWebDavUser)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $children = $this->virtualDirectory->loadChildren();

        $this->assertCount(0, $children);
    }
}

class VirtualAllFilesDirectoryTestClass extends VirtualAllFilesDirectory {
    public function loadChildren(): array {
        return parent::loadChildren();
    }
}
