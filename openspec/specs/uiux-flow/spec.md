# uiux-flow Specification

## Purpose
TBD - created by archiving change uiux-flow. Update Purpose after archive.

## Requirements

### Requirement: Every page offers its way back

The three complaint detail pages (admin, teknisi, siswa) render a visible
text-plus-arrow return link to their list surface ("Kembali ke antrean" on
the teknisi queue); the report window's return link carries the active
filters (`status`, `q`, `category`, `from`/`to`) back to the list; the
master form pages (create/edit of categories, rooms, users) render the
same visible return link to their index. No page relies on the browser's
back control as the only return, and no return affordance is icon-only.

#### Scenario: A teknisi opens a detail and returns

- **WHEN** the teknisi opens any complaint detail from the queue
- **THEN** the page shows a "Kembali ke antrean" link navigating to the
  teknisi queue, and following it renders the queue and not an error

#### Scenario: The print window returns

- **WHEN** an admin opens the print window from the complaint list
- **THEN** the window's "Kembali" link opens the list carrying that
  window's active filters, so the list shows what the report showed

#### Scenario: An admin edits a category

- **WHEN** the admin opens the category edit form
- **THEN** the page shows "Kembali" navigating to the category index
  (same pattern on rooms and users, create and edit alike)

### Requirement: Empty states hand off to the real next step

A list that renders with zero rows always explains the state and links the
one action that changes it: a first-run siswa (no complaints at all) links
"Buat pengaduan pertama" to the create route; any filtered-empty state
links the filter reset. No empty state renders with an inert or no-op CTA.

#### Scenario: A first-run student opens their complaints

- **WHEN** a siswa with zero complaints opens "Pengaduan Saya"
- **THEN** the empty state reads the welcome copy and links "Buat
  pengaduan pertama" to the complaint-create route

#### Scenario: A filtered list matches nothing

- **WHEN** any list with active filters renders zero rows
- **THEN** the empty state offers the filter reset (the existing
  "Tampilkan Semua" hand)

### Requirement: A created ticket is pointed at on the list

Creating a complaint lands on the list with the new ticket highlighted
（a distinct ring/tint on that row) and a success flash naming the ticket
number; `?created=` with an unknown ticket renders harmlessly without the
highlight.

#### Scenario: A student creates a complaint

- **WHEN** the create form submits successfully
- **THEN** the student lands on their complaint list with the new ticket's
  row visually highlighted and the flash naming the ticket number

### Requirement: The audit keeps surfaces DESIGN.md-true

A screenshot sweep of every routed page (desktop + 390px) records the
spacing/background/heading-order/motion deviations it finds and fixes them
within the existing token set; no new tokens, no new components.

#### Scenario: The sweep leaves no token at risk

- **WHEN** the sweep's fixes land
- **THEN** `composer test` stays green including `DesignTokensTest` and the
  page-title contract
