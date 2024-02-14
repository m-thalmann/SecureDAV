<?php

use App\Backups\LogBackupProvider;
use App\Backups\WebDavBackupProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Backup settings
    |--------------------------------------------------------------------------
    */

    'providers' => [
        WebDavBackupProvider::class => [],
        LogBackupProvider::class => [],
    ],

    'aliases' => [
        'webdav' => WebDavBackupProvider::class,
    ],
];
