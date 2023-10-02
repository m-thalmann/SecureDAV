<?php

namespace App\Exceptions;

use Exception;

class FileAlreadyExistsException extends Exception {
    public function __construct(
        string $message = 'Path already exists. Try again'
    ) {
        parent::__construct($message);
    }
}
