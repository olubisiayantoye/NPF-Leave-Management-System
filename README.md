# Nominal Roll Management System (NRMS)
IGP-Secretariat, Nigeria Police Force

## What's included in this build

A working, deployable core of the system described in the spec:

- Public landing page (`index.php`) with staff login
- Authentication: login, logout, forgot/reset password, session timeout,
  rate-limited login attempts, CSRF protection, audit logging
- Role-based access control (5 roles from the spec, enforced per-page)
- Dashboard: live stats, retirement alerts, birthday notifications,
  recent transfers/registrations, department/rank charts
- Personnel module: register, edit, view, archive/restore, photo upload,
  Next of Kin (2 records per profile), search + filter + pagination
- Transfers module: manual transfer form + auto-logged transfer history
  when a personnel record's department changes
- Verification module: quick lookup by AP number/name/phone/employee
  code/IPPIS number, printable verification slip
- Nominal Roll reports: filterable report builder (including Rank and
  Geopolitical Zone filters), native .xlsx export, print-to-PDF view
- Bulk upload: native .xlsx import with a downloadable Excel template,
  validation, duplicate detection, per-row error report, import history log
- User management: add/edit users, assign roles, enable/disable accounts
- Audit trail viewer (Super Administrator only)
- **Monthly Allowance Management module** (`/allowance/`): a separate
  register of personnel for monthly allowance payment, independent of the
  main nominal roll table — register/edit/view/delete, search + filter,
  bulk upload from `.xlsx` **or** `.csv`, filterable report (including Rank)
  with `.xlsx` export and print view, unique F-AP Number enforced at the
  database level, full activity logging. Fields follow the required order
  everywhere (list, form, report, export): F-AP Number, Rank, Name, Account
  Number, Bank Name, Employee Code, Section. See "Monthly Allowance
  Management module" below.
- **Settings module** (`/settings/`): Role Management (create/edit/delete
  roles), a Permissions matrix per role, System Settings (site name,
  pagination size, session timeout — actually read by the app), and a
  generic Lookup Management screen covering Rank, Bank, Section, Department,
  Geopolitical Zone, Tribe, Command, PFA, State, and LGA, each protected
  from deletion while still referenced by existing records. See "Settings
  module" below.
- **Front Page Featured Staff**: administrators can feature any existing
  personnel profile (photo, name, rank) on the public landing page, managed
  either from Settings or with a one-click toggle right on that person's
  profile page. No duplicate profiles possible (enforced at the database
  level), and the front page always reflects the live profile data.

## What's simplified vs. the original spec, and why

The spec's tech stack calls for CodeIgniter 4 + PhpSpreadsheet + Dompdf,
all installed via Composer. This build was produced in an offline sandbox
with no package-manager access, so it uses:

- **Plain PHP + PDO** instead of CodeIgniter 4 — same MVC-ish organization
  (config/includes/modules), zero framework dependency. Porting into CI4
  later is straightforward since the SQL and business logic are already
  isolated per page.
- **Native `.xlsx` reader/writer built from scratch** (`includes/xlsx_reader.php`,
  `includes/xlsx_writer.php`) using PHP's bundled `ZipArchive` + `SimpleXML`,
  instead of PhpSpreadsheet — no Composer dependency required. Used for bulk
  upload (`bulk_upload.php`, `bulk_upload_template.php`) and for the Nominal
  Roll Excel export (`reports/export_xlsx.php`). It covers what those need
  (shared strings, inline strings, numeric/date-serial cells) but isn't a
  general-purpose spreadsheet library. If you later add Composer to the
  deployment, swapping in `PhpSpreadsheet\Reader\Xlsx` / `Writer\Xlsx` is a
  contained change to those three files — everything else is unaffected.
- **Browser print-to-PDF** (`window.print()`) instead of Dompdf-generated
  PDFs — works everywhere with no server dependency; swap in Dompdf later
  if you need PDFs generated without user interaction (e.g. scheduled
  email reports).

Not yet built (flagged as "Future Enhancements" in the original spec
anyway): biometric/fingerprint integration, QR code ID cards, native
mobile app, SMS notifications, facial recognition, REST API, GIS mapping,
2FA. Two-factor columns exist in the `users` table so 2FA can be added
without a schema change.

## Setup

1. Create the database:
   ```
   mysql -u root -p < database/schema.sql
   ```
2. Copy this folder to your web root (e.g. `/var/www/html/nrms`).
3. Set your DB credentials as environment variables (recommended) or edit
   `config/database.php` directly:
   ```
   NRMS_DB_HOST=127.0.0.1
   NRMS_DB_NAME=nrms_db
   NRMS_DB_USER=your_user
   NRMS_DB_PASS=your_password
   ```
4. Make `uploads/photos` writable by the web server:
   ```
   chmod -R 755 uploads
   ```
5. If the app is served from the domain root (e.g. `https://nrms.example.com/`),
   skip this step — `BASE_URL` defaults to empty. If it's served from a
   subdirectory instead (e.g. `https://example.com/nrms/`), set:
   ```
   NRMS_BASE_URL=/nrms
   ```
   Every link, redirect, form action, and asset/photo path in the app is
   built through the `url()` helper (`config/app.php` + `includes/functions.php`),
   so this one variable is the only thing that needs to change when moving
   the app between a domain root and a subdirectory.
6. Visit `/setup.php` in your browser **once** to create the first Super
   Administrator account. **Delete `setup.php` immediately afterward** —
   it refuses to run again once a user exists, but there's no reason to
   leave it reachable.
7. Log in at `/login.php` and start populating lookup tables (banks,
   PFAs, commands, LGAs) that weren't seeded, via direct SQL or by
   extending `users/list.php`-style CRUD pages for them.
8. Serve over HTTPS in production, and uncomment the `'secure' => true`
   cookie flag in `includes/functions.php`'s `start_secure_session()`.

## Monthly Allowance Management module

A self-contained module for registering personnel who receive a monthly
allowance — deliberately kept as its own table (`monthly_allowance_personnel`)
rather than reusing `personnel`, since this list can include people who
aren't (yet) on the main nominal roll (e.g. attached or contract staff).

**Files:** `allowance/list.php` (record management: search, filter, edit,
delete links), `add.php` / `edit.php` / `save.php` (registration form,
validation, duplicate F-AP Number prevention), `view.php` (record detail),
`delete.php` (hard delete — the full record is written to the audit trail
first, so there's still a trace after removal), `bulk_upload.php` +
`bulk_upload_template.php` (accepts both `.xlsx` and `.csv`, matching the
column order F-AP Number / Rank / Name / Account Number / Bank Name /
Employee Code / Section), `report.php` + `export_xlsx.php` + `print_view.php`
(filterable report, Excel export, print-to-PDF).

**Data model:** Rank and Bank Name are foreign keys into the existing
`ranks` and `banks` lookup tables (same dropdowns as the Personnel module,
for consistency and to avoid duplicate reference data). Section is a plain
text field with autocomplete suggestions drawn from values already in use,
since it isn't one of the app's existing lookup tables.

**Permissions:** mirrors the Personnel module's pattern — Super
Administrator / Administrator / Documentation Officer can register and
edit; Super Administrator / Administrator only can bulk-upload and delete;
every logged-in role can view records and run reports. Menu links in the
sidebar are gated the same way, so a role that can't perform an action
won't see the link either.

**Migration:** if you're adding this module to an NRMS install that
already has a database, run:
```
mysql -u root -p nrms_db < database/migration_monthly_allowance.sql
```
This creates `monthly_allowance_personnel` and adds a `module` column to
the existing `import_history` table (defaulting existing rows to
`'personnel'`) so both modules' bulk-upload activity share one log, filtered
by module. Brand-new installs get both already via `schema.sql` — no
separate migration step needed.

For Role Management/Settings, Next of Kin, and Front Page Featured Staff,
run the second migration (also already folded into `schema.sql` for new
installs):
```
mysql -u root -p nrms_db < database/migration_role_settings_kin_featured.sql
```
This adds the `sections`, `personnel_next_of_kin`, and `featured_staff`
tables, a uniqueness constraint on `permissions`, and seeds default
permission grants + starter system settings.

## Settings module

`/settings/` covers Role Management, Permissions, System Settings, Lookup
Management, and Front Page Featured Staff. **Important limitation to know
about:** this module introduces a genuine permission-driven access system
(`has_permission()` / `require_permission()` in `includes/functions.php`,
backed by the `permissions` table) — but it's layered on top of, not a
replacement for, the app's original `require_role(['Super Administrator', ...])`
checks used throughout Personnel, Allowance, Transfers, User Management, and
Audit Trail. That means:

- A **custom role** you create from Settings > Role Management will
  immediately work for anything gated by `require_permission()` — right now
  that's the Settings area itself (once you grant it permissions).
- It will **not** automatically gain access to core modules like Personnel
  or Allowance, since those pages check for the 5 built-in role names
  directly. Migrating those checks to `require_permission()` was out of
  scope for this pass — each page would need its own permission key added
  to the `$permissionCatalog` in `settings/permissions.php` and its
  `require_role([...])` call swapped for `require_permission('...')`. The
  plumbing (the `permissions` table, the helpers, the UI) is all in place
  for that to be a mechanical follow-up.
- The 5 built-in roles (Super Administrator, Administrator, Documentation
  Officer, Verification Officer, Read-only User) keep working exactly as
  before everywhere else in the app.

**Lookup Management** (`settings/lookups.php`) is a single generic screen
that handles Rank, Bank, Section, Department, Geopolitical Zone, Tribe,
Command, PFA, State, and Local Government Area — add/edit/delete, with
delete blocked whenever a value is still referenced by an existing
Personnel or Allowance record (checked against the same foreign keys the
schema already enforces).

**System Settings** currently exposes three settings the app actually
reads: `site_name` (page titles, front page), `records_per_page`
(Personnel and Allowance list pagination), and `session_timeout_minutes`
(inactivity logout). Add a new setting by adding it to the `$fields` array
in `settings/system_settings.php` and reading it elsewhere with
`setting('your_key', $default)`.

## Next of Kin

Each personnel profile supports exactly 2 Next of Kin records (full name,
relationship, email, residential address, phone, alternative phone),
enforced at the database level with a unique `(personnel_id, kin_number)`
constraint. The section appears in the registration/edit form and the
profile view, positioned immediately before Remarks in both. A kin slot is
only saved if its Full Name is filled in; leaving both slots blank on
create is fine, and clearing all fields on edit removes that slot's record.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `fileinfo` extensions
- MySQL 8 / MariaDB 10.5+
- Apache with `mod_rewrite` (for `.htaccess`) or equivalent Nginx rules
