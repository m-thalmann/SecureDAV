<?php

namespace Tests\Unit\Models;

use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FileTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testIsLatestVersionEncryptedAttributeReturnsTrueWhenLatestFileVersionIsEncrypted(): void {
        $file = File::factory()->create();
        $fileVersions = FileVersion::factory(3)
            ->for($file)
            ->create([]);

        $latestFileVersion = FileVersion::factory()
            ->for($file)
            ->create([
                'encryption_key' => 'encrypted',
            ]);

        $file->refresh();

        $this->assertTrue($file->isLatestVersionEncrypted);
    }

    public function testIsLatestVersionEncryptedAttributeReturnsFalseWhenLatestFileVersionIsNotEncrypted(): void {
        $file = File::factory()->create();
        $fileVersions = FileVersion::factory(3)
            ->for($file)
            ->create([
                'encryption_key' => 'encrypted',
            ]);

        $latestFileVersion = FileVersion::factory()
            ->for($file)
            ->create([
                'encryption_key' => null,
            ]);

        $file->refresh();

        $this->assertFalse($file->isLatestVersionEncrypted);
    }

    public function testIsLatestVersionEncryptedAttributeReturnsFalseWhenNoFileVersionsExist(): void {
        $file = File::factory()->create();

        $this->assertFalse($file->isLatestVersionEncrypted);
    }

    /**
     * @dataProvider extensionAttributeDataProvider
     */
    public function testExtensionAttributeReturnsExpectedValue(
        string $filename,
        ?string $expectedExtension
    ): void {
        $file = File::factory()->create([
            'name' => $filename,
        ]);

        $this->assertEquals($expectedExtension, $file->extension);
    }

    public function testFileLastUpdatedAtAttributeReturnsLastUpdatedAtOfLatestVersion(): void {
        $file = File::factory()->create();
        $fileVersions = FileVersion::factory(3)
            ->for($file)
            ->create([
                'file_updated_at' => now()->subDays(3),
            ]);

        $latestFileVersion = FileVersion::factory()
            ->for($file)
            ->create([
                'file_updated_at' => now()->subDays(1),
            ]);

        $file->refresh();

        $this->assertEquals(
            $latestFileVersion->file_updated_at,
            $file->fileLastUpdatedAt
        );
    }

    public function testFileLastUpdatedAtAttributeReturnsNullWhenNoFileVersionsExist(): void {
        $file = File::factory()->create();

        $this->assertNull($file->fileLastUpdatedAt);
    }

    public function testFileIconAttributeReturnsExpectedValue(): void {
        $file = File::factory()->create([
            'name' => 'file.txt',
        ]);

        // uses the getFileIconForExtension helper function
        $icon = $file->fileIcon;

        $this->assertEquals('fa-solid fa-file-word text-blue-600', $icon);
    }

    public function testFileIconAttributeReturnsExpectedValueForHiddenFiles(): void {
        $file = File::factory()->create([
            'name' => '.file.txt',
        ]);

        // uses the getFileIconForExtension helper function
        $icon = $file->fileIcon;

        $this->assertStringContainsString('opacity-20', $icon);
    }

    public function testWebdavUrlAttributeReturnsExpectedValue(): void {
        $file = File::factory()->create([
            'uuid' => 'uuid',
            'name' => 'file.txt',
        ]);

        $url = $file->webdavUrl;

        $this->assertEquals(route('webdav.files', ['uuid', 'file.txt']), $url);
    }

    public function testMoveMovesFileToNewDirectory(): void {
        $file = File::factory()->create();

        $newDirectory = Directory::factory()
            ->for($file->user)
            ->create(['name' => 'new-directory']);

        $file->move($newDirectory);

        $this->assertEquals($newDirectory->id, $file->directory_id);
    }

    public function testMoveThrowsValidationExceptionWhenFileNameExistsInDirectory(): void {
        $file = File::factory()->create();

        $newDirectory = Directory::factory()
            ->for($file->user)
            ->create(['name' => 'new-directory']);

        $fileInDirectory = File::factory()
            ->for($newDirectory, 'directory')
            ->for($file->user)
            ->create([
                'name' => $file->name,
            ]);

        $this->expectException(ValidationException::class);

        $file->move($newDirectory);
    }

    public function testMoveThrowsValidationExceptionWhenDirectoryWithSameNameExistsInDirectory(): void {
        $file = File::factory()->create();

        $newDirectory = Directory::factory()
            ->for($file->user)
            ->create(['name' => 'new-directory']);

        $otherDirectory = Directory::factory()
            ->for($newDirectory, 'parentDirectory')
            ->for($file->user)
            ->create(['name' => $file->name]);

        $this->expectException(ValidationException::class);

        $file->move($newDirectory);
    }

    public function testMoveMovesAndDoesNotSaveChangesAutomatically(): void {
        $file = File::factory()->create(['directory_id' => null]);

        $newDirectory = Directory::factory()
            ->for($file->user)
            ->create(['name' => 'new-directory']);

        $file->move($newDirectory);

        $file->refresh();

        $this->assertNull($file->directory_id);
    }

    public static function extensionAttributeDataProvider(): array {
        return [
            ['file.txt', 'txt'],
            ['file', null],
            ['file.', null],
            ['.file', null],
            ['file.tar.gz', 'gz'],
            ['.file.tar.gz', 'gz'],
        ];
    }
}
