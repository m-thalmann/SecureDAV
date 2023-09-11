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

Route::middleware(['auth', 'verified'])->group(function () {
    // TODO: replace with resource controllers
    Route::view('files', 'files.index')->name('files.index');
    Route::view('access', 'access.index')->name('access.index');
    Route::view('backups', 'backups.index')->name('backups.index');
});

Route::prefix('settings')
    ->as('settings.')
    ->middleware(['auth', 'password.confirm'])
    ->group(function () {
        Route::any(
            '/',
            fn() => redirect()->route('settings.profile.show')
        )->name('index');

        Route::singleton('profile', ProfileSettingsController::class)
            ->only(['show', 'destroy'])
            ->destroyable();
    });

