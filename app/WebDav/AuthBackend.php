<?php

namespace App\WebDav;

use App\Models\User;
use App\Models\WebDavUser;
use App\WebDav\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Sabre\DAV;

/**
 * Authentication backend for WebDAV which uses the WebDavUser model to authenticate users
 */
class AuthBackend extends DAV\Auth\Backend\AbstractBasic {
    protected const RATE_LIMITER_KEY = 'webdav-auth';
    protected const RATE_LIMITER_ATTEMPTS = 5;

    protected ?WebDavUser $authenticatedWebDavUser = null;

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

        /**
         * @var WebDavUser|null
         */
        $webDavUser = WebDavUser::query()
            ->where('username', $username)
            ->with('user')
            ->first();

        if (
            $webDavUser === null ||
            !Hash::check($password, $webDavUser->password) ||
            !$webDavUser->active ||
            $webDavUser->user->is_webdav_suspended
        ) {
            RateLimiter::hit($rateLimiterKey);

            return false;
        }

        RateLimiter::clear($rateLimiterKey);

        $webDavUser->forceFill(['last_access' => now()])->save();

        $this->authenticatedWebDavUser = $webDavUser;

        return true;
    }

    public function getAuthenticatedWebDavUser(): ?WebDavUser {
        return $this->authenticatedWebDavUser;
    }

    public function getAuthenticatedUser(): ?User {
        return $this->authenticatedWebDavUser?->user;
    }
}
