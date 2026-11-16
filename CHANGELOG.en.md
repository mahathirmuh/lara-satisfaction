# Changelog (English)

The release notes of CHANGELOG.md in English, for the version history of the application when it is shown in English
or Chinese. Every version is written in both files, with the same headings; the lines for the server (**Deploy**) are
only in CHANGELOG.md.

## [1.8.0] - 2026-11-16

### Added

- **Version history**: the version number in the footer opens a timeline of every version, newest first, with what
  was added, changed, fixed or removed in each, in the language in use. The version that is running is marked.

### Changed

- The screens follow MTI Radio Management: a grey sidebar with section names (Survey, User, Settings) and groups that
  fold, which collapses to a rail of icons (the choice is remembered per browser, an icon shows its name when pointed
  at), and a white top bar with the page heading, EN | ID | 中文, the notification bell, and the user's name and roles.
- The path to the page ("Dashboard › Survey Management › Reminders") moved under the top bar and starts at the
  Dashboard.
- The sign-in page is the card of Radio Management: site photos that fade into one another at the left; the Merdeka
  Copper Gold, Merdeka Tsingshan Indonesia and Merdeka Battery Materials logos, the SSO buttons, the network account
  form and the language at the right. The access guide is a small dialog with a box for each way in.

## [1.7.0] - 2026-11-16

### Added

- The employees of the assessed departments are taken from Active Directory before they ever sign in: every day at
  06:00 and from **User Management > Sync from Directory** (`php artisan users:sync-directory`, `--dry-run` to
  preview). AD accounts that are disabled are deactivated too. Reminders, Survey Progress and the participation rate
  count these employees only.
- Employee photos from the access card system (CardDB), kept as small thumbnails and taken again only when the card
  changes. Photos show in User Registered, the top bar and My Profile; people without one show their initials.

## [1.6.0] - 2026-11-16

### Added

- **Survey Progress** menu (IT Admin and Super Admin): where every employee stands with the chosen survey (Not
  Started, In Progress, Completed), with totals, the completion rate and filters. Only the filling status is shown;
  answers stay anonymous.
- The status flow Not Started → In Progress → Completed on the survey cards, on the survey form (with "Saved as draft"
  as soon as a draft is saved) and on the thank-you page. On wide screens the status and progress sit in a side panel
  of the survey form.
- A path to the page at the top of every page.

## [1.5.0] - 2026-11-16

### Added

- **Survey Management > Reminders**: send survey reminders by hand to everyone who has not completed a survey or to
  chosen people (searched by name, email or employee ID). Each person gets an in-app notification and an e-mail in
  their own language; every send, by hand or scheduled, is listed with who sent it.
- Ready for production at `https://survey.merdekabattery.com`: every link uses `APP_URL` over HTTPS and the proxy in
  `TRUSTED_PROXIES` is trusted. A `queue` container delivers the queued reminders.

## [1.4.0] - 2026-11-16

### Added

- Human Resources is assessed as a support services department, with its ten sections.
- The interface switches between English, Bahasa Indonesia and 简体中文. The choice is kept on the account, so it
  follows people to their other devices and is used for their notifications.
- A **My Profile** page in the user menu: what the company directory says about you, your roles, the departments whose
  results you can see, your last sign-in, the surveys you completed, and the language.

## [1.3.0] - 2026-11-16

### Added

- Sign in with the network account (LDAP / Active Directory) beside Microsoft SSO.
- Roles follow the job title from the directory: IT Admin (given by hand only) > Super Admin (Support Services Deputy
  General Manager) > Manager (sees the results of the departments they manage) > Employee. The job title rules are set
  in Role & Permission.
- The employee ID is taken from the directory at every sign-in.

### Removed

- Local passwords: there is no email/password form, no Change Password page and no password field in User Management
  any more.
- The company Data Scope of roles: every role covers every company.

## [1.2.0] - 2026-11-16

### Added

- Runs on SQL Server.
- Docker Compose on port 26113: `app` serves the site and `scheduler` runs the deadline reminders and the draft
  clean-up. `php artisan serve` uses the same port, so the Microsoft redirect URI is the same for both.

## [1.1.0] - 2026-11-16

### Changed

- The assessed departments are the departments under the Support Services Deputy General Manager in the MTI
  organization chart 2026, each with its sections on the supervisor level.
- Departments and sections are kept in Master Data: running the seeders again never overwrites what was changed there,
  and a section that was evaluated cannot be deleted or moved (deactivate it instead).

## [1.0.0] - 2026-11-14

### Added

- The first version of Employee Satisfaction Survey: a public landing page, **My Survey** (the survey form per
  department and section, with drafts), **Survey Management** (survey periods and questions), **Survey Report** (results
  per department, section, question and company, CSV export, hidden below the anonymity threshold), the Dashboard,
  Master Data, User Management with roles and per-menu permissions, the System Log and deadline reminders.
