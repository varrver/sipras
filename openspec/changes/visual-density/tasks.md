# Tasks: visual-density

## 1. Shared components

- [x] 1.1 `page-heading`: optional description slot; supply one-line Indonesian subtitles on list, form, and detail headers (including the master pages, all three pengaduan lists, and dashboards)
- [x] 1.2 Master forms: widen to `max-w-4xl`; two-column field grids where fields pair (users name+email, password pair, role+specialty)

## 2. Dashboard and cards

- [x] 2.1 Dashboard stat cards: icon chips on tinted token backgrounds; 4-up on desktop, 2×2 on tablet/mid, stack on mobile (teknisi dashboard keeps its tertiary tint)
- [x] 2.2 Detail/info cards: consistent card header rows (small title + muted description) and muted meta labels

## 3. Verify

- [x] 3.1 Full suite + Pint + `DesignTokensTest` (token-only garnish); `openspec validate`
- [x] 3.2 1440px + 390px screenshot sweep across the main pages (empty-screen checklist: no narrow floating canvases, no label-only walls)
