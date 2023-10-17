<?php

namespace App\Http\Controllers;

use App\Models\AccessUser;
use App\View\Helpers\SessionMessage;
use Illuminate\Http\RedirectResponse;
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

