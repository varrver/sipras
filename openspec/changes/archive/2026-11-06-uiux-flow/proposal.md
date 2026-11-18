# Proposal: uiux-flow

## Why

The user's revision pass: "refine the ui/ux — all the pages have flow and do
not stop." The app's pages are individually correct but several journeys
dead-end: detail pages have no in-page way back (mobile users must use the
invisible browser back), first-run users meet an empty list whose only CTA
("Tampilkan Semua") does nothing new, a freshly created ticket lands unmarked
on the list, and the printed-report window ends with no way home. Adding the
visual polish sweep (spacing, touch targets, motion) keeps the surfaces
consistent with DESIGN.md after a week of fast feature landings.

## What Changes

- **Back affordances** on every detail/report surface: an in-page
  "Kembali" link to the list the user came from (three complaint detail
  pages + the print window; master edit pages pick one too).
- **Empty states point at the real next step**: first-run siswa gets
  "Buat pengaduan pertama" (the real create route); filtered-empty keeps
  the reset hand.
- **Post-create hand-off**: `?created=TICKET-…` on the list highlights the
  fresh row and pairs a dedicated flash.
- **Polish sweep** found by a screenshot audit of every page desktop +
  390px: spacing/token/touch-target/heading-order fixes, minor motion
  (re-entrance) where DESIGN.md §motion allows.

## Capabilities

### New capabilities

- `uiux-flow`: every page must offer its next step — no dead ends, first
  run included, and the audit fixes recorded.

## Impact

- Blades only + `ComplaintManagementController`/`SiswaComplaintController`
  redirects (add `?created=`), plus a fresh `UiuxFlowTest`.
- Report's standalone doc gains header links (dompdf-safe).
- No new routes, tokens, or dependencies.
