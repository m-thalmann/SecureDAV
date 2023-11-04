<?php

namespace App\WebDav;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Sabre\DAV;

/**
 * Authentication backend for WebDAV which uses the AccessGroupUser model to authenticate users
 */
class AuthBackend extends DAV\Auth\Backend\AbstractBasic {
    private ?AccessGroupUser $authenticatedAccessGroupUser = null;

    public function validateUserPass(mixed $username, mixed $password): bool {
        // TODO: add rate limiting

        $accessGroupUser = AccessGroupUser::query()
            ->where('username', $username)
            ->with('accessGroup.user')
            ->first();

        if (
            $accessGroupUser === null ||
            !Hash::check($password, $accessGroupUser->password) ||
            !$accessGroupUser->accessGroup->active ||
            $accessGroupUser->accessGroup->user->is_webdav_suspended
        ) {
            return false;
        }

        $this->authenticatedAccessGroupUser = $accessGroupUser;

        return true;
    }

    public function getAuthenticatedAccessGroupUser(): ?AccessGroupUser {
        return $this->authenticatedAccessGroupUser;
    }

    public function getAuthenticatedAccessGroup(): ?AccessGroup {
        return $this->authenticatedAccessGroupUser?->accessGroup;
    }

    public function getAuthenticatedUser(): ?User {
        return $this->authenticatedAccessGroupUser?->accessGroup->user;
    }
}
