<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Network Directory (LDAP / Active Directory)
    |--------------------------------------------------------------------------
    |
    | Sign-in with the network account, next to Microsoft / Google SSO. There
    | is no local password: these are the only ways in. Same directory and
    | same settings as the trilingual-docon application.
    |
    | Nothing has a default that would work by accident: without LDAP_URL and
    | LDAP_BIND_DN the sign-in form is shown as "coming soon".
    |
    | Needs the PHP ldap extension (installed in the Docker image). Without it
    | network sign-in is reported as unavailable. On Windows, trilingual-docon
    | found that PHP 8.4.24 broke web requests (a Carbon class declaration
    | error) once extension=ldap was enabled; PHP 8.4.27 serves pages and
    | loads CarbonPeriod normally with it (checked 2026-11-16).
    |
    */

    'url' => env('LDAP_URL'),

    'base_dn' => env('LDAP_BASE_DN'),

    /*
    | Read-only account used to look a person up before their own password is
    | checked by binding as them. It never writes and never signs anybody in.
    */

    'bind_dn' => env('LDAP_BIND_DN'),

    'bind_password' => env('LDAP_BIND_PASSWORD'),

    /*
    | The narrowest unit holding everyone who may sign in, not the domain root:
    | a root-level search also reaches disabled and contractor accounts.
    | {username} is what the person typed before any "@", escaped.
    */

    'user_search_base' => env('LDAP_USER_SEARCH_BASE', env('LDAP_BASE_DN')),

    'user_search_filter' => env('LDAP_USER_SEARCH_FILTER', '(sAMAccountName={username})'),

    /*
    | Seconds; short on purpose so a directory that does not answer fails fast.
    */

    'timeout' => (int) env('LDAP_TIMEOUT', 5),

    /*
    | Verify the domain controller's certificate (ldaps://). Only switch it off
    | when the issuing internal CA is not trusted by the server: a simple bind
    | sends the user's password, so an unverified connection can be intercepted.
    */

    'tls_verify' => filter_var(env('LDAP_TLS_VERIFY', true), FILTER_VALIDATE_BOOL),

    /*
    | PEM file of the internal CA that issued the domain controller
    | certificate, e.g. /var/www/html/certs/ca.pem in Docker (./certs is
    | mounted there). Empty: the system trust store.
    */

    'ca_cert' => env('LDAP_CA_CERT'),

    /*
    | Directory attributes holding the username, email and display name. The
    | email falls back to userPrincipalName when the mail attribute is empty.
    */

    'attributes' => [
        'username' => env('LDAP_ATTR_USERNAME', 'samaccountname'),
        'mail' => env('LDAP_ATTR_MAIL', 'mail'),
        'name' => env('LDAP_ATTR_NAME', 'displayname'),
    ],

];
