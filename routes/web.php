<?php

use App\Http\Controllers\Settings\ProfileSettingsController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::permanentRedirect('/', RouteServiceProvider::HOME);

Route::middleware('auth')->group(function () {
    // TODO: replace with resource controllers
    Route::view('files', 'files.index')->name('files.index');
    Route::view('access', 'access.index')->name('access.index');
    Route::view('backups', 'backups.index')->name('backups.index');

    Route::prefix('settings')
        ->as('settings.')
        ->group(function () {
            Route::any(
                '/',
                fn() => redirect()->route('settings.profile.edit')
            )->name('index');

            Route::controller(ProfileSettingsController::class)
                ->prefix('profile')
                ->as('profile.')
                ->group(function () {
                    Route::get('', 'edit')->name('edit');
                });
        });
});

