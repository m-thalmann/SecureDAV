<?php

namespace App\Http\Controllers\WebDav;

use App\Http\Controllers\Controller;
use App\Models\WebDavUser;
use App\Support\SessionMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebDavUserController extends Controller {
    public function __construct() {
        $this->authorizeResource(WebDavUser::class);

        $this->middleware('password.confirm')->only([
            'edit',
            'update',
            'resetPassword',
            'destroy',
        ]);
    }

    public function index(Request $request): View {
        $search = $request->get('q', default: null);

        $webDavUsers = WebDavUser::query()
            ->withCount('files')
            ->when($search, function (Builder $query, string $search) {
                $query
                    ->where('label', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            })
            ->forUser(authUser())
            ->paginate(perPage: 10)
            ->appends(['q' => $search]);

        return view('web-dav-users.index', [
            'webDavUsers' => $webDavUsers,
            'search' => $search,
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
            ->with('generated-password', $password);
    }

    public function show(WebDavUser $webDavUser): View {
        $webDavUser->load(['files.latestVersion', 'files.directory']);

        return view('web-dav-users.show', [
            'webDavUser' => $webDavUser,
        ]);
    }

    public function edit(WebDavUser $webDavUser): View {
        return view('web-dav-users.edit', [
            'webDavUser' => $webDavUser,
        ]);
    }

    public function update(
        Request $request,
        WebDavUser $webDavUser
    ): RedirectResponse {
        $data = $request->validate([
            'label' => ['string', 'max:128'],
            'readonly' => ['nullable'],
            'active' => ['nullable'],
        ]);

        $webDavUser->update([
            'label' => $data['label'],
            'readonly' => !!Arr::get($data, 'readonly', false),
            'active' => !!Arr::get($data, 'active', false),
        ]);

        return redirect()
            ->route('web-dav-users.show', $webDavUser->username)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('WebDav user updated successfully')
                )->forDuration()
            );
    }

    public function resetPassword(WebDavUser $webDavUser): RedirectResponse {
        $this->authorize('update', $webDavUser);

        $password = Str::password();

        $webDavUser
            ->forceFill([
                'password' => $password,
            ])
            ->save();

        return redirect()
            ->route('web-dav-users.show', $webDavUser->username)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Password reset successfully')
                )->forDuration()
            )
            ->with('generated-password', $password);
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
