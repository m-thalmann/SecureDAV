<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\View\View;

class FileController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);
    }

    public function show(File $file): View {
        return view('files.show', ['file' => $file]);
    }
}

