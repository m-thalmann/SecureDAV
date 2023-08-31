<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
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

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('logout', LogoutController::class)->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::controller(LoginController::class)->group(function () {
        Route::get('login', 'create')->name('login');
        Route::post('login', 'store');
    });

    Route::controller(PasswordResetController::class)->group(function () {
        Route::get('forgot-password', 'create')->name('password.request');
        Route::post('forgot-password', 'store')->name('password.email');

        Route::get('reset-password/{token}', 'edit')->name('password.reset');
        Route::post('reset-password', 'update')->name('password.store');
    });
});
