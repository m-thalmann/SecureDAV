<?php

namespace App\WebDav;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Sabre\DAV;

/**
 * Modified server class from (see author)
 * @author https://github.com/monicahq/laravel-sabre
 */
class Server extends Dav\Server {
    /**
     * HTTP-Methods required for the server to work
     */
    const methods = [
        "GET",
        "PROPFIND",
        "LOCK",
        "UNLOCK",
        "POST",
        "PUT",
        "OPTIONS",
        "HEAD",
    ];

    /**
     * Creates a new instance of Sabre Server.
     *
     * @param  \Sabre\DAV\Tree|\Sabre\DAV\INode|array|null  $treeOrNode  The tree object
     */
    public function __construct($treeOrNode = null) {
        parent::__construct($treeOrNode);

        /** @var \Sabre\HTTP\Sapi */
        $sapi = new Sapi();
        $this->sapi = $sapi;

        if (!App::environment("production")) {
            $this->debugExceptions = true;
        }

        $authPlugin = new DAV\Auth\Plugin(new Authentication());
        $locksPlugin = new DAV\Locks\Plugin(new Locks());

        $this->setBaseUri("/dav");

        $this->addPlugin($authPlugin);
        $this->addPlugin($locksPlugin);
    }

    /**
     * Set request from Laravel.
     *
     * @param  Request  $request
     * @return void
     */
    public function setRequest(Request $request) {
        // Base Uri of dav requests
        $this->setBaseUri("/dav");

        // Set Url with trailing slash
        $this->httpRequest->setUrl($this->fullUrl($request));
    }

    /**
     * Get response for Laravel.
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function getResponse() {
        // Transform to Laravel response
        /** @var resource|string|null */
        $body = $this->httpResponse->getBody();
        $status = $this->httpResponse->getStatus();
        $headers = $this->httpResponse->getHeaders();

        if (VirtualFile::getSelectedFile() !== null) {
            $headers["Content-Disposition"] =
                'attachment; filename="' .
                VirtualFile::getSelectedFile()->client_name .
                '"';
        }

        if (config("webdav.cors.enabled", false)) {
            $headers = array_merge($headers, self::getCorsHeaders());
        }

        if (is_null($body) || is_string($body)) {
            return response($body, $status, $headers);
        }

        $contentLength = $this->httpResponse->getHeader("Content-Length");

        return response()->stream(
            function () use ($body, $contentLength): void {
                if (
                    is_numeric($contentLength) ||
                    (!is_null($contentLength) && ctype_digit($contentLength))
                ) {
                    echo stream_get_contents($body, intval($contentLength));
                } else {
                    echo stream_get_contents($body);
                }
            },
            $status,
            $headers
        );
    }

    /**
     * Get the full URL for the request.
     *
     * @param  Request  $request
     * @return string
     */
    private function fullUrl(Request $request) {
        $query = $request->getQueryString();
        $url = Str::finish($request->getPathInfo(), "/");

        return is_null($query) ? $url : $url . "?" . $query;
    }

    /**
     * Returns the HTTP-Headers required for CORS
     *
     * @return array
     */
    private static function getCorsHeaders() {
        return [
            "Access-Control-Allow-Origin" => config(
                "webdav.cors.allowed_origin"
            ),

            "Access-Control-Allow-Methods" => implode(", ", self::methods),

            "Access-Control-Expose-Headers" => config(
                "webdav.cors.expose_headers"
            ),

            "Access-Control-Request-Headers" => config(
                "webdav.cors.request_headers"
            ),

            "Access-Control-Allow-Credentials" => "true",
        ];
    }
}
