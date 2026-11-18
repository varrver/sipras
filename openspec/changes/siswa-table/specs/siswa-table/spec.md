# siswa-table Specification (delta)

## ADDED Requirements

### Requirement: Shared list pattern

The student complaint list SHALL render as a table on sm+ (Status · Tipe kerusakan · Ruang · Keluhan · Tiket · Tanggal) and as stacked cards on small screens, exactly like the staff lists.

#### Scenario: Student opens the list on desktop

- **WHEN** a siswa opens their complaint list at 1024px
- **THEN** the rows present as a named table with the student-scoped columns and status badges

#### Scenario: Student opens the list on a phone

- **WHEN** a siswa opens the list at 390px
- **THEN** the list renders as stacked cards with the same content and labels

### Requirement: Flows untouched

Filter chips, newest-first ordering, the first-run/filtered-empty walkthroughs, and the `?created=<id>` ring on a fresh complaint SHALL all persist after the restyle.

#### Scenario: Post-create ring survives the restyle

- **WHEN** a complaint is created and the list navigates with `?created=<id>`
- **THEN** that row (table row or card, whichever body renders) carries the primary ring highlight
