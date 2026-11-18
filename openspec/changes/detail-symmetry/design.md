# Design: detail-symmetry

## Decisions

- **D1 — One skeleton, three forks.** A single matched grid definition (container, two-column section, card paddings) is applied identically to the admin, teknisi, and siswa blades; role-specific action surfaces slot into the same cells.
- **D2 — Column ratio.** The main info column and the status/actions column keep a consistent proportional relationship (the action column narrower but fixed, not the current elastic mismatch), with gutters equal in both columns.
- **D3 — Equal tops.** Both columns' first cards share one top edge on the sm+ breakpoint — the visible lopsidedness disappears.
- **D4 — One mobile order.** On narrow screens the sections stack info-first, then status/actions, in all three variants; no orphan cards between them.
- **D5 — No logic moves.** Only classes/layout; any coincidental markup shifts re-verified against `UiuxFlowTest`.

## Risks

- The teknisi detail carries the status picker + refusal surfaces; it must keep its action set while adopting the skeleton — the cells are content-neutral, so this is a slotting exercise.
- Slight width wins may hide focus targets behind other elements if z-order carelessly changes — only padding/geometry classes move, no stacking contexts.

## Verification

- 1024px screenshots of all three variants show aligned tops and equal gutters; 390px shows the same single stacking order; suite green.
