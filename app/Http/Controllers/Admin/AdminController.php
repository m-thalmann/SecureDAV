<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use App\Models\WebDavUser;
use Illuminate\View\View;

class AdminController extends Controller {
    public function index(): View {
        $amountUsers = User::count();
        $amountFiles = File::count();
        $amountVersions = FileVersion::count();
        $amountWebDavUsers = WebDavUser::count();
        $amountConfiguredBackups = BackupConfiguration::count();

        return view('admin.index', [
            'amountUsers' => $amountUsers,
            'amountFiles' => $amountFiles,
            'amountVersions' => $amountVersions,
            'amountWebDavUsers' => $amountWebDavUsers,
            'amountConfiguredBackups' => $amountConfiguredBackups,
        ]);
    }
}
