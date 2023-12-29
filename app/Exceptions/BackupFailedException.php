<?php

namespace App\Exceptions;

use Exception;

class BackupFailedException extends Exception {
    public function __construct(string $message = 'The backup has failed.') {
        parent::__construct($message);
    }
}
