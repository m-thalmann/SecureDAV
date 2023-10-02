<?php

namespace App\Exceptions;

use Exception;

class NoVersionFoundException extends Exception {
    public function __construct(string $message = 'No version found') {
        parent::__construct($message);
    }
}
