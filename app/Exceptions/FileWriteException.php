<?php

namespace App\Exceptions;

use Exception;

class FileWriteException extends Exception {
    public function __construct(
        string $message = 'File could not be written',
        Exception $previous = null
    ) {
        parent::__construct($message, previous: $previous);
    }
}
