# Tasks

Reference specs — category-filter/specs/category-filter/spec.md; design decisions D1–D5 in design.md.

## 1. Grammar and counts

- [x] 1.1 Extend `scopeSearch` with the category condition reading `?category` per D1 (integer, existing-id tolerated — unknown id filters nothing); verify a hand-edited `?category=999999` renders with the other filters intact, no error
- [x] 1.2 Extend `statusCounts($params)` to honor category the same tier as search; verify dashboard's bare call is untouched (`DashboardTest` green unchanged)

## 2. Control surface and propagation

- [x] 2.1 Add the category `x-select-menu` control to the filter row (D2, "Semua kategori" empty option, current selected, one label); verify selection submits with the current status/period/search params intact
- [x] 2.2 Propagate `?category` through every filter carrier per D3 (status chips, period links, hidden search field); verify clicking a chip with a category active keeps the category, `aria-current` stays single

## 3. Report

- [x] 3.1 Thread `?category` into `reportData()` and add the "Kategori: <name>" summary line (D4); verify the filtered PDF matches the filtered list row-for-row and `ReportTest` palette points stay green

## 4. Mobile re-flow

- [x] 4.1 Reorder the toolbar on narrow screens per D5 (picker + period first, search, print anchored last, full-width tap target); verify at 390px all controls are present, labeled, and there is no horizontal overflow, desktop unchanged

## 5. Tests and verification

- [x] 5.1 Extend `ComplaintTriageTest` (chip-count contract with category), `ComplaintPeriodTest` (garbage tolerance for category), `ReportTest` (summary line, filtered equality), plus the composition scenario in the new spec; full `composer test` green
- [x] 5.2 Run `vendor/bin/pint`, and refresh the AGENTS.md `?from`/`?to` paragraph to name the category dimension as part of the filter grammar

## Workflow follow-up

- Archive the change after the project's review requirements are satisfied.
