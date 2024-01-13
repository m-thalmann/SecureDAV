<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Models\BackupConfiguration;
use App\Support\BackupSchedule;
use App\Support\SessionMessage;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BackupConfigurationScheduleController extends Controller {
    public function __construct() {
        $this->authorizeResource(BackupConfiguration::class);

        $this->middleware('password.confirm');
    }

    public function edit(BackupConfiguration $backupConfiguration): View {
        $displayInformation = $backupConfiguration->provider_class::getDisplayInformation();

        $availableSchedules = BackupSchedule::createAllAvailable();
        $hasCustomSchedule =
            $backupConfiguration->schedule !== null &&
            $backupConfiguration->schedule->getName() === null;

        $allNextRuns = collect($availableSchedules)
            ->mapWithKeys(function ($schedule) {
                return [
                    $schedule->getValue() => $schedule->getMultipleRunDates(
                        amount: 4
                    ),
                ];
            })
            ->all();

        if ($hasCustomSchedule) {
            $allNextRuns[
                'custom'
            ] = $backupConfiguration->schedule->getMultipleRunDates(amount: 4);
        }

        $allNextRuns = array_map(
            fn(array $runs) => array_map(
                fn(Carbon $run) => [
                    'diff' => $run->diffForHumans(),
                    'timestamp' => $run->format('Y-m-d H:i:s'),
                ],
                $runs
            ),
            $allNextRuns
        );

        return view('backups.edit-schedule', [
            'configuration' => $backupConfiguration,
            'displayInformation' => $displayInformation,
            'availableSchedules' => BackupSchedule::createAllAvailable(),
            'hasCustomSchedule' =>
                $backupConfiguration->schedule !== null &&
                $backupConfiguration->schedule->getName() === null,
            'allNextRuns' => $allNextRuns,
        ]);
    }

    public function update(
        Request $request,
        BackupConfiguration $backupConfiguration
    ): RedirectResponse {
        $data = $request->validate([
            'schedule' => [
                'required',
                Rule::in('none', ...BackupSchedule::AVAILABLE_SCHEDULES),
            ],
        ]);

        $schedule = $data['schedule'] === 'none' ? null : $data['schedule'];

        $backupConfiguration->update([
            'cron_schedule' => $schedule,
        ]);

        return redirect()
            ->route('backups.show', $backupConfiguration)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Backup schedule successfully updated.')
                )->forDuration()
            );
    }
}

