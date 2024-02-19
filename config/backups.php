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
        // LogBackupProvider::class => [],
    ],

    // used for nicer routes when creating a backup configuration
    'aliases' => [
        'webdav' => WebDavBackupProvider::class,
    ],
];
