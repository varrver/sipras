# Proposal: visual-density

## Why
Round-2 review: "the UI/UX and layout need fixes — too empty and tasteless." The app's interiors are functionally correct but cling to narrow content canvases and label-only cards: large screens show long empty margins, master forms float in whitespace, and dashboards read as plain text walls. The fix is density and polish — presentation work, no behavior change.

## What Changes
- Widen form canvases: master forms claim a wider container (`max-w-4xl`) with two-column field grids where fields pair naturally.
- The shared page heading gains an optional one-line description; every addressed screen gets a subtitle under its h1 (what this screen does, in Indonesian).
- Dashboard stat cards become an icon-chip + tinted-token grid (4-up desktop, 2×2 mid, stack on mobile) consuming the existing tints (in-progress indigo, teknisi tertiary, primary family).
- Detail and list info cards gain consistent card headers (small title + muted description) and muted meta rows.
- Tokens only: every new visual comes from DESIGN.md's `@theme` palette; no new colors, fonts, or surfaces.

## Impact
- Affected specs: `visual-density` (new capability)
- Affected code: `page-heading` component (optional slot), master blades, dashboard blades, detail blades, DESIGN.md
- Behavior untouched: statuses, filters, flows, transitions — only presentation classes and copy-density.
