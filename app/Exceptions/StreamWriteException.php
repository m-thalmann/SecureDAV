<?php

namespace App\Exceptions;

use Exception;

class StreamWriteException extends Exception {
    public function __construct(
        string $message = 'Stream could not be written'
    ) {
        parent::__construct($message);
    }
}

