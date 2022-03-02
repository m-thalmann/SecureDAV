<?php

use App\Http\Controllers\WebDavController;
use App\WebDav\Server;
use Illuminate\Support\Facades\Route;

Route::match(Server::methods, "{path?}", [WebDavController::class, "server"])
    ->where("path", ".*")
    ->name("webdav");
