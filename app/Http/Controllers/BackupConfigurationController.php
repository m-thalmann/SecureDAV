<?php

namespace App\Http\Controllers;

use App\Models\BackupConfiguration;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupConfigurationController extends Controller {
    public function __construct() {
        $this->authorizeResource(BackupConfiguration::class);

        $this->middleware('password.confirm')->only([
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

        $configurations = authUser()
            ->backupConfigurations()
            ->withCount('files')
            ->paginate(perPage: 10);

        return view('backups.index', [
            'providers' => $providers,
            'configurations' => $configurations,
        ]);
    }

    public function show(BackupConfiguration $backupConfiguration): View {
        $backupConfiguration->load(['files.latestVersion', 'files.directory']);

        return view('backups.show', [
            'configuration' => $backupConfiguration,
            'displayInformation' => $backupConfiguration->provider_class::getDisplayInformation(),
        ]);
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

