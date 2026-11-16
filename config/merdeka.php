<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Branding
    |--------------------------------------------------------------------------
    |
    | Values rendered by the Merdeka layouts (page titles, sidebar, footer and
    | the public landing page).
    |
    */

    'app_name' => env('MERDEKA_APP_NAME', 'Employee Satisfaction Survey'),

    'app_tagline' => env('MERDEKA_APP_TAGLINE', 'Your Voice Shapes Our Collective Future'),

    'app_version' => env('MERDEKA_APP_VERSION', '1.0.0'),

    'company_name' => env('MERDEKA_COMPANY_NAME', 'Merdeka Group'),

    'company_url' => env('MERDEKA_COMPANY_URL', 'https://merdekacoppergold.com/'),

    /*
    |--------------------------------------------------------------------------
    | Role Based Access Control
    |--------------------------------------------------------------------------
    |
    | Roles, highest first: IT Admin > Super Admin > Manager > Employee. The IT
    | Admin role bypasses every menu/permission check and is only given
    | manually. The other roles follow the job title from the directory at
    | every sign-in (role job title rules); the default role is given when no
    | rule matches and to accounts registered automatically.
    |
    */

    'it_admin_role_id' => 1,

    'default_role_id' => (int) env('MERDEKA_DEFAULT_ROLE_ID', 3),

    'rbac_cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On and Network Accounts
    |--------------------------------------------------------------------------
    |
    | Users sign in with SSO (Microsoft / Google) or their network account
    | (LDAP, see config/ldap.php) only; there are no local passwords. Accounts
    | are matched on the company email. When auto register is enabled, unknown
    | users from an allowed domain are created with the default role on their
    | first sign-in instead of being rejected. Public mailbox domains are
    | always rejected for SSO.
    |
    */

    'sso' => [
        'auto_register' => (bool) env('SSO_AUTO_REGISTER', false),

        'allowed_domains' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SSO_ALLOWED_DOMAINS', 'merdekabattery.com,merdekacoppergold.com'))
        ))),

        'blocked_domains' => [
            'gmail.com',
            'googlemail.com',
            'yahoo.com',
            'yahoo.co.id',
            'outlook.com',
            'hotmail.com',
            'live.com',
            'icloud.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity & Error Logging
    |--------------------------------------------------------------------------
    |
    | Every state-changing web request is written to `activity_logs` and every
    | reported exception to `error_logs`. Sensitive keys are always redacted and
    | the payload of the listed routes is never stored, which keeps anonymous
    | survey answers out of the logs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Database Seeding
    |--------------------------------------------------------------------------
    |
    | First IT admin (the highest role, only given by hand): the company email
    | of a real person, who signs in with SSO or their network account. Demo
    | data (employees and anonymous responses) is only seeded when enabled,
    | never in production.
    |
    */

    'seed' => [
        'admin_email' => env('SEED_ADMIN_EMAIL'),
        'demo_data' => (bool) env('SEED_DEMO_DATA', false),
    ],

    'log' => [
        'activity' => (bool) env('LOG_ACTIVITY_ENABLED', true),

        'errors' => (bool) env('LOG_ERRORS_TO_DATABASE', true),

        'redacted_keys' => [
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'client_secret',
        ],

        'redacted_routes' => [
            'survey-form.store',
            'survey-form.draft',
        ],
    ],

];
