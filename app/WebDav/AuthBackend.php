<?php

namespace App\WebDav;

use App\Models\AccessGroup;
use App\Models\AccessGroupUser;
use App\Models\User;
use App\WebDav\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Sabre\DAV;

/**
 * Authentication backend for WebDAV which uses the AccessGroupUser model to authenticate users
 */
class AuthBackend extends DAV\Auth\Backend\AbstractBasic {
    protected const RATE_LIMITER_KEY = 'webdav-auth';
    protected const RATE_LIMITER_ATTEMPTS = 5;

    protected ?AccessGroupUser $authenticatedAccessGroupUser = null;

    public function validateUserPass(mixed $username, mixed $password): bool {
        $rateLimiterKey = static::RATE_LIMITER_KEY . ':' . $username;

        if (
            RateLimiter::tooManyAttempts(
                $rateLimiterKey,
                static::RATE_LIMITER_ATTEMPTS
            )
        ) {
            $availableIn = RateLimiter::availableIn($rateLimiterKey);

            throw new TooManyRequestsException($availableIn);
        }

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
            RateLimiter::hit($rateLimiterKey);

            return false;
        }

        RateLimiter::clear($rateLimiterKey);

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
