<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Models\WebDavUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\View\View;

class AdminController extends Controller {
    public function index(): View {
        $amountUsers = User::count();
        $amountFiles = File::count();
        $amountVersions = FileVersion::count();
        $amountWebDavUsers = WebDavUser::count();
        $amountConfiguredBackups = BackupConfiguration::count();

        $fileSize = collect(Storage::disk('local')->allFiles())->reduce(
            fn(float $size, string $file) => $size +
                Storage::disk('local')->size($file),
            0.0
        );

        return view('admin.index', [
            'amountUsers' => $amountUsers,
            'amountFiles' => $amountFiles,
            'amountVersions' => $amountVersions,
            'amountWebDavUsers' => $amountWebDavUsers,
            'amountConfiguredBackups' => $amountConfiguredBackups,
            'fileSize' => Number::fileSize($fileSize, precision: 2),

            'settings' => [
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'registration_enabled' => config('app.registration_enabled'),
                'email_verification_enabled' => config(
                    'app.email_verification_enabled'
                ),
                'webdav_cors_enabled' => config('webdav.cors.enabled'),
                'webdav_cors_allowed_origins' => join(
                    ', ',
                    config('webdav.cors.allowed_origins')
                ),
            ],
        ]);
    }
}
