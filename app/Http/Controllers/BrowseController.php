<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Models\File;
use Illuminate\View\View;

class BrowseController extends Controller {
    public function __invoke(?Directory $directory = null): View {
        if ($directory) {
            $this->authorize('view', $directory);
        }

        $directories = Directory::query()
            ->inDirectory($directory)
            ->ordered()
            ->get()
            ->all();
        $files = File::query()
            ->inDirectory($directory)
            ->ordered()
            ->with('latestVersion')
            ->get()
            ->all();

        $breadcrumbs = $directory ? $directory->breadcrumbs : [];

        $webdavUrl = match ($directory) {
            null => route('webdav.directories'),
            default => $directory->webdavUrl,
        };

        return view('browse', [
            'currentDirectory' => $directory ?? null,
            'breadcrumbs' => $breadcrumbs,
            'webdavUrl' => $webdavUrl,

            'directories' => $directories,
            'files' => $files,
        ]);
    }
}
