<?php

namespace App\Http\Controllers;

use App\Models\BackupConfiguration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupConfigurationController extends Controller {
    public function __construct() {
        $this->authorizeResource(BackupConfiguration::class);
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
            ->get();

        return view('backups.index', [
            'providers' => $providers,
            'configurations' => $configurations,
        ]);
    }
}

