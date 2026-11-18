# Design — teknisi-specialization

## Context

The teknisi arm ships with a global queue: `TeknisiComplaintController::index`
runs the shared list scope unfiltered by category, and the whole status
contract (`applyTransition`) is category-blind. Users already carry a `role`;
categories are plain master data with a complaints-only in-use deletion guard.
`?category` filtering exists on the admin list (CategoryFilter change), which
makes the teknisi queue's toolbar category picker redundant the moment the
queue itself is scoped.

## Decisions

**D1 — Storage and constraint.** One nullable FK column, `users.category_id`,
`constrained()->nullOnDelete()` no — deliberate choice: `restrictOnDelete()`
and an explicit guard instead. A teknisi randomly losing their specialty to a
cascade would silently stop seeing work; the deletion path must refuse via the
master-data flash (widened to count assigned teknisi), never reassign by
accident. The migration also adds a partial unique-free index — none needed;
the count query stays trivial.

**D2 — Assignment UX and validation.** Master users create + edit: the
category select renders only when the picked role is `teknisi` (Alpine
`x-show` on the menu's committed value — same select-menu pattern as the page
already uses). The FormRequests carry the server truth: category_id is
`required_with:role:teknisi`-style via a closure (`required` when the posted
role is teknisi, else ignored and nulled), with the Indonesian message
"Spesialisasi kategori wajib dipilih untuk role Teknisi." For non-teknisi
roles the field is force-nulled in the request layer so stray POSTs cannot
park a category on a siswa. Edit-own-role rules stay untouched (the specialty
select sits beside, not inside, the existing self-edit block).

**D3 — Scoping.** `TeknisiComplaintController` resolves the signed-in
teknisi's `category_id` once; the queue chains
`Complaint::where('category_id', $technician->category_id)` into the existing
shared scope (search/status/period/`latest()`), and the chip counts call
`statusCounts` with the same category filter — numbers stay honest (the
chip-count tier rule now includes the technician's implicit category). The
teknisi blade drops its category picker entirely (it was inherited from the
admin fork) — with the queue scoped, a picker would be decoration. The
remaining filters pass no `category` query parameter at all on this arm.

**D4 — Refusals.** Two distinct refusal surfaces, two distinct shapes, both
Indonesian:
- GET show outside scope → the localized 403 error card (the route-level
  guard lives in the controller before any data loads — one `if`, cheap, and
  "you may not view this" is a page-level condition).
- PATCH status outside scope → a validation error on `status` (a closure rule
  in the teknisi FormRequest mirroring the final-state rule already there:
  "Pengaduan ini di luar spesialisasi Anda."). Validation, not 403, because
  the mutation re-renders the detail page the teknisi is honestly on — a form
  error keeps them in place.
Admin routes gain no guard (D3's net: admin works every category).

**D5 — The unstaffed gap.** Nothing synthetic: no auto-assignment, no shared
queue, no teknisi inheriting unstaffed complaints. Complaints in categories
without an assigned teknisi simply appear only on the admin arm. The queue
test seeds a complaint in an unstaffed category and asserts its absence. This
is deliberate (stated in the spec) — it also means the admin is motivated to
keep every category staffed.

**D6 — Category deletion guard.** `CategoryController::destroy` (and its
LogicException-free flash pattern) gains a second in-use source: a count on
`users` where role is teknisi and category_id matches. The message stays one
flash ("Kategori masih digunakan…"), text unchanged — the admin sees the
error, reassigns, proceeds. Master-data pattern already covers complaints.

**D7 — Category naming.** No rename, no new categories, no sub-types —
recorded as rejected: a second taxonomy level would multiply the form, list
grammar, report, and chip counting for marginal value this late in the
project, and the four seeded names are precise. (If the teacher later asks,
that becomes its own change with its own spec.)

## Risks / Trade-offs

- `TeknisiWorkflowTest` currently seeds complaints via the factory, which
  picks categories randomly — cross-category collisions occasionally hide a
  scoping bug. Mitigation: the new `TeknisiSpecializationTest` always pins a
  specialist to one category and asserts both in/out; the old suite gains a
  per-test specialist assignment in its setup so its assertions keep holding.
- Master users edit: an admin switching a teknisi to another category
  silently reshapes someone's queue. Accepted — it is the operator's job; the
  specialty select shows the change loudly enough at save time.
- The widened deletion guard touches a master-data controller outside the
  teknisi arm — guarded by extending the existing master-data test file
  rather than new faith in manual checks.
