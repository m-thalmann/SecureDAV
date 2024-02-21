<?php

namespace App\Console\Commands;

use App\WebDav\LocksBackend;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class CleanDatabase extends Command {
    protected $signature = 'clean-database';

    protected $description = 'Cleans expired and old data from the database.';

    public function handle(): int {
        $this->cleanWebDavLocks();
        $this->newLine();
        $this->cleanOldNotifications();

        return static::SUCCESS;
    }

    protected function cleanWebDavLocks(): void {
        $this->line('Cleaning expired WebDAV locks...');

        $amountDeleted = DB::table(LocksBackend::TABLE_NAME)
            ->whereRaw('((created + timeout) <= ?)', [time()])
            ->delete();

        $this->info("Deleted $amountDeleted expired WebDAV locks.");
    }

    protected function cleanOldNotifications(): void {
        $this->line('Cleaning old notifications...');

        $amountDeleted = DatabaseNotification::query()
            ->where('read_at', '<=', now()->subWeek())
            ->delete();

        $this->info("Deleted $amountDeleted old notifications.");
    }
}
