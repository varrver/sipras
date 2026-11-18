# Tasks

## 1. Return links

- [x] 1.1 Admin + teknisi + siswa complaint detail pages: "Kembali ke …" link at the top of the content area pointing at their list; teknisi link reads "Kembali ke antrean"
- [x] 1.2 The complaints list detail's misaligned on-page anchor link on the report header (dompdf-safe: plain anchor, no classes with absolute positioning); the list's own header back-positioning stays unchanged

## 2. Empty states and hand-off

- [x] 2.1 Siswa list empty-state branch: first-run (no filters) welcome copy + "Buat pengaduan pertama" CTA; filtered-empty keeps "Tampilkan Semua"
- [x] 2.2 Create redirects append `?created=`; the admin + siswa lists highlight a matching ticket row (ring tint, respects prefers-reduced-motion) and flash names the ticket

## 3. The polish sweep

- [x] 3.1 Screenshot every routed page desktop + 390px; record findings; apply spacing/touch-target/heading-order fixes found within tokens; re-swept to prove
- [x] 3.2 Suite green after the sweep (`composer test` incl. `DesignTokensTest`, `PageTitleTest`, `NavigationTest`, `ComplaintTriageTest`) + `vendor/bin/pint`

## 4. Follow-up hand discovered late

- [x] 4.1 Login card stacks "Kembali ke pengantar" above the Masuk heading — the landing made `/` real, and the guest shell's wordmark loop was the only legacy affordance (guarded in `UiuxFlowTest`)
