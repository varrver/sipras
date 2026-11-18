# Design: users-csv

## Context

The master-users screen (`app/Http/Controllers/Admin/UsersController.php` +
`resources/views/admin/users/*`) is plain CRUD with role assignment. Spider
scale is small (a school roster, tens to low hundreds of rows) but the file
mechanism must still fail honestly per row. Roles are an applied vocabulary
(`admin`, `siswa` today; `teknisi` arrives in another change) surfaced by the
role select on create/edit.

## Goals / Non-Goals

**Goals**

- One honest CSV export and one strictly-validated all-or-nothing import.
- Row-level error reporting in Indonesian, matching the app's validation tone.
- No schema changes; imported rows are ordinary `users`.

**Non-Goals**

- No dry-run/preview step (the row report on failure already shows what would
  have happened).
- No CSV column mapping UI — the header is fixed (`name,email,role`).
- No guest surface, no mass-deletion by import (later rows with same email in
  the file are errors, not upserts).

## Decisions

### D1 — Export streams through `fputcsv` in a plain response

Controller builds a `StreamedResponse` over `fputcsv` rows (header first), with
`Content-Type: text/csv` and an `attachment` filename like
`sipras-users-YYYYMMDD.csv`. No temp files, no memory blow at real scale, and
no dependency on any package. The roster order is the same newest-last id order
the table shows exported... (kept deterministic: id ascending).

### D2 — Import validation is procedural, not a FormRequest

The uploaded file must pass Symfony's file rules first (`file`, `mimes:csv,txt`,
`max:2048` — the screen's messages already speak Indonesian). Row validation is
a loop in the controller: parse with `fgetcsv`, trim, then per-row rules:
name required, email `required|email` + uniqueness against the DB **and** a
seen-set collected from earlier rows, role in the applied role vocabulary. Every
failed row appends `nomor baris + alasan` to the error collection; the loop
reads the whole file BEFORE deciding. If errors exist → `DB::transaction` never
runs; the controller flashes the error list back to the dialog. Else one
`DB::transaction` creates all users at once via `User` model inserts
(`password` = `Hash::make(Str::random(24))`).

### D3 — Imported-account password: random, admin-resets

No password column in the file. Imported accounts get an unguessable random
password so they cannot be logged into until an admin resets the password for
that user (existing feature at `/admin/master/users`). This avoids inventing a
temporary-password UX while keeping created rows real; the confirm sheet
states it explicitly in Indonesian ("Sandi diatur acak; admin meresetnya nanti").

### D4 — The import lives behind a named `<x-modal>`

Reuse `<x-modal name="import-users">` with a `{{ $name }}-title` heading, a
visible lasting label "Berkas CSV", and the standard destructive-agnostic
dialog pattern (submit inside the form). Nothing on the page depends on it;
without JS the form still posts (file inputs are native). No new component.

### D5 — Role vocabulary is read from where the select reads it

The validating role list is the same source the create/edit role select uses
(the app's role list constant/helper), so added roles (teknisi) start importing
correctly with no second list to forget.

## Risks / Trade-offs

- `fgetcsv` accepts semicolons/quotes oddly in some spreadsheets — mitigate by
  documenting "koma sebagai pemisah" next to the dialog, and parsing with the
  comma default only (a wrong separator shows as a row error, never silent data).
- A large file (capped at 2 MB) cannot realistically overflow the transaction —
  a school roster is far under it.
- The all-or-nothing rule makes a fix-and-retry loop the user's cost for one
  bad row — that IS the requested behavior ("no partial silently"); the error
  list quotes every offending row at once so one retry fixes many rows.

## Open Questions

- None — placement, vocabulary source, and password policy are decided above.
