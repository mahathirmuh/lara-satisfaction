<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | In production the application runs behind a reverse proxy that serves
    | https://survey.merdekabattery.com and forwards to the container on
    | APP_PORT. Trusting it lets Laravel read the X-Forwarded-* headers: the
    | real client IP (activity log, sign-in throttling) and HTTPS.
    |
    | Comma separated IP addresses or CIDR ranges of the proxy, or "*" for the
    | machine calling the application (only when the port is not reachable
    | from anywhere else). Empty: no proxy is trusted.
    |
    | Read by Illuminate\Http\Middleware\TrustProxies.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
