# NPF Leave Management System (NPF-LMS)

Pure PHP 8.2+ / MySQL 8+ / Bootstrap 5. No framework, no Composer
dependencies — anywhere. XLSX import/export is hand-built on PHP's
built-in `ZipArchive` and `DOMDocument`; PDF export is hand-built OOXML-
free PDF 1.4; the SMTP mail client and TOTP two-factor auth are both
implemented from scratch on nothing but PHP's standard library.

## 1. Requirements

- PHP 8.2+ with the `pdo_mysql`, `zip`, and `fileinfo` extensions enabled
  (all three are standard in most PHP installs; `fileinfo` is used to
  verify uploaded attachment types by content, not just filename)
- MySQL 8+ (or MariaDB 10.6+)
- A web server pointed at this folder (Apache/Nginx, or `php -S
  localhost:8000` for quick local testing)
- Optional: a local MTA (sendmail/postfix) or an external SMTP server, if
  you want email notifications — the app runs completely fine without one

## 2. Set up the database

**Fresh install** — one file has everything (schema, roles, leave types,
permissions):

```bash
mysql -u root -p < database/npf_leave_management.sql
```

To also get a fully populated demo environment (recommended for trying
the system out):

```bash
mysql -u root -p < database/demo_seed.sql
```

**Demo login — every demo account uses the same password:**

| Username     | Role                        | Password     |
|--------------|-----------------------------|--------------|
| `superadmin` | Super Admin                 | `Demo@12345` |
| `j.bello`    | Administrator                | `Demo@12345` |
| `a.musa`     | I-Admin                     | `Demo@12345` |
| `o.kalu`     | Administration Officer (SOA) | `Demo@12345` |
| `t.yaro`     | PSO I                        | `Demo@12345` |
| `b.nnamdi`   | PSO II                       | `Demo@12345` |
| `c.eze`      | Read-Only                    | `Demo@12345` |

⚠️ **Demo data only.** Change these passwords (Administration → Users) or
delete the demo accounts entirely before this ever runs anywhere real.

The demo seed adds 10 personnel across 6 departments, public holidays
for the current year, and 7 leave applications covering every stage of
the approval chain — draft, two pending Administration Officer review
(sign in as `o.kalu` to review them), one pending a PSO's final decision
(sign in as `b.nnamdi`), disapproved, approved-and-currently-on-leave
(with a generated signal), and a fully completed one with resumption
recorded.

If you'd rather start from a genuinely empty system, skip `demo_seed.sql`
and create your own super admin instead — see the commented-out block at
the bottom of `database/npf_leave_management.sql` for the exact
`password_hash()` command to generate a real one.

**Upgrading an existing install?** Run whichever of these you're missing,
in order — each is idempotent (safe to run more than once), but
`migration_workflow_v2.sql` expects the roles/permissions from
`migration_permissions_seed.sql` to already exist:

```bash
mysql -u root -p < database/migration_mfa.sql
mysql -u root -p < database/migration_leave_form_fields.sql
mysql -u root -p < database/migration_permissions_seed.sql
mysql -u root -p < database/migration_attachments_and_settings.sql
mysql -u root -p < database/migration_workflow_v2.sql
```

`migration_workflow_v2.sql` **renames** your existing `sectional_head`
role to `i_admin` and `hod` to `administration_officer` — it does not
drop them — so every existing user keeps their access. It adds `pso_i`
and `pso_ii` as new, empty roles: assign your actual PSOs to one or the
other afterward under Administration → Users. Any application that was
`pending` moves to `pending_soa` (the nearest equivalent stage).

## 3. Configure the app

Edit `config/config.php`:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` — your database credentials
- `BASE_URL` auto-detects itself from the request in development. For
  production, uncomment and set it explicitly:
  ```php
  define('BASE_URL', 'https://leave.npf.gov.ng');
  ```

## 4. Run it

```bash
php -S localhost:8000
```

Then open `http://localhost:8000/` — that's the landing page. Sign in from
there with one of the demo accounts above.

## What's implemented

- **Auth** — CSRF-protected login, bcrypt password hashing, lockout after
  5 failed attempts, 30-minute idle session timeout, full audit trail,
  TOTP two-factor authentication, self-service password change/MFA
  enrollment (click your name in the top bar)
- **Role-based access control, actually wired up** — every protected page
  checks a permission code (`administration/roles.php`) rather than a
  hardcoded role name, so what each role can do is reconfigurable from
  the UI, not just from code. `super_admin` always has every permission
  and can't be edited, so it's structurally impossible to lock yourself
  out entirely.
- **Personnel** — registration, editing, live search, profile with
  entitlement/balance/history, bulk upload (`.csv` or `.xlsx`)
- **Leave applications** — type selection, auto-populated form, live
  resumption-date calculation (Saturdays, Sundays, and configured public
  holidays excluded by default — see below), submit or save as draft,
  **file attachments** (e.g. a medical certificate — PDF/JPG/PNG/DOC/DOCX,
  5MB limit, content-type verified not just by extension, served only to
  the submitter or an approver, never by direct link)
- **Three-stage approval chain** — I-Admin submits → Administration
  Officer (SOA) reviews and forwards to a specific PSO → PSO I or PSO II
  gives the final decision (see "Approval workflow" below)
- **Signals** — printable Leave Approval Signal and a real downloadable
  PDF, generated automatically on final approval
- **Cancellation & curtailment**, **entitlement management** (bulk
  year-rollover or per-officer), **Leave Scheduled** calendar view,
  **reports** (5 categories, each exportable as CSV, Excel, or PDF)
- **Notifications** — in-app bell icon, plus optional **email
  delivery** (off by default; Administration → Email Settings)
- **Scheduled tasks** — keeps personnel status and overdue/reminder
  notifications date-exact (see below)
- **Audit log** viewer, full administration screens for every lookup
  table

## Approval workflow

Three stages, each a distinct application status:

1. **I-Admin** (`i_admin` role) receives a leave request from a
   registered officer, fills and submits the form (`personnel/search.php`
   → `leave/apply.php`). Optionally records **Checked By** and **Leave
   Checked Up To** at this point. Status becomes `pending_soa`.
2. **Administration Officer / SOA** (`administration_officer` role)
   reviews it (`approvals/soa_review.php`), records a recommendation —
   **Recommend Approval** or **Recommend Disapproval**, with **Remarks
   mandatory when recommending disapproval** — and forwards it to a
   specific PSO I or PSO II. Status becomes `pending_pso`.
3. **PSO I or PSO II** (`pso_i` / `pso_ii` roles) — whichever one the
   SOA picked — sees it in their decision queue (`approvals/pending.php`,
   filtered to applications assigned to them) and gives the final
   **Approve** or **Disapprove**. Approval auto-generates the Leave
   Approval Signal, same as before.

`super_admin` and `administrator` can act at any stage, for oversight.
Every step is logged to `leave_approvals` (with a distinct
`recommended_approve`/`recommended_disapprove` action for the SOA's
step, separate from the PSO's `approved`/`disapproved`) and to the
audit log.

## Public holidays & leave-day calculation

By default (`system_settings.leave_calculation_mode = 'working_days'`),
the resumption-date calculator excludes **Saturdays, Sundays, and every
date in Administration → Public Holidays** from the day count — not
just Sundays like an earlier version of this calculator did. Manage the
holiday list at `administration/holidays.php`; every leave application
calculated after a holiday is added picks it up automatically. Switch
back to pure calendar-day counting by setting that system setting to
`'calendar'` instead, if that ever fits your rules better.

## Two-factor authentication

Standard TOTP — compatible with Google Authenticator, Authy, 1Password,
etc. There's no QR code (generating one needs either a library or a
network call to a QR API, and this project deliberately has neither);
you're given a manual entry key instead, which every authenticator app
accepts as an alternative to scanning.

## Roles & permissions

**Administration → Roles & Permissions.** Eleven permission codes govern
every protected page (`personnel.manage`, `leave.review_soa`,
`leave.approve`, `audit.view`, etc. — the full list and each role's
defaults are in `database/migration_permissions_seed.sql`). Untick a box
and that role loses access to the matching pages immediately, no deploy
required. The `super_admin` role is shown fixed and can't be edited
through the UI —
this is also enforced again in the permission-check code itself, so a
crafted request can't remove it either.

## Email notifications

Off by default. Turn them on and configure delivery at
**Administration → Email Settings** (super admin only):

- **`mail` driver** — uses PHP's built-in `mail()`, which works out of
  the box if the server has sendmail/postfix configured (true of most
  production Linux hosts, usually *not* true of a bare local dev
  machine).
- **`smtp` driver** — a minimal hand-rolled SMTP client (AUTH LOGIN,
  optional STARTTLS) for relaying through an external server — a
  corporate mail server, or a provider's SMTP endpoint. Fill in host,
  port, username, password, encryption on the same settings page.

Use the **Send Test Email** button on that page to confirm delivery
before relying on it. Every notification the app already generates
(submission, approval, disapproval, cancellation, curtailment, overdue,
resumption reminders) goes out by email too once this is on — there's
nothing else to wire up.

## Scheduled tasks (cron)

Fills a specific gap: previously, personnel only flipped to "on leave"
at the exact moment an HOD clicked approve — accurate for leave starting
today, wrong for leave approved ahead of time for a future date. Run
this once a day and it becomes date-exact, and it also sends the overdue
and "resuming tomorrow" notifications:

```bash
# crontab -e
5 0 * * * php /path/to/npf-leave-management/bin/scheduled_tasks.php
```

No shell access (shared hosting with only a "cron via URL" panel)? Set a
secret first:

```sql
UPDATE system_settings SET setting_value = 'some-long-random-string'
WHERE setting_key = 'cron_secret';
```

then point your host's URL-cron feature (or an external uptime/cron
service) at:

```
https://your-domain/bin/scheduled_tasks.php?token=some-long-random-string
```

The script is idempotent — running it twice in the same day never
double-applies a status change or sends a duplicate notification.

## Testing

There's no PHPUnit here — it needs Composer, and this project
deliberately has zero dependencies anywhere. `tests/` is a small
hand-rolled equivalent instead:

```bash
php tests/run.php
```

This covers **pure logic only** — the TOTP/base32 implementation
(checked against official RFC 4226 test vectors), the resumption-date
calculator (calendar and working-day modes — including Saturday+Sunday
exclusion, a public-holiday exclusion, a leap-year case, and a
month-boundary case), and the XLSX column-math/round-trip. It needs
nothing but a PHP CLI — no database, no web server.

What it deliberately does **not** cover: anything that touches the
database (personnel CRUD, the I-Admin → SOA → PSO application lifecycle,
RBAC enforcement, notifications, attachments) or a running web server.
Automating that properly would mean either standing up a disposable
MySQL instance in CI or maintaining a second SQLite-compatible copy of
the schema — real options, just out of scope here. Until one of those
exists, verify that layer manually:

1. Import `npf_leave_management.sql` + `demo_seed.sql` into a scratch
   database.
2. Sign in as each of the seven demo roles in turn; confirm the sidebar
   and every page match what `administration/roles.php` says that role
   can do.
3. Walk one application through its full life: register personnel (or
   use a demo one) → search → apply for leave with an attachment →
   submit as `a.musa` (I-Admin) → sign in as `o.kalu` (SOA) → recommend
   approval and forward to a PSO → sign in as that PSO → approve →
   confirm the signal appears, prints, and downloads as PDF → record
   resumption → confirm the officer's status returns to active and the
   application shows completed.
4. Try a disapproval (confirm the SOA can't recommend disapproval
   without remarks), a cancellation, and a curtailment the same way.
5. Add a public holiday, then apply for leave spanning it; confirm the
   calculated resumption date skips it (and skips weekends) rather than
   counting it as a leave day.
6. Run `php bin/scheduled_tasks.php` from the CLI against an application
   approved for a future date; confirm the officer doesn't show as
   on_leave until you back-date `proceeding_date` to today and run it
   again.
7. Turn on email notifications with the `mail` driver on a machine with
   sendmail configured (or `smtp` pointed at a real relay), send a test
   email, then trigger a real notification (e.g. submit an application)
   and confirm it arrives.

## Known gaps (still open, honestly)

- No automated integration/UI test suite — see "Testing" above for why
  and what to do instead in the meantime.
- The scheduled-task script needs a real cron entry (or URL-cron) to
  actually run on a schedule — nothing runs it automatically on its own.
