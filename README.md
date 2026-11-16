# Employee Satisfaction Survey (lara-satisfaction)

Rebuild of the *satisfaction* application on **Laravel 13** following the architecture and
style of **Base-Apps-Merdeka** (modular `modules/` structure, menu-based RBAC, Tabler/Merdeka UI,
SweetAlert dialogs, activity & error logging). Sign in is Microsoft/Google SSO or the network
account (LDAP / Active Directory) only: the application has no passwords of its own.

Employees rate the services of the support service departments (the departments under the Support
Services Deputy General Manager in the MTI organization chart 2026: Supply Chain, Occupational Health
& Safety, Environmental, External Affairs, Finance, Interface) once per survey period. Each department
is split into sections, the units on the supervisor level of its chart (e.g. External Affairs:
*Asset Protection*); employees read the overview of every section (team
picture, work summary, role overview) and evaluate each one with the department's questions
(criticality, service quality, feedback). Answers are anonymous by design and administrators
analyse the results per department, section, question and company.

## Requirements

- PHP 8.3+ (tested on 8.4) with `pdo_sqlsrv` / `pdo_sqlite` / `pdo_mysql` / `pdo_pgsql`, `gd` (WebP), `ldap`, `mbstring`, `openssl`, `intl`
- Composer 2
- No Node.js build step: the Merdeka theme and vendor libraries are served from `public/assets`

## Setup

```bash
composer setup          # install, .env, app key, storage link, migrate + seed
php artisan serve       # or: composer dev
```

Uploaded images (department illustrations, landing page hero image) are re-encoded to WebP and
stored on the `public` disk, so `php artisan storage:link` is required on every server.

The first super admin is the company email in `SEED_ADMIN_EMAIL`: that person signs in with
Microsoft SSO or their network account. Running `php artisan db:seed --class=UserSeeder` again
also grants the role to an existing account, e.g. after its owner signed in once as Employee.
Set `SEED_DEMO_DATA=true` (never in production) to seed demo employees and anonymous responses
(they cannot sign in), then run `php artisan migrate:fresh --seed`.

Built-in roles: **Super Admin** (everything), **Survey Administrator** (survey management,
reports, user registration, master data) and **Employee** (dashboard
and *My Survey*; default role of auto-registered SSO / LDAP users).

### Docker

```bash
docker compose up -d --build                                  # http://<host>:26113 (APP_PORT)
docker compose exec app php artisan migrate --seed --force    # first install only
```

The image (PHP 8.4 + Apache, Microsoft ODBC Driver 18 for SQL Server, LDAP) reads the project
`.env` and `certs/` (mounted read-only) and always runs in production mode. `app` serves the site
and `scheduler` runs the deadline reminders and draft pruning. Uploaded images are kept in the
`storage-app` volume. Set `APP_URL` to the address users open, and
`DB_TRUST_SERVER_CERTIFICATE=true` when SQL Server uses its own (self-signed) certificate. After
changing `.env`, run `docker compose up -d --force-recreate`.

## Configuration

| Variable | Purpose |
| --- | --- |
| `MS_SSO_*` / `MS_GRAPH_*` or `AZURE_*` | Microsoft SSO (button always shown, "Coming soon" until set) |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Google SSO (button hidden while empty) |
| `LDAP_*` | Network account sign-in (form shown as "Coming soon" until `LDAP_URL` and `LDAP_BIND_DN` are set) |
| `SSO_AUTO_REGISTER`, `SSO_ALLOWED_DOMAINS` | Register unknown SSO / LDAP users of allowed domains as Employee on their first sign-in |
| `SEED_ADMIN_EMAIL` | Company email of the first super admin |
| `SURVEY_MIN_GROUP_SIZE` | Anonymity threshold for showing results (default 5) |
| `SURVEY_REMINDER_DAYS` | Deadline reminder days before the end date (default `14`, e.g. `14,7,1`) |
| `MAIL_*`, `APP_URL` | SMTP for reminder emails; `APP_URL` is used for the links in emails |
| `LOG_ACTIVITY_ENABLED`, `LOG_ERRORS_TO_DATABASE` | Merdeka internal monitoring |

Production: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS with `SESSION_SECURE_COOKIE=true`,
then `php artisan optimize`. Run the scheduler (`* * * * * php artisan schedule:run`): it sends the
deadline reminders daily at 08:00 and prunes drafts of surveys that are no longer open.

Deadline reminders (`php artisan survey:send-deadline-reminders`, `--dry-run` to preview) go to
active users whose role may fill in surveys and who have not submitted yet, as an in-app
notification (navbar bell) and an email. Each reminder day is sent once per survey.

Survey drafts ("Save as Draft") are the only answers stored with a user: they are encrypted with
`APP_KEY`, never shown to administrators, excluded from the activity log and deleted on submission.

### Activating Microsoft SSO

1. In Microsoft Entra ID, use an app registration with the **Web** redirect URI
   `https://<host>/auth/microsoft/callback` and the delegated Microsoft Graph permission `User.Read`.
2. Set its client id, secret and tenant id in `.env`: `MS_SSO_*` (else `MS_GRAPH_*`, as in
   trilingual-docon) or `AZURE_*`, and `MS_SSO_REDIRECT_URI` to the exact registered redirect URI.
   Run `php artisan config:cache` in production.
3. Users sign in with the account whose email matches their registered account; with
   `SSO_AUTO_REGISTER=true`, unknown users of `SSO_ALLOWED_DOMAINS` are created as Employee.

### Activating network sign-in (LDAP)

1. Set `LDAP_URL` (`ldaps://<domain controller>:636`), `LDAP_BASE_DN`, the read-only service
   account `LDAP_BIND_DN` / `LDAP_BIND_PASSWORD` and `LDAP_USER_SEARCH_BASE`, the narrowest unit
   holding everyone who may sign in. Same settings as trilingual-docon.
2. Keep `LDAP_TLS_VERIFY=true` and put the internal CA certificate (PEM) at `certs/ca.pem`, with
   `LDAP_CA_CERT=/var/www/html/certs/ca.pem` in Docker. The `certs/` folder is not committed.
3. People type their network username (an email works too: the part before "@" is used). Their
   account here is found by the directory email (`mail`, else `userPrincipalName`).

## Architecture

```text
app/                 Core: models (UserManagement, Organization, Survey, Content, Log), RBAC
                     middleware, helpers (Alert, Apps, LogApps, FileApps), module service
                     provider, make:module commands
modules/{Module}/    Http/Controllers, Http/Requests, Repositories (Interface + Repository),
                     Services, Providers, Resources/views ("{Module}::"), Routes/web
  Dashboard          Open surveys + insights of the featured survey
  MasterData         Departments (with image), sections (with overview), landing page content
  SurveyForm         Employee wizard (one step per department, every section evaluated) and
                     anonymous submission
  SurveyManagement   Survey periods and questions (department + optional section)
  SurveyReport       Aggregated results per department/section, comments, CSV export
  UserManagement     Registered users, roles & menu/CRUD permission matrix
  SystemLog          Activity log and error log viewers
resources/views/     main/ (layout, sidebar, navbar), auth/, components/global/ (x-global.*)
```

- **RBAC**: every module route uses the `permission` middleware. A route maps to the menu whose
  route shares its key (`survey-report.comments` → menu `survey-report.index`); `.index` needs
  menu access, `GET` needs Read, `POST` Create, `PUT/PATCH` Update, `DELETE` Delete. Routes not
  registered as a menu are only reachable by the super admin. Use `@permission('menu-key', 'Create')`
  in Blade.
- **Anonymity**: `survey_participations` stores *who* completed a survey; `survey_responses` /
  `survey_answers` store *what* was answered without user, timestamps or ordered ids (random
  UUIDv4). Survey payloads are never written to the activity log and results are hidden for
  groups below the threshold.

## New module

```bash
php artisan make:module TrainingRecord
php artisan make:module-component TrainingRecord repository Certificate
```

Then register a menu with the route `training-record.index` (see `MenuSeeder`) and grant it in
*Role & Permission*.

## Quality

```bash
php artisan test        # feature test suite
vendor/bin/pint         # code style
composer audit          # dependency advisories
```

Front-end libraries in `public/assets/libs` (audited with `npm audit`, 0 advisories): jQuery 3.7.1,
Select2 4.1.0-rc.0, ApexCharts 3.40.0, SweetAlert2 11.26.25, Tabler 1.0.0-beta19 (Merdeka build),
Tabler Icons 2.36.0.
