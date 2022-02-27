<?php

use App\Http\Controllers\WebDavController;
use Illuminate\Support\Facades\Route;

Route::match(["GET", "PROPFIND", "LOCK", "UNLOCK", "POST", "PUT"], "{path?}", [
    WebDavController::class,
    "server",
])->where("path", ".*");
