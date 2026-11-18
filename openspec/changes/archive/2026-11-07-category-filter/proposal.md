# Proposal: category-filter

## Why

Teacher's revision point 5: the filter and print controls re-flow badly on narrow
screens, and a **damage-type (category) filter does not exist** — neither in the
complaint list nor in the printed report. Complaints carry `category_id`
alongside status/period/search, so the category is a filter dimension the screen
already owns data-wise; it is simply not wired into the toolbar, the chip-count
grammar, or the report.

## What Changes

- **Category filter control** in the admin list filter row, wired as a new
  `?category=` URL dimension that composes with `?status`, `?from`/`?to`, `?q`
  under the existing period/filter grammar.
- **Chip-count tiering preserved:** status chip counts count inside the active
  category (one tier at a time, same contract as `?q` today), so a chip's number
  always equals what its click reveals; the all-time `statusCounts()` the
  dashboard calls stays untouched so dashboard cards keep their full-window
  meaning.
- **Every filter link carries the new dimension** — status chips, period links,
  the search form (new hidden input) — so no active filter silently drops when
  another is applied; the report/PDF "Cetak Sesuai Filter" keeps following the
  full filter set and gains a **Kategori** line in its printed filter summary.
- **Mobile re-flow of the filter/print area** at `/admin/pengaduan` so the
  print button, category picker, period control, and search stay usable on a
  390px screen (the teacher's screenshot complaint), without changing desktop.
- Siswa's own list keeps its existing filters; only the admin screen and the
  report gain the category dimension.

## Capabilities

### New capabilities

- `category-filter`: the category dimension of the admin complaints list — URL
  grammar, count tiering, control surface, and its propagation to the printed
  report and PDF.

## Impact

- `app/Models/Complaint.php` — `scopeSearch` gains the category condition;
  `statusCounts()` gains a category tier; `reportData()` accepts it.
- `app/Http/Controllers/Admin/ComplaintController.php` + `ReportController` —
  read the new query parameter.
- `resources/views/admin/pengaduan/index.blade.php` + partials — filter control
  (an `x-select-menu` picker, the app's established picker pattern) and the
  mobile order of the toolbar.
- `resources/views/admin/reports/complaints.blade.php` — Kategori filter line.
- Tests: `ComplaintTriageTest` (chip-count contract), `ComplaintPeriodTest`
  (grammar), `ReportTest` (report follows the filter; palette pinned) — plus a
  new filter-composition scenario set.
