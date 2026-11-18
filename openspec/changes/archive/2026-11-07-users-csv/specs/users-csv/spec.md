# Spec Delta

## Purpose

Give the school's admin a CSV round-trip for the account roster: export the current
users to a file Excel/LibreOffice can open, and enroll new users from a file with
strict, row-by-row validation that never applies a partial import — so bulk
account administration stops being hand-typing without ever half-failing quietly.

## ADDED Requirements

### Requirement: Users roster exports as CSV

The admin master-users screen SHALL offer a CSV export that streams every account
as `name,email,role` in roster order and downloads as a file, with no pagination
or truncation.

#### Scenario: Export reflects the roster

- **WHEN** an admin runs the export on a database containing seeded and
  admin-created accounts
- **THEN** the downloaded CSV opens with the header `name,email,role` and every
  account appears exactly once with its own name, email, and displayed role

#### Scenario: Export is admin-only surface

- **WHEN** a guest or a siswa requests the export address
- **THEN** the request is refused by the role boundary (no roster data leaves
  the app to a non-admin)

### Requirement: Import creates accounts only when every row is valid

The admin master-users screen SHALL accept a CSV upload of the same columns and
SHALL validate every row — required name, required unique email (both against the
database and against earlier rows of the file), and a role among the applied
roles — before anything is written.

#### Scenario: Valid file imports every row

- **WHEN** an admin uploads a CSV with no validation errors
- **THEN** all listed rows become accounts (email unique per database state,
  role honored) and the response confirms the imported count

#### Scenario: Any invalid row rejects the whole file

- **WHEN** a file contains any row failing a rule (missing field, duplicate or
  existing email, unknown role)
- **THEN** no rows are imported, the database is unchanged, and the response
  reports the failure file-wide with the offending row numbers and reasons in
  the app's Indonesian validation vocabulary

#### Scenario: Import never falls back silently

- **WHEN** the file is technically malformed (wrong or empty body)
- **THEN** the screen reports an upload failure instead of treating it as zero
  rows or as a success

### Requirement: Import surface follows the modal contract

The import SHALL live behind a named `<x-modal>` dialog on the master-users
screen with a labeled file input, using the app's shared dialog semantics
(`role="dialog"`, `aria-modal`, titled heading) — not `confirm()` and not a
bare inline drop.

#### Scenario: Import dialog is reachable and labelled

- **WHEN** an admin opens the import dialog on the master-users screen
- **THEN** the dialog identifies itself by name with a titled heading and the
  file input has a visible lasting label; cancelling imports nothing
