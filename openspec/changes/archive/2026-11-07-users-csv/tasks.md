# Tasks

Reference specs — users-csv/specs/users-csv/spec.md; design decisions D1–D5 in design.md.

## 1. Export path

- [x] 1.1 Add the export route + controller action (`fputcsv` streamed response, header row, roster id-ascending, `sipras-users-YYYYMMDD.csv` filename per D1); verify manually that guests/siswa are refused by the role boundary (302 to login / 403)

## 2. Import path

- [x] 2.1 Controller-side file validation (`file`, `mimes:csv,txt`, `max:2048`) with Indonesian failure messages; verify an empty upload and a wrong-extension file both report a friendly failure
- [x] 2.2 Row loop + all-or-nothing semantics per D2 (name/email/role per row, email uniqueness vs DB and in-file seen-set, whole file read before any write, single `DB::transaction` on success); verify a 3-row valid file creates 3 users and a file with one bad row creates zero, quoting row numbers and reasons
- [x] 2.3 Role vocabulary sourced from the same helper/constant the role select reads (D5); verify a `teknisi`-style future role would import without changing the CSV code (code inspection note, not a live role)
- [x] 2.4 Random password for imported accounts per D3; verify created accounts cannot log in with anything guessed and an admin CAN reset one to a working password

## 3. UI

- [x] 3.1 Toolbar buttons "Ekspor CSV" and "Impor CSV" on `admin/users/index` and the import `<x-modal name="import-users">` with labeled file input and the random-password note (D4); verify the Pint suite + AXE-auditable dialog contract (named dialog, titled heading, labeled input)

## 4. Tests and verification

- [x] 4.1 `tests/Feature/Admin/UsersCsvTest.php`: export content shape (header + seeded rows), import success, file-wide rejection with row report, in-file duplicate email rejection, malformed upload; all green with `composer test` (existing 205 unaffected)
- [x] 4.2 Update AGENTS.md feature map line for master users to mention CSV import/export (one clause), keep the invariant test list accurate, run `vendor/bin/pint`

## Workflow follow-up

- Archive the change after the project's review requirements are satisfied.
