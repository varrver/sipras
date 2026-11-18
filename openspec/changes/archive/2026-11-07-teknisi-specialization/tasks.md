# Tasks

## 1. Data model and assignment

- [x] 1.1 Migration: nullable `users.category_id` FK (restrictOnDelete per D1); verify `migrate:fresh` and the existing suites stay green
- [x] 1.2 Master users FormRequests (create + edit): required-and-nulled specialty rules per D2 with Indonesian messages; stray categories on non-teknisi roles are force-nulled
- [x] 1.3 Master users create + edit blades: Alpine-gated specialty select beside the role picker (x-show on the committed role value); verify the edit page renders the stored specialty as current

## 2. Scoped teknisi surface

- [x] 2.1 `TeknisiComplaintController`: resolve the technician's category once, scope rows + chip counts by it, drop the arm's category picker from the blade
- [x] 2.2 Refusal guards: show outside scope → localized 403 error card; PATCH outside scope → validation error on `status` (Indonesian message) with complaints untouched

## 3. Master-data guard

- [x] 3.1 Category deletion guard gains the teknisi-staffing count (flash message unchanged pattern); verify staffed refuses, orphaned still refuses on complaints, free categories delete

## 4. Tests and verification

- [x] 4.1 New `TeknisiSpecializationTest`: assignment rules (missing/assigned/non-teknisi-null), queue scoping in+out, chip honesty, the unstaffed-gap absence, both refusal surfaces, the widened deletion guard; keep `TeknisiWorkflowTest` green by pinning each test's specialist
- [x] 4.2 Full `composer test`; run `vendor/bin/pint`; update AGENTS.md (feature map line, master-data in-use rule now counting teknisi assignment, invariant test list)
