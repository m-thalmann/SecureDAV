<?php

namespace App\WebDav;

use App\Models\AccessUser;
use Sabre\DAV;

class Authentication extends DAV\Auth\Backend\AbstractBasic {
    /**
     * @var AccessUser The authenticated user
     */
    private static $user = null;

    public function validateUserPass($username, $password) {
        // TODO: rate limiting

        $user = AccessUser::where("username", $username)->first();

        if ($user === null || !$user->authenticate($password)) {
            return false;
        }

        self::$user = $user;

        return true;
    }

    /**
     * @return AccessUser|null The authenticated user
     */
    public static function getUser() {
        return self::$user;
    }
}
