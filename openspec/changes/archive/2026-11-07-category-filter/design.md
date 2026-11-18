# Design: category-filter

## Context

The admin list (`admin/pengaduan/index.blade.php`) filters through
`Complaint::scopeSearch` shared by rows and chips; `statusCounts()` powers the
dashboard; `resolvePeriod()` owns `?from`/`?to` permissiveness; and the report
view doubles as the dompdf source. The app's picker pattern is now
`x-select-menu` (commit f3dfc4a).

## Goals / Non-Goals

**Goals**

- One new `?category` dimension that behaves exactly like the existing ones.
- The count contract ("chip number = what the click reveals") survives a new
  tier.
- The phone re-flow touches layout order only, not any control's identity.

**Non-Goals**

- No room-position filter (the teacher's wording is damage *type*).
- No siswa-side category filter (their list keeps its own filters).
- No change to `resolvePeriod` semantics or to the report's palette.

## Decisions

### D1 — Category composition inside `scopeSearch`

`scopeSearch` gains `when($categoryId, fn => where category_id)` reading
`$request->input('category')` validated as a existing-id integer (nonexistent →
ignored, same "filters nothing" rule as period garbage). Rows and chip counts
share the scope exactly as they do for `?q` today: `statusCounts($params)`
that already honors search now honors category the same tier.

### D2 — The control is `x-select-menu`

The category picker follows the app's established picker (`x-select-menu` with
a "Semua kategori" empty option), taking the current category as selected; the
noscript fallback is a native select of the same options, keeping the "one
`<label>` per control" rule. Applying it is immediate form submits — no new JS
state machine beyond the component's contract.

### D3 — Link and form propagation, one tier at a time

Status chips keep their existing inherit-and-carry pattern (converse to the period
and search tiers), now extended with `category`; period links carry `category`;
search form gets a hidden `category` field only when non-default. Chips keep
`aria-current` on the active chip alone.

### D4 — The report accepts the category param

`reportData()` includes the category in its query and the report view gains a
"Kategori: <name>" line in the filter summary when the param is set (omitted
when not, so the printed summary stays truthful and plain). The dompdf source
stays untouched: no new fonts, no palette changes, printed pills unchanged.

### D5 — Mobile order: print button last, picker first

The re-flow reorders the toolbar on small screens (flex order classes) —
category picker and period control stack first, then search, print button
anchored last with full-width tap target — while desktop keeps the current
row. No z-index/ARIA structure changes; `NavigationTest`'s drawer pair is
untouched.

## Risks / Trade-offs

- A stale category id (category deleted after bookmarking) reads as "no
  category filter" — consistent with the app's garbage-tolerance rule.
- `statusCounts` call sites now pass params that may include category; the
  dashboard deliberately keeps calling it with none — guarded by
  `DashboardTest` (no change expected).
- The printed summary grows one line; the two-table report wrapper structure
  is unchanged, so `ReportTest`'s snapshot points stay valid.

## Open Questions

- None.
