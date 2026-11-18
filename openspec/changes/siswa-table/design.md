# Design: siswa-table

## Decisions

- **D1 — Reuse, don't invent.** The staff lists' table block is the pattern; the siswa blade adopts its exact classes (scroll region, `sr-only` caption, badge cells, responsive card fallback) with the student-scoped column set.
- **D2 — Student-scoped columns.** Status, damage type, room, title, ticket, date. No technician/assignment columns — those are staff data views.
- **D3 — Per-cell Detail anchor (arbitrated 2026-11-07).** The drafted "whole-row
  affordance" mismatched the staff code it claimed to mirror: both staff tables
  actually carry a per-cell `Detail` anchor in a right-aligned Aksi column. The
  siswa table adopts that same anchor verbatim (user's call during apply), and
  the `?created` highlight rides the `<tr>`/card wrapper instead of an anchor.
- **D4 — Walkthroughs stay put.** The first-run/filtered-empty branches keep their position above the list body; only the row markup changes beneath them.
- **D5 — The created-highlight survives the conversion:** it attaches to whichever
  body the row renders in (table row or card) and the `?created` semantics don't
  move. Applied detail (2026-11-07, live-verified): the card keeps
  `ring-2 ring-primary`; the table row carries the highlight as a
  `bg-primary-container` wash plus a 2px primary top/bottom border — ring on
  `<tr>` rendered only as edge slivers in this Chromium, and all
  `surface-container-*` steps are white in the flat theme, so only the primary
  tint reads on a row.

## Risks

- The stacked card fallback must not regress mobile readability — the staff pattern is the same solution, so it inherits their solved behavior.
- Very long complaint titles in table cells: the staff lists already truncate with the same approach, so this inherits truncation rather than inventing a width rule.

## Verification

- sm+ renders a named table with the six column headers; 390px renders stacked cards; ring + chips + empty states all test green after the restyle.
