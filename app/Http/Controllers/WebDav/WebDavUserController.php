<?php

namespace App\Http\Controllers\WebDav;

use App\Http\Controllers\Controller;
use App\Models\WebDavUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebDavUserController extends Controller {
    public function __construct() {
        $this->authorizeResource(WebDavUser::class);

        $this->middleware('password.confirm')->only([
            'destroy',
        ]);
    }

    public function index(): View {
        return view('web-dav-users.index', [
            'webDavUsers' => WebDavUser::query()
                ->withCount('files')
                ->forUser(authUser())
                ->get(),
        ]);
    }

    public function show(WebDavUser $webDavUser): View {
        $webDavUser->load(['files.latestVersion', 'files.directory']);

        return view('web-dav-users.show', [
            'webDavUser' => $webDavUser,
        ]);
    }

    public function destroy(WebDavUser $webDavUser): RedirectResponse {
        $webDavUser->delete();

        return redirect()
            ->route('web-dav-users.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('WebDav user successfully deleted.')
                )->forDuration()
            );
    }
}

