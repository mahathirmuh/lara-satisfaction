<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On Providers (Merdeka Group)
    |--------------------------------------------------------------------------
    |
    | Google SSO serves Merdeka Copper Gold accounts (Google Workspace) and
    | Microsoft (Azure AD / Entra ID) serves Merdeka Battery Materials
    | accounts. A provider is only offered on the login page when its
    | client id has been configured.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    /*
    | Microsoft Entra ID, with the same settings as the trilingual-docon
    | application: MS_SSO_*, else the MS_GRAPH_* app registration, else the
    | AZURE_* names. The redirect must match a redirect URI registered in
    | Entra exactly (scheme, host and port).
    */
    'azure' => [
        'client_id' => env('MS_SSO_CLIENT_ID', env('MS_GRAPH_CLIENT_ID', env('AZURE_CLIENT_ID'))),
        'client_secret' => env('MS_SSO_CLIENT_SECRET', env('MS_GRAPH_CLIENT_SECRET', env('AZURE_CLIENT_SECRET'))),
        'redirect' => env('MS_SSO_REDIRECT_URI', env('AZURE_REDIRECT_URI', '/auth/microsoft/callback')),
        'tenant' => env('MS_SSO_TENANT_ID', env('MS_GRAPH_TENANT_ID', env('AZURE_TENANT_ID', 'common'))),
        'proxy' => env('AZURE_PROXY'),
        // Graph leaves employeeId out of the default profile, so the profile
        // fields used at sign-in are asked for by name (User.Read covers them).
        'graph_url' => 'https://graph.microsoft.com/v1.0/me?$select=id,displayName,mail,userPrincipalName,jobTitle,employeeId,department',
    ],

];
