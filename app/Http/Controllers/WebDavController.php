<?php

namespace App\Http\Controllers;

use App\Services\FileVersionService;
use App\WebDav;
use Illuminate\Http\Request;
use Sabre\DAV;
use Symfony\Component\HttpFoundation\Response;

class WebDavController extends Controller {
    protected WebDav\AuthBackend $authBackend;

    public function __construct(
        protected FileVersionService $fileVersionService
    ) {
        $this->authBackend = new WebDav\AuthBackend();
    }

    public function files(Request $request): Response {
        $root = new WebDav\Filesystem\VirtualAllFilesDirectory(
            $this->authBackend,
            $this->fileVersionService
        );

        return $this->execute($request, 'webdav.files.base', $root);
    }

    public function directories(Request $request): Response {
        $root = new WebDav\Filesystem\VirtualDirectory(
            $this->authBackend,
            $this->fileVersionService,
            directory: null
        );

        return $this->execute($request, 'webdav.directories', $root);
    }

    /**
     * Creates the server and executes it.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $baseRoute
     * @param \Sabre\DAV\INode $rootNode
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function execute(
        Request $request,
        string $baseRoute,
        DAV\INode $rootNode
    ): Response {
        $server = new WebDav\Server(
            $this->authBackend,
            route($baseRoute, null, absolute: false),
            new DAV\Tree($rootNode)
        );

        $server->setRequest($request);

        $server->start();

        return $server->getResponse();
    }
}

