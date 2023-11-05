<?php

namespace App\WebDav\Exceptions;

use Illuminate\Http\Response;
use Sabre\DAV;
use Sabre\DAV\Server;

class TooManyRequestsException extends DAV\Exception {
    public function __construct(protected int $availableIn) {
        parent::__construct(
            __('auth.throttle', [
                'seconds' => $availableIn,
                'minutes' => ceil($availableIn / 60),
            ])
        );
    }

    public function getHTTPCode(): int {
        return Response::HTTP_TOO_MANY_REQUESTS;
    }

    public function getHTTPHeaders(?Server $server): array {
        return [
            'Retry-After' => $this->availableIn,
        ];
    }
}
