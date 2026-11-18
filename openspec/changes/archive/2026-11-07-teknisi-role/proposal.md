# Proposal: teknisi-role

## Why

Teacher's revision point 3: add a **teknisi** role that processes complaints.
Roles today are exactly `admin` and `siswa`; the role middleware is generically
parameterized (`role:teknisi` would already resolve) — what is missing is the
role's place in the vocabulary, its own complaint-processing surface, and the
master-users plumbing to assign it. The user decided the role carries **full
process authority**: a teknisi may start work, finish it (with proof photo), and
also **reject** complaints with their own justification — i.e. the same status
transitions an admin has, without the rest of the admin surface.

## What Changes

- **Role vocabulary grows to `admin | siswa | teknisi`**; admins pick it in the
  role selects of master users (create/edit/edit-own-role rules unchanged, the
  two role pickers are `x-select-menu`).
- **A teknisi complaint screen** at `/teknisi/pengaduan` (list, filterable the
  way the admin list is) and its detail page with the status/process flow:
  start (pending → in_progress), finish (in_progress or pending → resolved,
  proof photo required), reject (any not-final status → rejected, with written
  justification in `admin_notes`).
- **No admin surface changes**: status work on the admin detail page stays;
  teknisi endpoints are their own routes and controllers with `role:teknisi`
  guards; technology detail page is teknisi-scoped only.
- A teknisi account sees nothing else: no dashboard counts beyond their own
  queue, no master data, no user management, no report.

## Capabilities

### New capabilities

- `teknisi-workflow`: the teknisi role's complaint-processing surface — role
  vocabulary, guarded routes, queue screen, and the status transitions with
  their proof-photo and justification contracts.

## Impact

- `app/Models/User.php` — role vocabulary constant; validation rules wherever
  roles are validated (master users create/edit, profile self-delete paths
  unaffected).
- New `app/Http/Controllers/Teknisi/...` controllers + `routes/web.php`
  teknisi group (role middleware `role:teknisi`).
- `Complaint` status transitions move to a shared policy/helper so admin and
  teknisi surfaces share one source of truth (status vocabulary unchanged).
- Master users role pickers (`x-select-menu`) gain the teknisi member; siswa
  flow untouched; `<x-status-badge>` unchanged (statuses unchanged).
- Tests: role-boundary tests (teknisi blocked from admin/siswa arms and vice
  versa), transition tests incl. proof-photo and rejection-justification
  contracts, master-users teknisi assignment.
