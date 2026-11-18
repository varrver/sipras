# teknisi-specialization Specification

## Purpose

Assign each technician a complaint specialty and scope their whole surface by
it — the queue shows only that field's complaints, work outside it refuses,
and the school's real specialists stop seeing foreign noise.

## Requirements

### Requirement: The technician role carries a required specialty

A teknisi account MUST have a complaint category assigned (`users.category_id`,
non-null for role `teknisi`): the master-users create and edit screens expose a
category select whenever the teknisi role is picked, and saving a teknisi
without one is a validation error with an Indonesian message. Non-teknisi roles
MUST leave the field null.

#### Scenario: Creating a teknisi without a specialty is refused

- **WHEN** an admin saves a new user with role `teknisi` and no category
- **THEN** validation fails with an Indonesian message naming the missing specialty
- **AND** no user row is created

#### Scenario: Creating a teknisi with a specialty assigns it

- **WHEN** an admin creates a teknisi with role `teknisi` and a valid category
- **THEN** the account persists with that category
- **AND** the edit screen shows the specialty as the current option

#### Scenario: Non-teknisi accounts carry no specialty

- **WHEN** an admin saves a user with role `admin` or `siswa`
- **THEN** the account persists with a null category regardless of posted values

### Requirement: The teknisi queue lists only their specialty

The teknisi queue (rows, filter chips, and pagination) MUST list only
complaints whose category equals the signed-in technician's; chip counts MUST
count inside that scope. The queue's toolbar MUST NOT render a category picker —
the whole page is already one category — while the remaining filters (search,
status, period) keep the list grammar unchanged.

#### Scenario: The queue contains only matching categories

- **WHEN** a teknisi of category A with complaints in categories A and B opens the queue
- **THEN** only category A's complaints appear
- **AND** chip counts (e.g. Menunggu) reflect category A exclusively

#### Scenario: The queue has no category picker

- **WHEN** the teknisi queue renders
- **THEN** no "Tipe kerusakan" control exists in its toolbar

### Requirement: Work outside the specialty refuses

A teknisi MUST NOT open (GET show) or mutate (PATCH status) a complaint outside
their assigned category: the detail address refuses with the localized 403
error card, and the status mutation refuses with a validation error carrying an
Indonesian message. The admin surface remains the net over every category —
admin scoping is unchanged.

#### Scenario: The detail page of a foreign complaint refuses

- **WHEN** a teknisi of category A opens the detail of a category B complaint
- **THEN** the response is the localized (Indonesian) forbidden error card

#### Scenario: The status PATCH of a foreign complaint refuses

- **WHEN** a teknisi of category A POSTs a status change on a category B complaint
- **THEN** the response is a validation error with an Indonesian message
- **AND** the complaint's status and timestamps are untouched

#### Scenario: Admin still works every category

- **WHEN** an admin opens or mutates a complaint of any category
- **THEN** the request proceeds exactly as before this capability

### Requirement: Unstaffed categories queue for nobody — admin is the net

A complaint whose category has no assigned technician MUST appear in no
teknisi's queue; only the admin surface sees and works it. No fallback queue,
no auto-assignment, no shared pool exists.

#### Scenario: A complaint in an unstaffed category is invisible to teknisi

- **WHEN** a complaint exists in a category with no assigned teknisi
- **AND** every teknisi opens their queue
- **THEN** that complaint appears for none of them
- **AND** the same complaint remains visible and workable on the admin surface

### Requirement: In-use categories refuse deletion — including staffing

Deleting a category that is referenced by complaints OR by assigned teknisi
accounts MUST refuse with the existing session flash error (no 500). A
category freed of both remains deletable per the existing master-data rule.

#### Scenario: A staffed category refuses deletion

- **WHEN** an admin deletes a category that has an assigned teknisi (even with zero complaints)
- **THEN** the flow redirects back with the Indonesian flash error
- **AND** the category row remains

#### Scenario: An orphaned category still refuses on complaints alone

- **WHEN** a category has complaints but no teknisi
- **THEN** deletion refuses exactly as the master-data rule already requires

#### Scenario: A category with neither stays deletable

- **WHEN** a category has no complaints and no assigned teknisi
- **THEN** deletion succeeds as before this capability
