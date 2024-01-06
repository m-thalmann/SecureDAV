<?php

namespace App\Http\Controllers\Backups;

use App\Backups\AbstractBackupProvider;
use App\Http\Controllers\Controller;
use App\Models\BackupConfiguration;
use App\Models\FileVersion;
use App\Support\SessionMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupConfigurationController extends Controller {
    public function __construct() {
        $this->authorizeResource(BackupConfiguration::class);

        $this->middleware('password.confirm')->only([
            'edit',
            'update',
            'destroy',
        ]);
    }

    public function index(): View {
        $providers = collect(config('backups.providers'))
            ->map(
                fn(array $options, string $className) => [
                    'class' => $className,
                    'alias' =>
                        array_search($className, config('backups.aliases')) ?:
                        null,
                    'options' => $options,
                    'displayInformation' => $className::getDisplayInformation(),
                ]
            )
            ->values();

        $upToDateSubQuery = FileVersion::query()
            ->latestVersion()
            ->join('files', 'files.id', 'file_versions.file_id')
            ->join(
                'backup_configuration_files',
                'backup_configuration_files.file_id',
                'files.id'
            )
            ->join(
                'backup_configurations as inner_backup_configurations',
                'inner_backup_configurations.id',
                'backup_configuration_files.backup_configuration_id'
            )
            ->whereColumn(
                'inner_backup_configurations.id',
                'backup_configurations.id'
            )
            ->where(function (Builder $query) {
                $query
                    ->where(
                        'backup_configuration_files.last_backup_checksum',
                        null
                    )
                    ->orWhereColumn(
                        'file_versions.checksum',
                        '!=',
                        'backup_configuration_files.last_backup_checksum'
                    );
            })
            ->selectRaw('COUNT(*) = 0');

        $configurations = authUser()
            ->backupConfigurations()
            ->addSelect(['up_to_date' => $upToDateSubQuery])
            ->withCount('files')
            ->paginate(perPage: 10);

        return view('backups.index', [
            'providers' => $providers,
            'configurations' => $configurations,
        ]);
    }

    public function create(Request $request): View|RedirectResponse {
        /**
         * @var AbstractBackupProvider|string|null
         */
        $provider = $request->get('provider');

        if ($provider === null) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __('No provider was selected.')
                )->forDuration()
            );
        }

        $aliases = config('backups.aliases');

        if (array_key_exists($provider, $aliases)) {
            /**
             * @var AbstractBackupProvider
             */
            $provider = $aliases[$provider];
        }

        if (!class_exists($provider)) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __('The selected provider does not exist.')
                )->forDuration()
            );
        }

        $displayInformation = $provider::getDisplayInformation();

        $providerTemplate = $provider::getConfigFormTemplate();

        return view('backups.create', [
            'provider' => $provider,
            'displayInformation' => $displayInformation,
            'providerTemplate' => $providerTemplate,
        ]);
    }

    public function store(Request $request): RedirectResponse {
        /**
         * @var AbstractBackupProvider|string
         */
        $provider = $request->get('provider');

        if (!class_exists($provider)) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __('The selected provider does not exist.')
                )->forDuration()
            );
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:128'],
        ]);

        $config = $provider::validateConfig($request->all());

        $backupConfiguration = $provider::createConfiguration(
            authUser(),
            $config,
            $data['label']
        );

        return redirect()
            ->route('backups.show', $backupConfiguration)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Backup configuration successfully created.')
                )->forDuration()
            );
    }

    public function show(BackupConfiguration $backupConfiguration): View {
        $backupConfiguration->load(['files.latestVersion', 'files.directory']);

        $jsonConfig = json_encode(
            $backupConfiguration->maskedConfig,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $scheduleInfo = null;

        if ($backupConfiguration->cron_schedule !== null) {
            $nextRunDates = $backupConfiguration->schedule->getMultipleRunDates(
                amount: 4
            );

            $scheduleInfo = [
                'expression' => $backupConfiguration->schedule->cronExpression,
                'nextRunDates' => $nextRunDates,
            ];
        }

        return view('backups.show', [
            'configuration' => $backupConfiguration,
            'displayInformation' => $backupConfiguration->provider_class::getDisplayInformation(),
            'jsonConfig' => $jsonConfig,
            'scheduleInfo' => $scheduleInfo,
        ]);
    }

    public function edit(BackupConfiguration $backupConfiguration): View {
        $displayInformation = $backupConfiguration->provider_class::getDisplayInformation();

        $providerTemplate = $backupConfiguration->provider_class::getConfigFormTemplate();

        return view('backups.edit', [
            'configuration' => $backupConfiguration,
            'provider' => $backupConfiguration->provider_class,
            'displayInformation' => $displayInformation,
            'providerTemplate' => $providerTemplate,
        ]);
    }

    public function update(
        Request $request,
        BackupConfiguration $backupConfiguration
    ): RedirectResponse {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:128'],
        ]);

        $config = $backupConfiguration->provider_class::validateConfig(
            $request->all()
        );

        $backupConfiguration->update([
            'label' => $data['label'],
            'config' => $config,
        ]);

        return redirect()
            ->route('backups.show', $backupConfiguration)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Backup configuration successfully updated.')
                )->forDuration()
            );
    }

    public function destroy(
        BackupConfiguration $backupConfiguration
    ): RedirectResponse {
        $backupConfiguration->delete();

        return redirect()
            ->route('backups.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Backup configuration successfully deleted.')
                )->forDuration()
            );
    }
}

