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

        $directories = [];
        $files = [];
        $breadcrumbs = [];

        if ($directory) {
            $directories = $directory->directories->all();
            $files = $directory->files->all();

            $breadcrumbs = $directory->breadcrumbs;
        } else {
            $directories = Directory::query()
                ->whereNull('parent_directory_id')
                ->forUser(auth()->user())
                ->get()
                ->all();
            $files = File::query()
                ->whereNull('directory_id')
                ->forUser(auth()->user())
                ->get()
                ->all();
        }

        return view('browse', [
            'currentDirectory' => $directory ?? null,
            'breadcrumbs' => $breadcrumbs,

            'directories' => $directories,
            'files' => $files,
        ]);
    }
}

