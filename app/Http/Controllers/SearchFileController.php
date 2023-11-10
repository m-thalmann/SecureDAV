<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;

class SearchFileController extends Controller {
    public function __invoke(Request $request) {
        $search = $request->get('q', default: null);

        $files =
            $search !== null
                ? File::query()
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->forUser(authUser())
                    ->paginate(perPage: 10)
                    ->appends(['q' => $search])
                    ->onEachSide(2)
                : null;

        return view('files.search', ['search' => $search, 'files' => $files]);
    }
}

