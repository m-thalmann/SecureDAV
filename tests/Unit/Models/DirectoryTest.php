<?php

namespace Tests\Unit\Models;

use App\Models\Directory;
use App\Models\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testBreadcrumbsAttributeReturnsExpectedArrayOfDirectories(): void {
        $grandparentDirectory = Directory::factory()->create();
        $parentDirectory = Directory::factory()
            ->for($grandparentDirectory, 'parentDirectory')
            ->create();
        $directory = Directory::factory()
            ->for($parentDirectory, 'parentDirectory')
            ->create();

        $breadcrumbs = $directory->breadcrumbs;

        $breadcrumbIds = array_map(
            fn(Directory $directory) => $directory->id,
            $breadcrumbs
        );

        $this->assertEquals(
            [$grandparentDirectory->id, $parentDirectory->id, $directory->id],
            $breadcrumbIds
        );
    }

    public function testBreadcrumbsAttributeReturnsExpectedArrayOfDirectoriesWhenDirectoryIsRoot(): void {
        $directory = Directory::factory()->create();

        $breadcrumbs = $directory->breadcrumbs;

        $breadcrumbIds = array_map(
            fn(Directory $directory) => $directory->id,
            $breadcrumbs
        );

        $this->assertEquals([$directory->id], $breadcrumbIds);
    }

    public function testBreadcrumbsAttributeReturnsCachedValue(): void {
        $computedBreadcrumbs = [];

        $directory = new DirectoryModelTestClass();
        $directory->setComputedBreadcrumbsAttribute($computedBreadcrumbs);

        $this->assertEquals($computedBreadcrumbs, $directory->breadcrumbs);
    }

    public function testIsEmptyAttributeReturnsTrueWhenDirectoryHasNoFilesOrSubdirectories(): void {
        $directory = Directory::factory()->create();

        $this->assertTrue($directory->isEmpty);
    }

    public function testIsEmptyAttributeReturnsFalseWhenDirectoryHasFiles(): void {
        $directory = Directory::factory()
            ->has(File::factory())
            ->create();

        $this->assertFalse($directory->isEmpty);
    }

    public function testIsEmptyAttributeReturnsFalseWhenDirectoryHasSubdirectories(): void {
        $directory = Directory::factory()
            ->has(Directory::factory())
            ->create();

        $this->assertFalse($directory->isEmpty);
    }

    public function testWebdavUrlAttributeReturnsExpectedUrl(): void {
        $grandparentDirectory = Directory::factory()->create([
            'name' => 'grandparent',
        ]);
        $parentDirectory = Directory::factory()
            ->for($grandparentDirectory, 'parentDirectory')
            ->create(['name' => 'parent']);
        $directory = Directory::factory()
            ->for($parentDirectory, 'parentDirectory')
            ->create(['name' => 'directory']);

        $this->assertEquals(
            route('webdav.directories', 'grandparent/parent/directory'),
            $directory->webdavUrl
        );
    }
}

class DirectoryModelTestClass extends Directory {
    public function setComputedBreadcrumbsAttribute(?array $value): void {
        $this->computedBreadcrumbs = $value;
    }
}
