<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use PDOException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler {
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void {
    }

    public function render($request, Throwable $e): Response {
        if ($e instanceof PDOException && !config('app.debug')) {
            $e = new Exception(
                __(
                    'A database error occurred. Please try again later or ask the system administrator for help.'
                ),
                previous: $e
            );
        }

        return parent::render($request, $e);
    }
}
