# Design: uiux-flow

## Context

Flows were audited against the live blades after the five revision changes.
The three app shells (admin/teknisi/siswa) exist; each list carries its
grammar (chips/search/period/category where relevant); the report is a
standalone dompdf doc. The audit found: no in-page return on details or the
report; an inert first-run empty state in the siswa list; creates that land
unmarked; assorted polish drift only visible side by side.

## Goals / Non-Goals

**Goals**

- Removal of every listed dead end, plus the polish fixes the sweep
  surfaces, all inside the existing DESIGN.md token system.
- Tests pinning the new hand-offs at the HTTP surface (no pixel tests).

**Non-Goals**

- No navigation redesign, no new routes, no design-token changes, no
  transitions beyond DESIGN.md's motion allowance (re-entrance on the
  highlighted row, respect prefers-reduced-motion).

## Decisions

- **D1 — Return links are server-known.** The list URL a detail
  "came from" is always its plain index route (filters live in the list's
  own tabs, the browser preserves query history). Every detail page and the
  report header renders one "Kembali ke …" link pointing at its own index;
  simple, static, and honest — no referer sniffing.
- **D2 — Created-ticket hand-off via the existing flash channel.** Create
  actions append `?created={ticket_number}` to their list redirect. The
  list greets it by ring-highlighting the matching row (no DB lookup: the
  row just compares its own ticket string) and the flash already names the
  ticket; the paper trail is one query param deep.
- **D3 — Empty-state logic keyed on filters, not JSX-free formulas.** The
  siswa list distinguishes "first run" (no filters active) from
  "filtered-empty" by the same predicate the reset button already uses.
- **D4 — The polish sweep is bounded by the audit screenshots,** desktop +
  390px, page by page; fixes are spacing/order/targets only, gate-kept by
  the existing invariant tests.

## Risks / Trade-offs

- Return links are "dumb" (to the index, not the filtered view the user
  filtered before drilling in) — the browser back trail still holds the
  exact filtered view, so the in-page link is the safety net, not the
  replica.
- `?created=` survives a manual refresh; harmless (highlight persists, no
  state change).

## Open Questions

- None.
