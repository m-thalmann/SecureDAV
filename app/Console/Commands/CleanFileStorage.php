<?php

namespace App\Console\Commands;

use App\Models\FileVersion;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class CleanFileStorage extends Command {
    protected $signature = 'files:clean-storage {--dry-run} {--list-missing}';

    protected $description = 'Cleans unused files from the storage.';

    protected FilesystemAdapter $storage;

    public function __construct() {
        parent::__construct();

        $this->storage = Storage::disk('files');
    }

    public function handle(): int {
        $dryRun = $this->option('dry-run');
        $listMissing = $this->option('list-missing');

        $uncheckedFiles = $this->storage->allFiles();

        $missingFileVersionFiles = [];

        $versions = FileVersion::query()
            ->select(['id', 'file_id', 'storage_path'])
            ->get();

        foreach ($versions as $version) {
            $fileIndex = array_search($version->storage_path, $uncheckedFiles);

            if ($fileIndex !== false) {
                array_splice($uncheckedFiles, $fileIndex, 1);
                continue;
            }

            $missingFileVersionFiles[] = $version;
        }

        $missingVersions = array_filter(
            $uncheckedFiles,
            fn(string $filePath) => $this->canFileBeDeleted($filePath)
        );

        if (!empty($missingFileVersionFiles)) {
            $this->error(
                'Found ' .
                    count($missingFileVersionFiles) .
                    ' missing files from storage! These files can not be recovered. ' .
                    ($listMissing
                        ? 'See list:'
                        : 'Run with `--list-missing` to see the list.')
            );

            if ($listMissing) {
                $this->table(
                    ['File Id', 'Version Id', 'Storage Path'],
                    array_map(
                        fn(FileVersion $missingFileVersion) => [
                            $missingFileVersion->file_id,
                            $missingFileVersion->id,
                            $missingFileVersion->storage_path,
                        ],
                        $missingFileVersionFiles
                    )
                );
            }
        }

        if (!empty($missingVersions)) {
            $this->warn(
                'Found ' .
                    count($missingVersions) .
                    ' files with no version! Deleting...'
            );
        } else {
            $this->info('No files with no version found.');
        }

        if ($dryRun) {
            $this->line('Dry run, not deleting files.');
        }

        $amountBytesFreed = 0;

        if (!empty($missingVersions)) {
            $this->withProgressBar($missingVersions, function (
                string $filePath
            ) use ($dryRun, &$amountBytesFreed) {
                $amountBytesFreed += $this->storage->size($filePath);

                if (!$dryRun) {
                    $this->storage->delete($filePath);
                }
            });

            $this->newLine();
        }

        $this->newLine();

        $amountMemoryFreed = Number::fileSize($amountBytesFreed);
        $amountDeletedFiles = count($missingVersions);

        $this->info(
            "Done. Deleted $amountDeletedFiles files, freed $amountMemoryFreed."
        );

        if (!$dryRun && $amountDeletedFiles > 0) {
            Log::info(
                "Ran files:clean-storage. Deleted $amountDeletedFiles files, freed $amountMemoryFreed."
            );
        }

        return empty($missingFileVersionFiles)
            ? static::SUCCESS
            : static::FAILURE;
    }

    protected function canFileBeDeleted(string $fileName): bool {
        $lastModified = $this->storage->lastModified($fileName);

        // don't delete files that have been modified in the last 24 hours
        return $lastModified <= now()->subDay()->timestamp;
    }
}
