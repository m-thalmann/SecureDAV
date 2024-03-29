<?php

return [
    'cors' => [
        'enabled' => env('WEBDAV_CORS_ENABLED', false),
        'allowed_origins' => explode(
            ',',
            env('WEBDAV_CORS_ALLOWED_ORIGINS', '*')
        ),
        'expose_headers' => join(', ', [
            'Content-Location',
            'DAV',
            'ETag',
            'Link',
            'Lock-Token',
            'Vary',
            'Webdav-Location',
            'X-Sabre-Status',
        ]),
        'allowed_headers' => join(', ', [
            'Accept',
            'Accept-Language',
            'Access-Control-Request-Method',
            'Access-Control-Allow-Origin',
            'Authorization',
            'Brief',
            'Cache-Control',
            'Content-Length',
            'Content-Range',
            'Content-Type',
            'Date',
            'Depth',
            'Destination',
            'ETag',
            'Host',
            'If',
            'If-Match',
            'If-Modified-Since',
            'If-None-Match',
            'If-Range',
            'If-Unmodified-Since',
            'Location',
            'Lock-Token',
            'Origin',
            'Overwrite',
            'Prefer',
            'Range',
            'Schedule-Reply',
            'Timeout',
            'User-Agent',
        ]),
    ],
];
