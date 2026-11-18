# Proposal: users-csv

## Why

The teacher's revision list (point 2) asks for user import and export at the admin
master-users screen. Today the screen is CRUD only: there is no way to take the
account roster out of SIPRAS (the only export in the app is the complaints PDF
report), and no way to enroll a batch of school users without typing them one by
one. A CSV round-trip is the smallest honest mechanism that a school IT person can
drive from Excel or LibreOffice, and it exercises the same Indonesian validation
vocabulary the screen already uses.

## What Changes

- **Export CSV** at `/admin/master/users`: one streamed CSV of all accounts with
  the columns `name`, `email`, `role` in roster order, offered as a download.
- **Import CSV** at the same screen: an uploaded CSV of the same columns creates
  the listed accounts in one transaction. Every row is validated (name present,
  email present and unique both against the database and against earlier rows in
  the same file, role one of the applied roles) and **failure is all-or-nothing**:
  when any row fails, the whole file is rejected with a row-by-row error report,
  never a silent partial import (the teacher's explicit rule).
- Both actions are admin-only surface additions; no schema changes (no new
  tables or columns; the import creates `users` rows only).

## Capabilities

### New capabilities

- `users-csv`: user roster CSV export at the admin master-users screen and
  all-or-nothing CSV import with row-by-row failure reporting.

## Impact

- `routes/web.php` (admin master group) — export + import routes only.
- `app/Http/Controllers/Admin/UsersController.php` or a dedicated
  `UsersCsvController` — implementation home; FormRequest-style per-row
  validation helper inside the controller (the import IS the validation).
- `resources/views/admin/users/index.blade.php` — two toolbar actions, the
  import behind a `<x-modal>` with a file input, consistent with the modal
  contract (named, `role="dialog"`, aria-modal, titled heading).
- No `User` model changes; `#[Fillable]` already covers name/email/role/password
  handling via the factory where needed (imported accounts get a random
  password, upgraded on first manual reset by admin).
- Tests: new `UsersCsvTest` (export shape, import success, row failure
  rejection, duplicate-email-in-file rejection), registered in the invariant
  suite the next time AGENTS.md's test list is touched.
