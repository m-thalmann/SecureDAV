<?php

namespace App\WebDav;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Sabre\DAV;
use Symfony\Component\HttpFoundation\Response;

/**
 * WebDAV server class for Laravel
 * @see https://github.com/monicahq/laravel-sabre
 */
class Server extends DAV\Server {
    /**
     * @var array List of HTTP methods allowed for this server
     */
    public const METHODS = [
        'GET',
        'PROPFIND',
        'LOCK',
        'UNLOCK',
        'PUT',
        'OPTIONS',
        'HEAD',
    ];

    public function __construct(
        AuthBackend $authBackend,
        LocksBackend $locksBackend,
        string $basePath,
        DAV\Tree $tree
    ) {
        parent::__construct($tree, new Sapi());

        if (app()->hasDebugModeEnabled()) {
            $this->debugExceptions = true;
        }

        $this->setBaseUri(
            str($basePath)
                ->finish('/')
                ->start('/')
                ->toString()
        );

        $this->addPlugin(new Plugins\HandleGetPlugin());
        $this->addPlugin(new DAV\Auth\Plugin($authBackend));
        $this->addPlugin(new DAV\Locks\Plugin($locksBackend));
    }

    /**
     * Set request from Laravel.
     * @param Request $request
     */
    public function setRequest(Request $request): void {
        // set url with trailing slash
        $this->httpRequest->setUrl($this->getFullUrl($request));

        // testing needs request to be set manually
        if (app()->runningUnitTests()) {
            /**
             * @var array
             */
            $requestHeaders = $request->headers->all();

            $this->httpRequest->setMethod($request->method());
            $this->httpRequest->setBody($request->getContent(asResource: true));
            $this->httpRequest->setHeaders($requestHeaders);
        }
    }

    /**
     * Get response for Laravel.
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getResponse(): Response {
        $body = $this->httpResponse->getBody();
        $status = $this->httpResponse->getStatus();
        $headers = $this->httpResponse->getHeaders();

        if (!is_callable($body) && !is_resource($body)) {
            return response($body, $status, $headers);
        }

        return response()->stream(
            function () use ($body) {
                if (is_callable($body)) {
                    $body();
                } else {
                    fpassthru($body);
                }
            },
            $status,
            $headers
        );
    }

    protected function getFullUrl(Request $request): string {
        $url = Str::finish($request->getPathInfo(), '/');
        $query = $request->getQueryString();

        if ($query === null) {
            return $url;
        }

        return "$url?$query";
    }

    /**
     * Returns the headers required for CORS
     *
     * @param string|null $requestOrigin The origin of the request to allow multiple origins
     *
     * @return array
     */
    public function getCorsHeaders(?string $requestOrigin): array {
        $configuredAllowedOrigins = config('webdav.cors.allowed_origins', []);

        $allowOrigin = null;

        foreach ($configuredAllowedOrigins as $origin) {
            if ($origin === '*' || $origin === $requestOrigin) {
                $allowOrigin = $requestOrigin ?? '*';
                break;
            }
        }

        return [
            'Access-Control-Allow-Origin' => $allowOrigin,

            'Access-Control-Allow-Methods' => join(', ', static::METHODS),

            'Access-Control-Expose-Headers' => config(
                'webdav.cors.expose_headers'
            ),

            'Access-Control-Allow-Headers' => config(
                'webdav.cors.allowed_headers'
            ),

            'Access-Control-Allow-Credentials' => 'true',
        ];
    }
}
