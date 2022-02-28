<?php

return [
    "cors" => [
        "enabled" => env("WEBDAV_CORS_ENABLED", false),
        "allowed_origin" => env("WEBDAV_CORS_ALLOWED_ORIGIN", "*"),
        "expose_headers" => "ETag",
        "request_headers" =>
            "origin, content-type, cache-control, accept, authorization, if-match, destination, overwrite",
    ],
];
