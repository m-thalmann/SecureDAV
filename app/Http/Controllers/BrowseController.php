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

        $directoriesQuery = null;
        $filesQuery = null;

        $breadcrumbs = [];

        if ($directory) {
            $directoriesQuery = $directory->directories();
            $filesQuery = $directory->files();

            $breadcrumbs = $directory->breadcrumbs;
        } else {
            $directoriesQuery = Directory::query()
                ->whereNull('parent_directory_id')
                ->forUser(auth()->user());
            $filesQuery = File::query()
                ->whereNull('directory_id')
                ->forUser(auth()->user());
        }

        $directories = $directoriesQuery
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        $files = $filesQuery
            ->orderBy('name', 'asc')
            ->orderBy('extension', 'asc')
            ->get()
            ->all();

        return view('browse', [
            'currentDirectory' => $directory ?? null,
            'breadcrumbs' => $breadcrumbs,

            'directories' => $directories,
            'files' => $files,
        ]);
    }
}

