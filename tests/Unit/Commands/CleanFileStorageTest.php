<?php

namespace Tests\Unit\Commands;

use App\Models\FileVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CleanFileStorageTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testItDeletesFilesThatHaveNoVersion(): void {
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
            ->expectsOutput('Found 5 files with no version! Deleting...')
            ->assertExitCode(0);

        $this->storageFake->assertExists($versionFile);

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertMissing($noVersionFile);
        }
    }

    public function testItWarnsAboutMissingFilesOnTheDisk(): void {
        $file = FileVersion::factory()->create();

        $this->storageFake->delete($file->storage_path);

        $this->artisan('files:clean-storage')
            ->expectsOutput(
                'Found 1 missing files from storage! These files can not be recovered.'
            )
            ->assertExitCode(1);
    }

    public function testDoesNotDeleteFilesOnDryRun(): void {
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

        $this->artisan('files:clean-storage --dry-run')
            ->expectsOutput('Found 5 files with no version! Deleting...')
            ->expectsOutput('Dry run, not deleting files.')
            ->assertExitCode(0);

        $this->storageFake->assertExists($versionFile);

        foreach ($noVersionFiles as $noVersionFile) {
            $this->storageFake->assertExists($noVersionFile);
        }
    }
}
