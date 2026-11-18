# Proposal

## Why

The teknisi arm (just shipped) gives every technician the same global queue — a
janitor's list contains electrical faults they cannot act on, and the cross
-field noise makes the queue less useful than the admin's own. The teacher's
revision list never asked for a division, but the school context demands it:
SMKN 5's technicians are field specialists (the RPL lab tech, the electrician,
the janitor). Dividing the queue by complaint category turns the teknisi arm
from "a smaller admin list" into a real work-assignment system — and the user
has confirmed the idea after manually exercising the new arm.

## What Changes

- **Technicians gain a required specialty:** `users.category_id` (nullable FK,
  validated only when `role = teknisi`). Master users shows the extra select
  when the teknisi role is picked (create + edit), and leaving it blank for a
  teknisi is a validation error.
- **The teknisi queue is scoped to their specialty:** the queue rows, chips,
  and pagination lists only complaints whose `category_id` matches the
  technician's. The `?category` picker is removed from the teknisi arm's
  toolbar — every scope there is already one category.
- **Cross-category work refuses:** teknisi cannot open or mutate complaints
  outside their specialty — the detail page and the status PATCH both refuse
  with the localized error card / a validation error.
- **The unstaffed-category gap is stated plainly:** a category with no
  technician's complaints queue for nobody — only the admin (the safety net
  over every category) sees and works them. No teknisi inheritance tricks.
- **Category deletion guard widens:** a category still assigned to a teknisi
  or its complaints refuses deletion with the existing flash, not a 500.
- **No two-level taxonomy:** sub-types within a category were considered and
  deliberately rejected (model/report/filter grammar multiplication across the
  whole app vs. marginal demo value). The four seeded category names stay as
  they are — precisely chosen, and renaming risks demo data for no requirement.

## Capabilities

### New Capabilities

- `teknisi-specialization`: assignment of a complaint category to each
  technician and the scoping/refusal rules that follow (queue scope,
  cross-category refusal, the unstaffed gap, category-deletion guard).

### Modified Capabilities

- (none — `teknisi-workflow`'s requirements remain true: the queue keeps its
  grammar and the detail keeps its transitions; specialization adds the
  category dimension on top, and the existing `TeknisiWorkflowTest` scenarios
  remain valid with a specialist assigned to the sampled category.)

## Impact

- Migration: one nullable FK column on `users`; seeded teknisi (none today —
  teknisi accounts are created by admins, so no seeder churn).
- Master users create/edit blades + their FormRequests (assigned-category
  rule + Indonesian messages); `Category` deletion path gains a second in-use
  source.
- Teknisi blades (toolbar picker removal), `TeknisiComplaintController`
  (scoping + guards).
- Tests: new `TeknisiSpecializationTest`; `TeknisiWorkflowTest` gains a
  per-test specialist (its factory complaints land in seeded categories).
