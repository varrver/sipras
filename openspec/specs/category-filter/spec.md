# category-filter Specification

## Purpose

Add damage type (category) as an honest filter dimension on the admin complaint
list and its printed report, composing with the existing status/period/search
grammar without degrading any of the screen's count promises, and re-flow the
filter/print area so it works on a phone.

## Requirements

### Requirement: Category is a first-class list filter

The admin complaints list SHALL accept a `?category=<id>` URL dimension that
narrows the rows to that category's complaints, composing with `?status`,
`?from`/`?to`, and `?q` under the same permissive grammar rules the period
dimension uses (an unknown or stale id filters nothing, never errors).

#### Scenario: Composed filters narrow together

- **WHEN** the list is opened with a category and any other active filter
- **THEN** rows satisfy every active filter simultaneously and each filter's
  control shows the active value

#### Scenario: Hand-edited category cannot break the page

- **WHEN** a manually edited URL carries a nonexistent or non-numeric category
- **THEN** the list renders normally filtered by the other dimensions (or
  unfiltered), with no error response

### Requirement: Chip counts stay inside the active category

With a category active, the status chip counts SHALL count within that category
(one tier, same contract as search today) so a chip's number always equals what
clicking it reveals, while the dashboard's all-time `statusCounts()` remains
untouched.

#### Scenario: Chip number equals the revealed rows

- **WHEN** a category filter is active and each status chip is read
- **THEN** every chip's count equals the row count its click would show

### Requirement: Filters compose without dropping each other

Every link or form that applies one filter dimension SHALL carry the other
active dimensions — status chips carry `?category`, period links carry it, and
the search form submits with it — so applying a filter never silently drops an
active one.

#### Scenario: Applying search keeps the category

- **WHEN** a category is active and the admin submits the search field
- **THEN** the resulting list is filtered by the search AND the active category

### Requirement: The report stays honest to active filters

The printed report and its PDF SHALL follow the full active filter set and
SHALL name the category in the report's filter summary (an empty report shows
its empty state as today); the printed recap's per-category counts remain
category-of-the-rows, not the filter.

#### Scenario: Filtered PDF matches the filtered list

- **WHEN** the admin prints per filter with a category active
- **THEN** the PDF contains exactly the list's rows and the filter summary line
  names the chosen category (a pinned test guards the line's shape)

### Requirement: The filter area is usable on a phone

The filter/print area at `/admin/pengaduan` SHALL re-flow for narrow screens so
the print button, category picker, period control, and search remain visible,
labeled, and operable at 390px, without altering desktop layout or removing any
control.

#### Scenario: Narrow screen keeps every control

- **WHEN** the admin list loads at a 390px viewport
- **THEN** the print button, category picker, period control, and search each
  remain present, reachable, and labeled, with no horizontal page overflow
