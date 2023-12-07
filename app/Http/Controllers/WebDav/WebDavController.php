<?php

namespace App\Http\Controllers\WebDav;

use App\Http\Controllers\Controller;
use App\Services\FileVersionService;
use App\WebDav;
use Illuminate\Http\Request;
use Sabre\DAV;
use Symfony\Component\HttpFoundation\Response;

class WebDavController extends Controller {
    protected WebDav\AuthBackend $authBackend;
    protected WebDav\LocksBackend $locksBackend;

    public function __construct(
        protected FileVersionService $fileVersionService
    ) {
        $this->authBackend = new WebDav\AuthBackend();
        $this->locksBackend = new WebDav\LocksBackend($this->authBackend);
    }

    public function cors(Request $request): Response {
        $server = $this->createServer(
            'webdav.cors',
            new DAV\SimpleCollection('cors')
        );

        $response = response()->noContent();

        if (config('webdav.cors.enabled', false)) {
            $response->withHeaders(
                $server->getCorsHeaders($request->header('Origin'))
            );
        }

        return $response;
    }

    public function files(Request $request): Response {
        $root = new WebDav\Filesystem\VirtualAllFilesDirectory(
            $this->authBackend,
            $this->fileVersionService
        );

        $server = $this->createServer('webdav.files.base', $root);

        return $this->execute($server, $request);
    }

    public function directories(Request $request): Response {
        $root = new WebDav\Filesystem\VirtualDirectory(
            $this->authBackend,
            $this->fileVersionService,
            directory: null
        );

        $server = $this->createServer('webdav.directories', $root);

        return $this->execute($server, $request);
    }

    /**
     * Executes the webdav server.
     *
     * @param WebDav\Server $server
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function execute(
        WebDav\Server $server,
        Request $request
    ): Response {
        $server->setRequest($request);

        $server->start();

        return $server->getResponse();
    }

    /**
     * Creates the webdav server.
     *
     * @param string $baseRoute
     * @param \Sabre\DAV\INode $rootNode
     *
     * @return \App\WebDav\Server
     */
    protected function createServer(
        string $baseRoute,
        DAV\INode $rootNode
    ): WebDav\Server {
        $server = new WebDav\Server(
            $this->authBackend,
            $this->locksBackend,
            route($baseRoute, null, absolute: false),
            new DAV\Tree($rootNode)
        );

        return $server;
    }
}

