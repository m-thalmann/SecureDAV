<?php

use App\Backups\WebDavBackupProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Backup settings
    |--------------------------------------------------------------------------
    */

    'providers' => [
        WebDavBackupProvider::class => [],
    ],

    'aliases' => [
        'webdav' => WebDavBackupProvider::class,
    ],
];

