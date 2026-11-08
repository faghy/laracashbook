<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | IP addresses (comma separated, CIDR allowed) of the reverse proxies in
    | front of the application, e.g. "10.0.0.200". Requests coming from them
    | are trusted for X-Forwarded-For/Proto/Host/Port, so the client IP used by
    | the login rate limits is the visitor's and not the proxy's.
    | "*" trusts any caller: use it only if the app is not reachable directly.
    | Empty: no proxy is trusted.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
