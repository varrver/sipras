# Design: visual-density

## Decisions

- **D1 — Bounded inventory.** This change is the density+polish reading of "too empty and tasteless": widths, subtitles, dashboard chips, card headers. A bolder redesign is out of scope; if review meant stronger, the change revises before applying.
- **D2 — Width.** Master forms move `max-w-2xl` → `max-w-4xl` with two-column field grids where fields pair (users: name+email, password pair, role+specialty; categories/rooms: name/deskripsi). The siswa complaint form stays single-column (its inputs are long: textarea + file upload).
- **D3 — Subtitles.** `page-heading` gains an optional description that renders invisibly when absent. One line, Indonesian, specific to the screen — density without noise.
- **D4 — Dashboard chips.** Stat cards get tinted icon chips from the existing token map (primary family for pending/total, in-progress indigo for Dalam Proses, teknisi tertiary only on the teknisi dashboard). The grid: 4-up `lg`, 2×2 `sm`, stacked phones.
- **D5 — Tokens only.** No new color/typography/surface token; spacing from `@theme` increments; the grouped background continues. `DesignTokensTest` stays green because every garnish consumes existing tokens.
- **D6 — Screenshot gate.** The change closes only after a 1440px + 390px sweep across every main page shows the empty-first-pass checklist cleared (no narrow floating canvases, no label-only walls).

## Risks

- Over-polish re-losing the calm iOS look: every edit is class-level, not re-layout; the grouped background and capsule language remain the base.
- Class-string tests: `ReportTest` pins the report palette (file untouched); markup-echo tests that assert exact class strings get a re-read in tasks before assuming.

## Verification

- Suite green, no visual-dependent tests broken; screenshots show denser headers, wider masters, chipped dashboards; token test unchanged.
