<?php

use App\Http\Controllers\WebDavController;
use Illuminate\Support\Facades\Route;

Route::match(
    ["GET", "PROPFIND", "PROPPATCH", "LOCK", "UNLOCK", "POST"],
    "{path?}",
    [WebDavController::class, "server"]
)->where("path", ".*");
