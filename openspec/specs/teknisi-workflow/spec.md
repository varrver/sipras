# teknisi-workflow Specification

## Purpose

Give the school's technicians their own guarded surface to process complaints —
start, finish with repair proof, or reject with justification — sharing the
admin's established status contracts while seeing none of the admin app.

## Requirements

### Requirement: The teknisi role exists in the user vocabulary

The role vocabulary SHALL be `admin | siswa | teknisi`; only admins may assign
it (master users create/edit, select grouped like the others), and the role
selects render it the same `x-select-menu` way.

#### Scenario: Admin assigns teknisi

- **WHEN** an admin edits or creates a user and picks teknisi
- **THEN** the account saves as teknisi and the account's own actions land in
  the teknisi surface from the next request

#### Scenario: Teknisi is outside admin/siswa arms

- **WHEN** a signed-in teknisi requests any admin-only or siswa-only address
  (dashboards, complaint creation, master data, report)
- **THEN** the request is refused by the role boundary (403 surface, error-card
  localized), and no admin data leaks (an id probed from the teknisi surface
  answers the same)

### Requirement: Teknisi sees a queue, not the admin app

The teknisi surface SHALL list complaints — newest first with the established
chips and status labels from `Complaint::STATUSES` — searchable/filterable by
the list's existing grammar, staying scoped read-only elsewhere: no
category/room/admin master surface, no report, and admin-only authorization
remains admin's.

#### Scenario: Queue lists the right rows

- **WHEN** a teknisi opens their complaint screen
- **THEN** complaints appear newest-first with the same status badges and
  labels the admin list uses; guest/other-role visitors are refused

### Requirement: The teknisi processes complaints with full authority

The teknisi detail page SHALL offer the status transitions an admin has for a
non-final complaint: start work (pending → in_progress), finish (→ resolved
with a proof photo, stamped `resolved_at`), and reject (→ rejected with a
written Indonesian justification stored as `admin_notes`), reusing the shared
validation contracts (proof photo needed for resolved, justification required
for rejected, resolved code unchanged).

#### Scenario: Start, finish, reject are available

- **WHEN** a teknisi opens a non-final complaint and applies any of the three
  transitions with valid inputs
- **THEN** the status changes per the same vocabulary/labels, `resolved_at`
  stamps on resolve, proof lands in `complaints/resolved/`, and justification
  text persists on rejection

#### Scenario: Contracts are enforced for teknisi too

- **WHEN** a teknisi attempts a resolved without photo, a rejected without
  justification, or a change on a final complaint
- **THEN** the request is refused with the Indonesian validation message and
  no state change occurs

### Requirement: The teknisi surface never duplicates the admin's status UI

The list screen SHALL NOT offer status-change controls (the admin-list
invariant), and the teknisi's own pages SHALL NOT expose admin-only
capabilities (master data, user management, report, dashboard-wide counts) or
share their links; the status work belongs on the teknisi detail page only.

#### Scenario: No status control in the teknisi list

- **WHEN** the teknisi list page is inspected (markup/control-wise, as
  `ComplaintTriageTest` does for the admin list)
- **THEN** there is no status-change select/button on any row, and bulk
  endpoints do not exist
