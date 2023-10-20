<?php

namespace App\Http\Controllers;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\View\Helpers\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessGroupUserController extends Controller {
    public function __construct() {
        $this->authorizeResource(AccessGroupUser::class, 'access_group_user');
    }

    public function create(AccessGroup $accessGroup): View {
        $this->authorize('update', $accessGroup);

        return view('access-groups.users.create', [
            'accessGroup' => $accessGroup,
        ]);
    }

    public function store(
        Request $request,
        AccessGroup $accessGroup
    ): RedirectResponse {
        $this->authorize('update', $accessGroup);

        $data = $request->validate([
            'label' => ['string', 'max:128'],
        ]);

        $password = Str::password();

        $accessGroup->users()->forceCreate([
            // username => uuid
            'label' => $data['label'],
            'password' => $password,
        ]);

        return redirect()
            ->route('access-groups.show', $accessGroup->uuid)
            ->withFragment('users')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Group user created successfully')
                )->forDuration()
            )
            ->with('generated-password', $password);
    }
}

