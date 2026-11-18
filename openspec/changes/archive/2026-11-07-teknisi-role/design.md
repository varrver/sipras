# Design: teknisi-role

## Context

Roles live as a short vocabulary used by `RoleMiddleware` (generic, already
parameterized), the master-users selects, and UI copies. Status transitions
are currently implemented wholly in `Admin\ComplaintController@update` with
validated proof/justification contracts; the status vocabulary and labels sit
on `Complaint` (`STATUSES`, `STATUS_LABELS`, `statusLabel()`) and tests guard
badge/label drift.

## Goals / Non-Goals

**Goals**

- One shared source of truth for status transitions used by both admin and
  teknisi surfaces (no drifting copies of the rules).
- Role addition with minimal blast radius: vocabulary entry + picker member +
  route group + controllers.
- Teknisi reads complaints the same way the admin list does, without touching
  the admin app's own surfaces.

**Non-Goals**

- No technician-specific statuses (no `assigned`, no `waiting_parts`),
  `Complaint::STATUSES` stays exactly four values.
- No teknisi dashboard with school-wide charts; their home IS the queue.
- No new auth guard or model-relation redesign.
- No changes to the siswa surface (a teknisi never was a siswa arm).

## Decisions

### D1 — Transition rules move to a shared helper on `Complaint`

The proof-photo-required / justification-required / final-state rules move to
explicitly named helpers on `Complaint` (e.g. `applyTransition($user,
$status, $data)`), called by BOTH controllers' `update` actions. The model's
transition table encodes: any non-final status → `in_progress`/`resolved`
(proof required) / `rejected` (justification required); final states accept no
further changes. Admin and teknisi reach the same helper with the same
contracts, so a future admin-side refactor cannot diverge from the teknisi's
checks and `ComplaintTriageTest`-class invariants stay single-sourced.

### D2 — Routes and guard shape

`Route::prefix('teknisi')->name('teknisi.')->middleware(['auth',
'role:teknisi'])` hosting `pengaduan` (index, show) + the update action. The
route names follow the siswa group's naming so blades and tests can mirror. The
admin group keeps its guards; both share the same `Complaint` model — scopes
such as the list's `scopeSearch` are reused as is (it is role-blind).

### D3 — Blades reuse the admin list's established components

The teknisi queue/detail pages reuse the admin screen's partials where they
are role-blind (status badge, filter chips, table structure, modal keys) and
fork only where authority differs (the detail page's status form). This keeps
`PageTitleTest`'s one-h1 / titled pages rule and the name-your-page
conventions intact by construction.

### D4 — Master users role picker gains a teknisi member

The two role pickers (create + edit) append teknisi to their option groups;
the label "Teknisi". The edit-own-role block stays (admin cannot demote
theirself), as does the password-reset screen's role display. Imported CSV
users (users-csv change) validate against the same vocabulary automatically.

### D5 — Shared proof-image handling with the admin flow

Proof images land in `complaints/resolved/` with the same validation and the
GD-free test pattern; rejection just writes `admin_notes` (no image). The
detail page's modal forms mirror the admin detail's destructive/confirm
patterns (named `<x-modal>`, never `confirm()`).

## Risks / Trade-offs

- Shared transition helper changes admin code paths — mitigated by keeping
  `ComplaintTriageTest`, `DashboardTest`, `ReportTest` green unchanged and by
  extending (not rewriting) the existing update-Action.
- A teknisi account is also a row any admin can password-reset/delete — no
  new deletion path needed (existing `User::deleting` covers the account).
- Route-group prefix naming (`teknisi.`) could collide with future Blade
  layouts — acceptable; siswa group set the precedent.

## Open Questions

- None — authority (full process, incl. reject) was decided by the user;
  vocabulary/read scope decided above.
