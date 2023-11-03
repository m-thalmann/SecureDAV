<?php

/*
|--------------------------------------------------------------------------
| DAV Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\WebDavController;
use App\WebDav;
use Illuminate\Support\Facades\Route;

Route::controller(WebDavController::class)
    ->as('webdav.')
    ->group(function () {
        Route::options('{path?}', 'cors')
            ->where('path', '.*')
            ->name('cors');

        Route::prefix('files')
            ->as('files')
            ->group(function () {
                // used to create url with named parameters
                Route::match(WebDav\Server::METHODS, '{uuid}/{name}', 'files');

                // used as a fallback route
                Route::match(WebDav\Server::METHODS, '{path?}', 'files')
                    ->where('path', '.*')
                    ->name('.base');
            });

        Route::match(
            WebDav\Server::METHODS,
            'directories/{path?}',
            'directories'
        )
            ->where('path', '.*')
            ->name('directories');

        Route::redirect('/', 'files')->name('default');
    });
