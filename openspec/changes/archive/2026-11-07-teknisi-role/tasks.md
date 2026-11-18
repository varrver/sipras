# Tasks

Reference specs — teknisi-role/specs/teknisi-workflow/spec.md; design decisions D1–D5 in design.md.

## 1. Vocabulary and shared transitions

- [x] 1.1 Add `teknisi` to the role vocabulary so the middleware/generic rules and validation rules see it; verify master-users create/edit accept and persist it while every existing role-boundary test stays green
- [x] 1.2 Extract the status-transition rules into a shared `Complaint` helper (`applyTransition`) encoding proof-required/justification-required/final-state per D1; verify `ComplaintTriageTest`, `DashboardTest`, `ReportTest` stay green with no behavioral change for admin

## 2. Teknisi surface

- [x] 2.1 Add the `teknisi.` route group + controllers (index/show/update) with `role:teknisi`; verify a teknisi account reaches their queue and is refused at every probed admin/siswa address (403 localized error card)
- [x] 2.2 Build the queue page (list scope reused; search/filters; one h1, titled page, named tables) and the detail page with the status form (start/resolve-with-proof/reject-with-notes modals, D5 image handling); verify the three transitions pass on the request level and their failure contracts (no photo / no notes / final state) refuse with Indonesian messages
- [x] 2.3 Verify the teknisi list markup has NO status-change control (mirroring `ComplaintTriageTest`'s admin-list check) and no admin-surface links; add the guard test

## 3. Master users integration

- [x] 3.1 Append the teknisi member to both role pickers (create + edit `x-select-menu`) with label "Teknisi"; verify assignment works, edit-own-role rules intact for admins, and `PageTitleTest`/`NavigationTest` stay green

## 4. Tests and verification

- [x] 4.1 New `TeknisiWorkflowTest` covering role boundary, queue shape, the three transitions + contracts, and the no-status-control-on-list guard; run the full `composer test` (existing suites untouched results)
- [x] 4.2 Update AGENTS.md (feature map line for the teknisi arm, role vocabulary mention, the status-change-surface rule now names both detail pages); run `vendor/bin/pint`

## Workflow follow-up

- Archive the change after the project's review requirements are satisfied.
