<?php

namespace App\Console\Commands;

use App\Jobs\RunBackup;
use App\Models\BackupConfiguration;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackupsRunScheduled extends Command {
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Runs all backups that are scheduled to run at the current moment.';

    public function handle(): int {
        /**
         * @var Collection
         */
        $backups = BackupConfiguration::query()
            ->active()
            ->withSchedule()
            ->with('user')
            ->get();

        /**
         * @var BackupConfiguration[]
         */
        $dueBackups = $backups
            ->filter(
                fn(BackupConfiguration $backup) => (new CronExpression(
                    $backup->cron_schedule
                ))->isDue(timeZone: $backup->user->timezone)
            )
            ->all();

        foreach ($dueBackups as $backup) {
            RunBackup::dispatch($backup);
        }

        $this->info('Dispatched ' . count($dueBackups) . ' backup jobs.');

        return static::SUCCESS;
    }
}
