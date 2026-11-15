# Employee Satisfaction Survey (lara-satisfaction)

Rebuild of the *satisfaction* application on **Laravel 13** following the architecture and
style of **Base-Apps-Merdeka** (modular `modules/` structure, menu-based RBAC, Tabler/Merdeka UI,
SweetAlert dialogs, activity & error logging, Microsoft/Google SSO).

Employees rate the services of the support service departments (the departments under the Support
Services Deputy General Manager in the MTI organization chart 2026: Supply Chain, Occupational Health
& Safety, Environmental, External Affairs, Finance, Interface) once per survey period. Each department
is split into sections, the units on the supervisor level of its chart (e.g. External Affairs:
*Asset Protection*); employees read the overview of every section (team
picture, work summary, role overview) and evaluate each one with the department's questions
(criticality, service quality, feedback). Answers are anonymous by design and administrators
analyse the results per department, section, question and company.

## Requirements

- PHP 8.3+ (tested on 8.4) with `pdo_sqlite` / `pdo_mysql` / `pdo_pgsql`, `mbstring`, `openssl`, `intl`
- Composer 2
- No Node.js build step: the Merdeka theme and vendor libraries are served from `public/assets`

## Setup

```bash
composer setup          # install, .env, app key, storage link, migrate + seed
php artisan serve       # or: composer dev
```

Uploaded images (department illustrations, landing page hero image) are re-encoded to WebP and
stored on the `public` disk, so `php artisan storage:link` is required on every server.

The seeder prints the super admin credentials (`SEED_ADMIN_EMAIL`, random password unless
`SEED_ADMIN_PASSWORD` is set). Set `SEED_DEMO_DATA=true` (never in production) to seed demo
employees and anonymous responses, then run `php artisan migrate:fresh --seed`.

Built-in roles: **Super Admin** (everything), **Survey Administrator** (survey management,
reports, user registration, master data) and **Employee** (dashboard
and *My Survey*; default role of SSO auto-registered users).

## Configuration

| Variable | Purpose |
| --- | --- |
| `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET`, `AZURE_TENANT_ID` | Microsoft SSO (button always shown, "Coming soon" until set) |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Google SSO (button hidden while empty) |
| `SSO_AUTO_REGISTER`, `SSO_ALLOWED_DOMAINS` | Create unknown SSO users of allowed domains as Employee |
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

1. In Microsoft Entra ID, create an app registration with the **Web** redirect URI
   `https://<host>/auth/microsoft/callback` and the delegated Microsoft Graph permission `User.Read`.
2. Create a client secret, then set `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET` and `AZURE_TENANT_ID`
   (directory id) in `.env` and run `php artisan config:cache` in production.
3. Users sign in with the account whose email matches their registered account; with
   `SSO_AUTO_REGISTER=true`, unknown users of `SSO_ALLOWED_DOMAINS` are created as Employee.

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
