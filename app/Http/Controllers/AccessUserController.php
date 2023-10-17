<?php

namespace App\Http\Controllers;

use App\Models\AccessUser;
use App\View\Helpers\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessUserController extends Controller {
    public function __construct() {
        $this->authorizeResource(AccessUser::class);
    }

    public function index(): View {
        return view('access-users.index', [
            'accessUsers' => AccessUser::query()
                ->withCount('files')
                ->forUser(auth()->user())
                ->get(),
        ]);
    }

    public function create(): View {
        return view('access-users.create');
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:128'],
            'readonly' => ['nullable'],
        ]);

        $username = Str::random();
        $password = Str::password(32);

        /**
         * @var AccessUser
         */
        $accessUser = AccessUser::make([
            'label' => $data['label'],
            'readonly' => !!Arr::get($data, 'readonly', false),
            'active' => true,
        ]);

        $accessUser->forceFill([
            'user_id' => $request->user()->id,
            'username' => $username,
            'password' => $password,
        ]);

        $accessUser->save();

        return redirect()
            ->route('access-users.show', $accessUser->username)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Access user created successfully')
                )->forDuration()
            )
            ->with('generated-password', $password);
    }

    public function show(AccessUser $accessUser): View {
        return view('access-users.show', [
            'accessUser' => $accessUser->load('files'),
        ]);
    }

    public function destroy(AccessUser $accessUser): RedirectResponse {
        $accessUser->delete();

        return redirect()
            ->route('access-users.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Access user successfully deleted.')
                )->forDuration()
            );
    }
}

