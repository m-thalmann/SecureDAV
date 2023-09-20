<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\View\Helpers\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DirectoryController extends Controller {
    public function __construct() {
        $this->authorizeResource(Directory::class);
    }

    public function edit(Directory $directory): View {
        return view('directories.edit', [
            'directory' => $directory,
        ]);
    }

    public function update(
        Request $request,
        Directory $directory
    ): RedirectResponse {
        // TODO: make parent_directory_id editable
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('directories', 'name')
                    ->where(
                        'parent_directory_id',
                        $directory->parent_directory_id
                    )
                    ->where('user_id', $directory->user_id)
                    ->ignore($directory),
            ],
        ]);

        $directory->update($data);

        return redirect()
            ->route('browse.index', $directory->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Directory updated successfully')
                )->forDuration()
            );
    }
}
