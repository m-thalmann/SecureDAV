<?php

namespace App\WebDav;

use App\Models\AccessGroupUser;
use Illuminate\Support\Facades\Hash;
use Sabre\DAV;

/**
 * Authentication backend for WebDAV which uses the AccessGroupUser model to authenticate users
 */
class AuthBackend extends DAV\Auth\Backend\AbstractBasic {
    private ?AccessGroupUser $authenticatedUser = null;

    public function validateUserPass(mixed $username, mixed $password): bool {
        // TODO: add rate limiting

        $accessGroupUser = AccessGroupUser::query()
            ->where('username', $username)
            ->with('accessGroup')
            ->first();

        if (
            $accessGroupUser === null ||
            !Hash::check($password, $accessGroupUser->password) ||
            !$accessGroupUser->accessGroup->active
        ) {
            return false;
        }

        $this->authenticatedUser = $accessGroupUser;

        return true;
    }

    public function getAuthenticatedUser(): ?AccessGroupUser {
        return $this->authenticatedUser;
    }
}
