# Proposal: siswa-table

## Why
Round-2 review: "why not table like admin and teknisi?" The siswa complaint list currently renders custom stacked rows, while the staff lists are proper tables above the sm+ breakpoint. Unifying gives the student surface the same professional presentation (columns, badges, dated rows) and keeps the app's list language consistent at a glance.

## What Changes
- Restyle the siswa list rows into the same table pattern the admin/teknisi lists use (table on sm+, stacked cards on small screens, same badge cells).
- Student-scoped columns: **Status · Tipe kerusakan · Ruang · Keluhan · Tiket · Tanggal** — no staff-assignment columns for students.
- Preserve exactly: newest-first ordering, filter chips (student-scoped counts), the first-run/filtered-empty walkthroughs, and the `?created=<id>` ring on a fresh complaint.

## Impact
- Affected specs: `siswa-table` (new capability)
- Affected code: the siswa list blade (rows/table block), `UiuxFlowTest`, `ComplaintTest` reads
- No behavior change: statuses, filters, detail links, actions all unchanged — presentation only.
