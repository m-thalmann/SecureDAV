<?php

namespace App\Http\Controllers\AccessGroups;

use App\Http\Controllers\Controller;
use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessGroupUserController extends Controller {
    public function __construct() {
        $this->authorizeResource(AccessGroupUser::class, 'access_group_user');

        $this->middleware('password.confirm')->only([
            'create',
            'store',
            'destroy',
            'resetPassword',
        ]);
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

    public function edit(AccessGroupUser $accessGroupUser): View {
        return view('access-groups.users.edit', [
            'accessGroupUser' => $accessGroupUser->load('accessGroup'),
        ]);
    }

    public function update(
        Request $request,
        AccessGroupUser $accessGroupUser
    ): RedirectResponse {
        $data = $request->validate([
            'label' => ['string', 'max:128'],
        ]);

        $accessGroupUser->update($data);

        return redirect()
            ->route('access-groups.show', $accessGroupUser->accessGroup->uuid)
            ->withFragment('users')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Group user updated successfully')
                )->forDuration()
            );
    }

    public function destroy(
        AccessGroupUser $accessGroupUser
    ): RedirectResponse {
        $accessGroupUser->delete();

        return redirect()
            ->route('access-groups.show', $accessGroupUser->accessGroup->uuid)
            ->withFragment('users')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Group user deleted successfully')
                )->forDuration()
            );
    }

    public function resetPassword(
        AccessGroupUser $accessGroupUser
    ): RedirectResponse {
        $this->authorize('update', $accessGroupUser);

        $password = Str::password();

        $accessGroupUser
            ->forceFill([
                'password' => $password,
            ])
            ->save();

        return redirect()
            ->route('access-groups.show', $accessGroupUser->accessGroup->uuid)
            ->withFragment('users')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Password reset successfully')
                )->forDuration()
            )
            ->with('generated-password', $password);
    }
}

