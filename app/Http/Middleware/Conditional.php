<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Conditional {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $configKey,
        bool $default = false
    ): Response {
        if (!config($configKey, $default)) {
            return abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
