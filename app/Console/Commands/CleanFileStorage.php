<?php

namespace App\Console\Commands;

use App\Models\FileVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanFileStorage extends Command {
    protected $signature = 'files:clean-storage {--dry-run}';

    protected $description = 'Cleans unused files from the storage.';

    public function handle() {
        $dryRun = $this->option('dry-run');

        $uncheckedFiles = Storage::disk('files')->allFiles();

        $missingFiles = [];

        $versions = FileVersion::query()
            ->select(['id', 'file_id', 'storage_path'])
            ->get();

        foreach ($versions as $version) {
            $fileIndex = array_search($version->storage_path, $uncheckedFiles);

            if ($fileIndex !== false) {
                array_splice($uncheckedFiles, $fileIndex, 1);
                continue;
            }

            $missingFiles[] = $version->storage_path;
        }

        if (!empty($missingFiles)) {
            $this->error(
                'Found ' .
                    count($missingFiles) .
                    ' missing files from storage! These files can not be recovered.'
            );
        }

        if (!empty($uncheckedFiles)) {
            $this->warn(
                'Found ' .
                    count($uncheckedFiles) .
                    ' files with no version! Deleting...'
            );
        } else {
            $this->info('No files with no version found.');
        }

        if (!$dryRun) {
            Storage::disk('files')->delete($uncheckedFiles);
        } else {
            $this->info('Dry run, not deleting files.');
        }

        $this->info('Done.');

        return empty($missingFiles) ? static::SUCCESS : static::FAILURE;
    }
}
