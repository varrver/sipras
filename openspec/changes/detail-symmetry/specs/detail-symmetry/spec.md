# detail-symmetry Specification (delta)

## ADDED Requirements

### Requirement: Matched skeleton

All three role variants (admin, teknisi, siswa) SHALL use the same detail grid: equal horizontal gutters/paddings, both columns' first cards starting on the same top line, and a consistent column ratio across the variants.

#### Scenario: Admin opens a detail page

- **WHEN** an admin opens a complaint detail at 1024px
- **THEN** the main info card and the status/actions card share their top line and padding set

#### Scenario: Teknisi variant consistency

- **WHEN** a teknisi opens the detail for the same ticket
- **THEN** the layout reads identically to the admin variant except for the role-specific action surface inside the same cells

### Requirement: Consistent mobile stack

On small screens the detail sections SHALL stack in one order (info → status/actions) across all three role variants.

#### Scenario: Siswa detail on a phone

- **WHEN** a siswa opens a complaint detail at 390px
- **THEN** the info cards render first, followed by the status card, with no orphan card between them
