<?php

namespace Tests\Unit\Commands;

use App\Models\FileVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CleanFileStorageTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testItDeletesFilesThatHaveNoVersionAndWritesLog(): void {
        $logMock = Log::partialMock();

        $logMock
            ->shouldReceive('info')
            ->once()
            ->withArgs(function (string $message) {
                return str_contains(
                    $message,
                    'Ran files:clean-storage. Deleted 5 files'
                );
            });

        $noVersionFiles = array_map(function (int $i) {
            $path = "no-version-file-$i";

            $this->putOldFile($path);

            return $path;
        }, range(1, 5));

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertExists($noVersionFile);
        }

        $version = FileVersion::factory()->create();

        $versionFile = $version->storage_path;

        $this->artisan('files:clean-storage')
            ->expectsOutput('Found 5 files with no version! Deleting...')
            ->assertSuccessful();

        $this->storageFake->assertExists($versionFile);

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertMissing($noVersionFile);
        }
    }

    public function testDoesNotDeleteFileThatHaveNoVersionButHaveBeenModifiedInTheLast24Hours(): void {
        $noVersionFiles = array_map(function (int $i) {
            $path = "no-version-file-$i";

            $this->storageFake->put($path, 'content');

            return $path;
        }, range(1, 5));

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertExists($noVersionFile);
        }

        $version = FileVersion::factory()->create();

        $versionFile = $version->storage_path;

        $this->artisan('files:clean-storage')
            ->expectsOutput('No files with no version found.')
            ->assertSuccessful();

        $this->storageFake->assertExists($versionFile);

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertExists($noVersionFile);
        }
    }

    public function testItWarnsAboutMissingFilesOnTheDisk(): void {
        $file = FileVersion::factory()->create();

        $this->storageFake->delete($file->storage_path);

        $this->artisan('files:clean-storage')
            ->expectsOutput(
                'Found 1 missing files from storage! These files can not be recovered. Run with `--list-missing` to see the list.'
            )
            ->assertFailed();
    }

    public function testItShowsListOfMissingFilesOnTheDiskWhenOptionIsPassed(): void {
        $file = FileVersion::factory()->create();

        $this->storageFake->delete($file->storage_path);

        $this->artisan('files:clean-storage --list-missing')
            ->expectsTable(
                ['File Id', 'Version Id', 'Storage Path'],
                [[$file->file_id, $file->id, $file->storage_path]]
            )
            ->assertFailed();
    }

    public function testDoesNotDeleteFilesOnDryRun(): void {
        $noVersionFiles = array_map(function (int $i) {
            $path = "no-version-file-$i";

            $this->putOldFile($path);

            return $path;
        }, range(1, 5));

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertExists($noVersionFile);
        }

        $version = FileVersion::factory()->create();

        $versionFile = $version->storage_path;

        $this->artisan('files:clean-storage --dry-run')
            ->expectsOutput('Found 5 files with no version! Deleting...')
            ->expectsOutput('Dry run, not deleting files.')
            ->assertSuccessful();

        $this->storageFake->assertExists($versionFile);

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertExists($noVersionFile);
        }
    }

    protected function putOldFile(string $path): void {
        $fullPath = $this->storageFake->path($path);

        touch($fullPath, time() - 60 * 60 * 24);
    }
}
