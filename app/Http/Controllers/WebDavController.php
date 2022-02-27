<?php

namespace App\Http\Controllers;

use App\WebDav;
use App\Http\Controllers\Controller;
use App\WebDav\VirtualDirectory;
use Illuminate\Http\Request;

class WebDavController extends Controller {
    public function server(Request $request) {
        $directory = new VirtualDirectory("/");

        $server = new WebDav\Server($directory);
        $server->setRequest($request);

        $server->start();

        return $server->getResponse();
    }
}
