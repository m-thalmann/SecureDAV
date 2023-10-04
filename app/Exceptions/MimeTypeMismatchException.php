<?php

namespace App\Exceptions;

use Exception;

class MimeTypeMismatchException extends Exception {
    public function __construct(
        public readonly string $expectedMimeType,
        public readonly string $actualMimeType,
        string $message = 'File mime types dont match'
    ) {
        parent::__construct($message);
    }
}
