<?php

namespace App\Http\Controllers\AccessGroups;

use App\Http\Controllers\Controller;
use App\Models\AccessGroupUser;
use App\Support\SessionMessage;
use Illuminate\Http\Request;

class JumpToAccessGroupUserController extends Controller {
    public function __invoke(Request $request) {
        $username = $request->validate([
            'username' => ['required', 'string'],
        ])['username'];

        $accessGroupUser = AccessGroupUser::query()
            ->where('username', $username)
            ->whereRelation('accessGroup', 'user_id', authUser()->id)
            ->first();

        if (!$accessGroupUser) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __('Access group user was not found.')
                )->forDuration()
            );
        }

        return redirect()->route('access-groups.show', [
            $accessGroupUser->accessGroup,
        ]);
    }
}

