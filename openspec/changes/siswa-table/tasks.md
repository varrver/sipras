# Tasks: siswa-table

## 1. Restyle

- [x] 1.1 Rebuild the siswa list rows into the shared table structure (Status · Tipe kerusakan · Ruang · Keluhan · Tiket · Tanggal), with the responsive stacked-card fallback mirroring the staff lists
- [x] 1.2 Keep chip rows, first-run/filtered-empty walkthroughs, and the `?created` ring atomically attached to the new markup

## 2. Tests

- [x] 2.1 Re-read `UiuxFlowTest` row-targets onto the new table/cards; add a table-pattern presence check (named table + column headers at sm+)
- [x] 2.2 Post-create ring and all complaint-flow tests stay green

## 3. Docs

- [x] 3.1 DESIGN.md tables note: the student list joins the shared pattern; `openspec validate`
