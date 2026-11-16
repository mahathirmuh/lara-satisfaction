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
php artisan serve       # http://localhost:26113 (SERVER_PORT); or: composer dev
```

`php artisan serve` and Docker use the same port (26113), so the Microsoft redirect URI is the
same for both: run one of them at a time, and open the app at `http://localhost:26113`.

Uploaded images (department illustrations, landing page hero image) are re-encoded to WebP and
stored on the `public` disk, so `php artisan storage:link` is required on every server.

The first IT Admin is the company email in `SEED_ADMIN_EMAIL`: that person signs in with
Microsoft SSO or their network account. Running `php artisan db:seed --class=UserSeeder` again
also grants the role to an existing account, e.g. after its owner signed in once.
Set `SEED_DEMO_DATA=true` (never in production) to seed demo employees and anonymous responses
(they cannot sign in), then run `php artisan migrate:fresh --seed`.

### Roles

| Role | Given | Access |
| --- | --- | --- |
| **IT Admin** | By hand only (User Management) | Everything, including Role & Permission, Audit Log and System Log |
| **Super Admin** | Job title *Support Services Deputy General Manager* | Survey management, master data, users, every result |
| **Manager** | Job titles `*Manager`, `Deputy GM` (not `Assistant of *`) | *My Survey*, the results of the departments they manage |
| **Employee** | Any other job title (default role) | Dashboard and *My Survey* |

The job title comes from the directory (LDAP `title`, Microsoft `jobTitle`) and is refreshed at
every sign-in; the roles given by job title follow it, roles given by hand are kept. Rules are
edited in Role & Permission (one per line, `*` any text, `!` excludes) and applied to every user
at once. A manager sees the departments whose *Manager Job Titles* (Master Data > Departments)
match their job title. After upgrading an existing install, run
`php artisan migrate --force` then `php artisan db:seed --class=RoleSeeder --force`.

The employee ID is recorded the same way (LDAP `employeeID`, Microsoft `employeeId`) whenever
the directory has one; an empty directory value keeps the ID entered in User Management.

### Respondents (employee import)

The survey respondents are the employees of the assessed departments, imported from Active
Directory before they ever sign in: `php artisan users:sync-directory` (`--dry-run` to preview)
runs every day at 06:00 and from **User Management > Sync from Directory**. It takes the enabled
accounts with a company e-mail and an employee ID whose AD `department` is a department name or
one of its *Directory Department Names* (Master Data > Departments), and creates or refreshes
them (name, company, department, job title and roles, employee ID). Disabled AD accounts are
deactivated; people who move to another department stop being respondents; accounts deleted
here are not restored. The AD `company` maps to the companies (`MBMA` = MBM, empty = MTI; see
`config/ldap.php`). The department is also refreshed at sign-in. Reminders, Survey Progress and
the participation rate count these respondents only.

After the import, their photos are taken from the access card system (`CARD_DB_DATABASE`, e.g.
`DataDBEnt`, table `CardDB`, as in employee-central-hub): the card whose `StaffNo` is the
employee ID, not deleted and last updated. Only `StaffNo`, `Photo`, `Del_State` and `LastUpdate`
are read. Each photo is kept as a 160 px WebP thumbnail on the private disk
(`storage/app/private/user-photos`) and taken again only when its card changes; it is served by
`users.photo` to the person and to whoever manages users or follows the survey progress.

### Docker

```bash
docker compose up -d --build                                  # http://<host>:26113 (APP_PORT)
docker compose exec app php artisan migrate --seed --force    # first install only
```

The image (PHP 8.4 + Apache, Microsoft ODBC Driver 18 for SQL Server, LDAP) reads the project
`.env` and `certs/` (mounted read-only) and always runs in production mode. `app` serves the site,
`scheduler` runs the deadline reminders and draft pruning, and `queue` delivers the queued
reminders (`QUEUE_CONNECTION=database`; locally `sync` delivers at once). Uploaded images are kept in the
`storage-app` volume. Set `APP_URL` to the address users open, and
`DB_TRUST_SERVER_CERTIFICATE=true` when SQL Server uses its own (self-signed) certificate. After
changing `.env`, run `docker compose up -d --force-recreate`.

#### Production (`https://survey.merdekabattery.com`)

1. On the server, put the production environment file (kept locally as `.env.production`, never
   committed) next to `docker-compose.yml` **as `.env`**, and the CA certificate at `certs/ca.pem`.
   It sets `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://survey.merdekabattery.com`,
   `SESSION_SECURE_COOKIE=true` and `TRUSTED_PROXIES`.
2. Point the DNS name at a reverse proxy that holds the TLS certificate and forwards to
   `http://<server>:26113` with the `X-Forwarded-For`, `-Proto`, `-Host` and `-Port` headers. In
   production every link uses `APP_URL`; narrow `TRUSTED_PROXIES` from `*` to the proxy's IP when
   port 26113 is reachable from elsewhere.
3. Add `https://survey.merdekabattery.com/auth/microsoft/callback` as a **Web** redirect URI of
   the Entra app registration (the one in `MS_GRAPH_CLIENT_ID`, shared with trilingual-docon).
4. `docker compose up -d --build`, then `docker compose exec app php artisan migrate --force`.

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
notification (navbar bell) and an email. Each reminder day is sent once per survey. The same
reminder can be sent by hand from **Survey Management > Reminders**, to everyone who has not
completed an open survey or to chosen people (e.g. yourself, to test), with a history of every
send. Only people with an account here are reminded: accounts are created at the first sign-in
(or in User Management). Reminders are queued (`App\Services\Survey\SurveyReminders`).

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
2. Keep `LDAP_TLS_VERIFY=true`, put the internal CA certificate (PEM) at `certs/ca.pem` and set
   `LDAP_CA_CERT=certs/ca.pem` (relative to the project root: works locally and in Docker). The
   `certs/` folder is not committed. Locally, PHP needs `extension=ldap` in `php.ini`.
3. People type their network username (an email works too: the part before "@" is used). Their
   account here is found by the directory email (`mail`, else `userPrincipalName`).

## Versions

The version follows [Semantic Versioning 2.0.0](https://semver.org/): `MAJOR.MINOR.PATCH`, e.g. `0.9.0`. It is kept
in one place, the file `VERSION` at the project root: `config('merdeka.app_version')` reads it, and the footer of every
page shows it. What changed in each version, and what to do on the server (migrations, seeders, new settings), is in
[CHANGELOG.md](CHANGELOG.md) (Indonesian, [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), as in MTI Radio
Management). The version in the footer opens **Version history** for everyone signed in: a timeline of CHANGELOG.md
without its **Deploy** lines, newest first, each series (`0.9`, `0.8`, …) a node with its patches branching below it,
each version coloured by its kind of change. The headings are one of the six of Keep a Changelog: `Ditambahkan`,
`Diubah`, `Diperbaiki`, `Dihapus`, `Keamanan`, `Ditinggalkan` (another heading shows grey); the legend and the labels
stay in English (Minor release, Added, Changed, …). In English and Chinese the notes come from
[CHANGELOG.en.md](CHANGELOG.en.md) (headings `Added`, `Changed`, `Fixed`, `Removed`, `Security`, `Deprecated`, no
Deploy lines).

The public API of this application, what others rely on and can break without knowing it, is:

1. the `.env` settings (their names and meaning) and the Docker setup (services, port, volumes, mounted files);
2. the CSV export of Survey Report (its columns);
3. the Artisan commands meant to be run by hand or by a scheduler (`users:sync-directory` and its options).

The screens are not part of it.

| Bump | When |
| --- | --- |
| MAJOR (`x.0.0`) | An incompatible change of the public API: a setting renamed or removed without the old one still being read, a CSV column renamed or removed, a command or option removed, the Docker setup changed so that the old way no longer runs |
| MINOR (`x.y.0`) | Functionality added in a backward compatible manner: a new menu or page, a new optional setting, a new migration, a new CSV column at the end |
| PATCH (`x.y.z`) | Backward compatible bug fixes, no new functionality |

Major version zero (`0.y.z`) is the initial development: until the application is used in production anything may
change at any time, and a MINOR bump also covers incompatible changes. Version `1.0.0` is released when the
application goes into production at `https://survey.merdekabattery.com`; from then on the public API only changes
incompatibly with a new MAJOR version. Pre-release labels (`1.0.0-rc.1`) and build metadata (`+abc1234`) are not used.

Changes that only touch the README or tests do not bump the version. Releasing a version is one commit that raises
`VERSION` and adds its part on top of `CHANGELOG.md` and, in English, `CHANGELOG.en.md`; a released version is never
changed afterwards, a correction is a new version. `VersionTest` checks that `VERSION` is a Semantic Version and that
CHANGELOG.md starts with it, with every version once and newest first; `ChangelogDialogTest` checks the headings and
that CHANGELOG.en.md has every version with the same kinds of change. There is no "Unreleased" part: every change
pushed to `main` is a version. Versions 0.1.0 to 0.8.0 were numbered afterwards, when numbering started at 0.9.0.

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
                     anonymous submission; each survey shows Not Started -> In Progress
                     (opened, or "Saved as draft") -> Completed
  SurveyProgress     Where every employee stands with a survey (status only, never answers)
  SurveyManagement   Survey periods, questions (department + optional section) and reminders
  SurveyReport       Aggregated results per department/section, comments, CSV export
  UserManagement     Registered users, roles & menu/CRUD permission matrix
  AuditLog           Audit trail (as in MTI Radio Management): sign-ins, refused sign-ins and
                     sign-outs, every change in the administration with its values before and
                     after; table or Log activity, filters, details. Read only
  SystemLog          Activity log and error log viewers
resources/views/     main/ (layout, sidebar, navbar), auth/, components/global/ (x-global.*)
public/assets/css/   merdeka-ui.css: the design layer taken from MTI Radio Management (sidebar
                     and icon rail, top bar, path to the page, sign-in page, version history,
                     the rm-* recipes); custom.css: the Merdeka theme
```

- **RBAC**: every module route uses the `permission` middleware. A route maps to the menu whose
  route shares its key (`survey-report.comments` → menu `survey-report.index`); `.index` needs
  menu access, `GET` needs Read, `POST` Create, `PUT/PATCH` Update, `DELETE` Delete. Routes not
  registered as a menu are only reachable by the super admin. Use `@permission('menu-key', 'Create')`
  in Blade.
- **Anonymity**: `survey_participations` stores *who* completed a survey; `survey_responses` /
  `survey_answers` store *what* was answered without user, timestamps or ordered ids (random
  UUIDv4). Survey payloads are never written to the activity log nor to the audit trail, and results are hidden for
  groups below the threshold.
- **Audit trail**: a new administrative change is recorded with `App\Services\Audit\AuditLogger` after it is made:
  `log()` for something made or deleted, `logChanges()` with the model's `auditValues()` before and after (only the
  fields that moved are kept). Give a new action its words in `Modules\AuditLog\Services\AuditVocabulary`. Secrets are
  redacted by field name, entries cannot be changed or deleted, and nothing is written before the `audit_logs`
  migration has run.
- **Languages**: the interface is in English, Bahasa Indonesia and Simplified Chinese
  (`config('app.available_locales')`, switcher `x-global.locale-switcher`). The choice is kept in
  the session and on the user (`users.locale`, also used for their notifications); otherwise the
  browser language is used. English texts are the keys of `lang/id.json` and `lang/zh_CN.json`:
  wrap every new text in `__('…')` and add it to both files (`TranslationFilesTest` checks they
  match). Date formats are keys too (`translatedFormat(__('d M Y'))`). Data typed by admins
  (department/section names and descriptions, survey titles, custom questions) is shown as typed;
  the standard questions and the default landing page texts are translated.

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
