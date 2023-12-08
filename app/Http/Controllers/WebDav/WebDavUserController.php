<?php

namespace App\Http\Controllers\WebDav;

use App\Http\Controllers\Controller;
use App\Models\WebDavUser;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    public function create(): View {
        return view('web-dav-users.create');
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:128'],
            'readonly' => ['nullable'],
        ]);

        $password = Str::password();

        $webDavUser = authUser()
            ->webDavUsers()
            ->forceCreate([
                // username => uuid
                'password' => $password,
                'label' => $data['label'] ?? fake()->userName(),
                'readonly' => !!Arr::get($data, 'readonly', false),
                'active' => true,
            ]);

        return redirect()
            ->route('web-dav-users.show', $webDavUser->username)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('WebDav user created successfully')
                )->forDuration()
            )
            ->with('generated-password', $password); // TODO:
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

