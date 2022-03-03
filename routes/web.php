<?php

use App\Http\Controllers\Access\AccessController;
use App\Http\Controllers\Files\AddFileController;
use App\Http\Controllers\Files\FilesController;
use App\Http\Controllers\Files\FileVersionsController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SettingsPasswordController;
use App\Models\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::permanentRedirect("/", "dashboard");

Route::middleware(["auth", "verified"])->group(function () {
    /*
     * DASHBOARD
     */

    Route::view("/dashboard", "dashboard")->name("dashboard");

    Route::middleware(["password.confirm"])->group(function () {
        /*
         * FILES
         */

        Route::controller(FilesController::class)->group(function () {
            Route::view("/files", "files.index")->name("files");

            Route::view("/files/trash", "files.trash")->name("files.trash");

            Route::delete("/files/trash", "clearTrash")->name(
                "files.trash.clear"
            );

            Route::delete("/files/trash/{file}", "deleteFromTrash")
                ->name("files.trash.delete")
                ->withTrashed()
                ->middleware("can:forceDelete,file");

            Route::put("/files/trash/{file}", "restoreFromTrash")
                ->name("files.trash.restore")
                ->withTrashed()
                ->middleware("can:restore,file");

            Route::delete("/files/{file}", "delete")
                ->name("files.delete")
                ->middleware("can:delete,file");

            Route::get("/files/{file}/details", "createDetails")
                ->name("files.details")
                ->middleware("can:view,file");
        });

        Route::controller(AddFileController::class)->group(function () {
            Route::view("/files/add", "files.add")->name("files.add");
            Route::post("/files/add", "store")->name("files.add.store");
        });

        Route::get("/files/{file}", function (File $file) {
            return Redirect::route("files.details", ["file" => $file->uuid]);
        });

        /*
         * FILE-VERSIONS
         */

        Route::get("/files/{file}/versions/latest/download", function (
            File $file
        ) {
            return Redirect::route("files.versions.download", [
                "file" => $file->uuid,
                "version" => $file->getLastVersion()?->version,
            ]);
        })->name("files.versions.download.latest");

        Route::controller(FileVersionsController::class)->group(function () {
            Route::get(
                "/files/{file}/versions/{version:version}/download",
                "download"
            )
                ->name("files.versions.download")
                ->middleware("can:view,version");

            Route::get("/files/{file}/versions/upload", "createUpload")
                ->name("files.versions.upload.view")
                ->middleware("can:update,file");

            Route::put("/files/{file}/versions", "upload")
                ->name("files.versions.upload")
                ->middleware("can:update,file");

            Route::delete("/files/{file}/versions/{version:version}", "delete")
                ->name("files.versions.delete")
                ->middleware("can:delete,version");

            Route::post("/files/{file}/versions", "store")
                ->name("files.versions.store")
                ->middleware("can:update,file");

            Route::get("/files/{file}/trash", "createTrash")
                ->name("files.versions.trash")
                ->middleware("can:view,file");

            Route::delete("/files/{file}/trash", "clearTrash")
                ->name("versions.trash.clear")
                ->middleware("can:forceDelete,file");

            Route::delete("/versions/trash/{version}", "deleteFromTrash")
                ->name("versions.trash.delete")
                ->withTrashed()
                ->middleware("can:forceDelete,version");

            Route::put("/versions/trash/{version}", "restoreFromTrash")
                ->name("versions.trash.restore")
                ->withTrashed()
                ->middleware("can:restore,version");
        });

        /*
         * ACCESS
         */

        Route::controller(AccessController::class)->group(function () {
            Route::view("/access", "access.index")->name("access");
        });

        /*
         * BACKUPS
         */

        Route::view("/backups", "backups")->name("backups");

        /*
         * SETTINGS
         */

        Route::controller(SettingsController::class)->group(function () {
            Route::view("/settings", "settings.index")->name("settings");
            Route::put("/settings", "update")->name("settings.update");
            Route::delete("/settings", "destroy")->name("settings.destroy");
        });

        Route::controller(SettingsPasswordController::class)->group(
            function () {
                Route::get("/settings/password", "create")->name(
                    "settings.password"
                );
                Route::put("/settings/password", "update")->name(
                    "settings.password.update"
                );
            }
        );
    });
});

require __DIR__ . "/auth.php";
