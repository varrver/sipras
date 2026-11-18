# visual-density Specification (delta)

## ADDED Requirements

### Requirement: Claimed width

Master form pages SHALL claim a wide canvas (max-w-4xl) with paired field grids, so the page interior is not a narrow column floating in margins on large screens.

#### Scenario: Master users form on a large screen

- **WHEN** an admin opens a master users form at 1440px
- **THEN** the form container fills a wide portion of the page and related fields sit side-by-side

### Requirement: Named headers

Every addressed page SHALL carry a one-line description under its h1 via the shared heading component; lists, forms, and dashboards ship with subtitles as the default.

#### Scenario: List header

- **WHEN** an admin opens the complaints list
- **THEN** the h1 sits above a one-line Indonesian description of the screen

### Requirement: Token-only richness

New visual garnish (icon chips, tinted counts, card headers) SHALL use only existing DESIGN.md tokens; no new colors or fonts SHALL be introduced.

#### Scenario: Dashboard card render

- **WHEN** the dashboard renders its count cards
- **THEN** all chips and tints resolve to existing tokens (`DesignTokensTest` remains green)
