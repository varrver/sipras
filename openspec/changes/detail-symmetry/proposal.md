# Proposal: detail-symmetry

## Why
Round-2 review: "the complaint detail page layout is not symmetrical." The three detail pages (admin, teknisi, siswa) are forks of the same skeleton, and their main info column and status/actions column differ in card width, padding, and top-alignment — the screen reads lopsided. Fixing the shared skeleton fixes all three forks at once.

## What Changes
- Re-grid all three detail blades to a matched skeleton: equal card gutters/paddings, both columns' first cards starting on the same top line, a consistent column ratio, and one mobile stacking order (info → status/actions).
- Presentation only: no transition logic, no action semantics, no route or flow changes.

## Impact
- Affected specs: `detail-symmetry` (new capability)
- Affected code: the three detail blades (shared classes), DESIGN.md layout note
- Contracts untouched: statuses, transitions, proof/justification rules — all unchanged.
