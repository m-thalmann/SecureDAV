<?php

use App\Http\Controllers\BrowseController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\Files\FileMoveController;
use App\Http\Controllers\Files\FileTrashController;
use App\Http\Controllers\Files\FileVersionController;
use App\Http\Controllers\Files\LatestFileVersionController;
use App\Http\Controllers\Files\SearchFileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Settings\LogoutBrowserSessionsController;
use App\Http\Controllers\Settings\ProfileSettingsController;
use App\Http\Controllers\Settings\WebDavSuspensionController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::permanentRedirect('/', RouteServiceProvider::HOME);
Route::permanentRedirect('/files', RouteServiceProvider::HOME);

Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::get('login', 'create')
        ->middleware('guest')
        ->name('login');

    Route::post('login', 'store')->middleware('guest');

    Route::post('logout', 'destroy')->name('logout');
});

Route::controller(RegisteredUserController::class)
    ->prefix('register')
    ->middleware(['guest', 'conditional:app.registration_enabled'])
    ->group(function () {
        Route::get('/', 'create')->name('register');
        Route::post('/', 'store');
    });

Route::as('password.')->group(function () {
    Route::controller(PasswordResetLinkController::class)
        ->prefix('forgot-password')
        ->group(function () {
            Route::get('/', 'create')
                ->middleware('guest')
                ->name('request');

            Route::post('/', 'store')
                ->middleware('guest')
                ->name('email');
        });

    Route::controller(NewPasswordController::class)->group(function () {
        Route::get('reset-password/{token}', 'create')
            ->middleware('guest')
            ->name('reset');

        Route::post('reset-password', 'store')
            ->middleware('guest')
            ->name('update');
    });

    Route::get('user/confirmed-password-status', [
        ConfirmedPasswordStatusController::class,
        'show',
    ])
        ->middleware('auth')
        ->name('confirmation');

    Route::controller(ConfirmablePasswordController::class)
        ->middleware('auth')
        ->group(function () {
            Route::get('user/confirm-password', 'show')->name(
                'show-confirmation'
            );

            Route::post('user/confirm-password', 'store')->name('confirm');
        });
});

Route::as('verification.')->group(function () {
    Route::get('email/verify', EmailVerificationPromptController::class)
        ->middleware('auth')
        ->name('notice');

    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['auth', 'signed', 'throttle:6,1'])
        ->name('verify');

    Route::post('email/verification-notification', [
        EmailVerificationNotificationController::class,
        'store',
    ])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('send');
});

Route::put('user/profile-information', [
    ProfileInformationController::class,
    'update',
])
    ->middleware('auth')
    ->name('user-profile-information.update');

Route::put('user/password', [PasswordController::class, 'update'])
    ->middleware('auth')
    ->name('user-password.update');

Route::as('two-factor.')->group(function () {
    Route::controller(TwoFactorAuthenticatedSessionController::class)
        ->middleware('guest')
        ->group(function () {
            Route::get('two-factor-challenge', 'create')->name('login');

            Route::post('two-factor-challenge', 'store')
                ->middleware('throttle:two-factor')
                ->name('authenticate');
        });

    Route::controller(TwoFactorAuthenticationController::class)
        ->prefix('user/two-factor-authentication')
        ->middleware(['auth', 'password.confirm'])
        ->group(function () {
            Route::post('/', 'store')->name('enable');

            Route::delete('/', 'destroy')->name('disable');
        });

    Route::post('user/confirmed-two-factor-authentication', [
        ConfirmedTwoFactorAuthenticationController::class,
        'store',
    ])
        ->middleware(['auth', 'password.confirm'])
        ->name('confirm');

    Route::post('user/two-factor-recovery-codes', [
        RecoveryCodeController::class,
        'store',
    ])
        ->middleware(['auth', 'password.confirm'])
        ->name('regenerate-recovery-codes');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('browse/{directory:uuid?}', BrowseController::class)->name(
        'browse.index'
    );

    Route::resource('directories', DirectoryController::class)
        ->scoped(['directory' => 'uuid'])
        ->except(['index', 'show']);

    Route::get('files/search', SearchFileController::class)->name(
        'files.search'
    );

    Route::controller(FileTrashController::class)
        ->prefix('files/trash')
        ->as('files.trash.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::put('/{file:uuid}', 'restore')
                ->withTrashed()
                ->name('restore');
            Route::delete('/{file:uuid}', 'destroy')
                ->withTrashed()
                ->name('destroy');
        });

    Route::put('files/{file:uuid}/auto-version-hours', [
        FileController::class,
        'updateAutoVersionHours',
    ])->name('files.auto-version-hours.update');

    Route::controller(FileMoveController::class)
        ->prefix('files/{file:uuid}/move')
        ->as('files.move')
        ->group(function () {
            Route::get('/', 'edit')->name('.edit');
            Route::put('/', 'update');
        });

    Route::resource('files', FileController::class)
        ->scoped(['file' => 'uuid'])
        ->except(['index']);

    Route::controller(LatestFileVersionController::class)
        ->prefix('files/{file:uuid}/versions/latest')
        ->as('files.versions.latest.')
        ->group(function () {
            Route::get('/', 'show')->name('show');

            Route::get('edit', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
        });

    Route::resource('files.versions', FileVersionController::class)
        ->scoped(['file' => 'uuid', 'version' => 'version'])
        ->except(['index']);


    // TODO: replace with resource controllers
    Route::view('backups', 'backups.index')->name('backups.index');

    Route::controller(NotificationController::class)
        ->prefix('notifications')
        ->as('notifications.')
        ->group(function () {
            Route::put('/', 'markAllAsRead')->name('mark-all-as-read');
            Route::delete('/', 'destroyAll')->name('destroy-all');
        });

    Route::resource('notifications', NotificationController::class)->only([
        'index',
        'show',
        'update',
        'destroy',
    ]);
});

Route::prefix('settings')
    ->as('settings.')
    ->middleware(['auth', 'password.confirm'])
    ->group(function () {
        Route::any(
            '/',
            fn() => redirect()->route('settings.profile.show')
        )->name('index');

        Route::prefix('profile')
            ->as('profile.')
            ->group(function () {
                Route::singleton('/', ProfileSettingsController::class)
                    ->only(['show', 'destroy'])
                    ->destroyable();

                Route::delete(
                    'sessions',
                    LogoutBrowserSessionsController::class
                )->name('sessions.destroy');
            });

        Route::put(
            'webdav-suspension',
            WebDavSuspensionController::class
        )->name('webdav-suspension');
    });

