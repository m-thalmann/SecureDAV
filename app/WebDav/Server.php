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
        'PROPPATCH',
        'LOCK',
        'UNLOCK',
        'POST',
    ];

    public function __construct(
        AuthBackend $authBackend,
        string $basePath,
        DAV\Tree $tree
    ) {
        parent::__construct($tree);

        $this->sapi = new Sapi();

        if (app()->hasDebugModeEnabled()) {
            $this->debugExceptions = true;
        }

        $this->setBaseUri(
            str($basePath)
                ->finish('/')
                ->start('/')
                ->toString()
        );

        $this->addPlugin(new DAV\Auth\Plugin($authBackend));

        // TODO: add locks
        // TODO: handle cors
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

        if ($body === null || is_string($body)) {
            return response($body, $status, $headers);
        }

        return response()->stream(
            function () use ($body) {
                fpassthru($body);
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
}
