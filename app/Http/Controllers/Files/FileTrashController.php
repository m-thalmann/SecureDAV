<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FileTrashController extends Controller {
    public function index(): View {
        $files = authUser()
            ->files()
            ->onlyTrashed()
            ->paginate(perPage: 10);

        return view('files.trash', [
            'files' => $files,
        ]);
    }
}

