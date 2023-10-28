<?php

namespace App\WebDav;

use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi as BaseSapi;

/**
 * Mock version of Sapi server to avoid 'header()' calls.
 * @see https://github.com/monicahq/laravel-sabre
 */
class Sapi extends BaseSapi {
    public static function sendResponse(ResponseInterface $response): void {
        // response is retrieved via the getResponse() method inside of the Server class.
    }
}
