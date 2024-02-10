<?php

namespace App\WebDav\Plugins;

use App\WebDav\Filesystem\VirtualFile;
use App\WebDav\Server;
use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class HandleGetPlugin extends DAV\ServerPlugin {
    protected Server $server;

    public function getPluginName(): string {
        return 'handle-get';
    }

    public function initialize(DAV\Server $server): void {
        $this->server = $server;

        // set priority to overwrite core plugin
        $server->on('method:GET', [$this, 'getHandler'], priority: 90);
    }

    /**
     * HTTP GET method handler
     *
     * @param \Sabre\HTTP\RequestInterface $request
     * @param \Sabre\HTTP\ResponseInterface $response
     *
     * @return bool Returns false if the method was handled, true otherwise.
     */
    public function getHandler(
        RequestInterface $request,
        ResponseInterface $response
    ): bool {
        $path = $request->getPath();
        $file = $this->server->tree->getNodeForPath($path);

        if (!$file instanceof VirtualFile) {
            return true;
        }

        $httpHeaders = [
            'Content-Type' => $file->getContentType(),
            'Content-Length' => $file->getSize(),
            'Last-Modified' => $file
                ->getLastModifiedDateTime()
                ->toRfc7231String(),
            'ETag' => $file->getETag(),
        ];

        if (!$httpHeaders['Content-Type']) {
            $httpHeaders['Content-Type'] = 'application/octet-stream';
        }

        if (config('webdav.cors.enabled', false)) {
            $httpHeaders = array_merge(
                $httpHeaders,
                $this->server->getCorsHeaders($request->getHeader('Origin'))
            );
        }

        $response->addHeaders($httpHeaders);
        $response->setStatus(200);

        $response->setBody(
            fn() => processResource(
                fopen('php://output', 'wb'),
                fn(mixed $output) => $file->writeToStream($output)
            )
        );

        return false;
    }

    public function getPluginInfo(): array {
        return [
            'name' => $this->getPluginName(),
            'description' => 'Adds custom handling for GET requests',
            'link' => null,
        ];
    }
}
