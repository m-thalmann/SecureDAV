<?php

namespace Tests\Unit\WebDav\Filesystem;

use App\WebDav\Filesystem\AbstractVirtualDirectory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AbstractVirtualDirectoryTest extends TestCase {
    protected AbstractVirtualDirectory|MockInterface $directory;

    protected function setUp(): void {
        parent::setUp();

        /**
         * @var AbstractVirtualDirectory|MockInterface
         */
        $this->directory = Mockery::mock(AbstractVirtualDirectory::class);

        $this->directory->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testGetChildrenLoadsTheChildrenTheFirstTime(): void {
        $children = [Mockery::mock(), Mockery::mock(), Mockery::mock()];

        $this->directory
            ->shouldReceive('loadChildren')
            ->once()
            ->andReturn($children);

        $receivedChildren = $this->directory->getChildren();

        $this->assertEquals($children, $receivedChildren);
    }

    public function testGetChildrenDoesNotLoadTheChildrenTheSecondTime(): void {
        $children = [Mockery::mock(), Mockery::mock(), Mockery::mock()];

        $this->directory
            ->shouldReceive('loadChildren')
            ->once()
            ->andReturn($children);

        $firstChildren = $this->directory->getChildren();
        $secondChildren = $this->directory->getChildren();

        $this->assertEquals($children, $firstChildren);
    }
}
