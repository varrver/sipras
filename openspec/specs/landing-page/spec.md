# landing-page Specification

## Purpose

Give visitors landing on the school's URL a branded one-screen introduction to
SIPRAS before they sign in, with the closed-registration reality respected
(intro and Masuk only).

## Requirements

### Requirement: Guests see an introduction at the root

Addressing `/` unauthenticated SHALL render a standalone landing page (not a
redirect): an Indonesian introduction of the app — what it is, who it is for,
how to file and track a complaint — titled and h1-ruled by the app's page
naming contracts (`x-guest-layout` `:title`, exactly one `<h1>`).

#### Scenario: A guest lands on the root

- **WHEN** an unauthenticated visitor opens `/`
- **THEN** they see the introduction page with the Masuk action (no
  registration CTA exists on the page)

### Requirement: Signed-in visitors bounce from the root

When a signed-in user addresses `/` they SHALL be redirected to their role's
dashboard — the landing is never served to an authenticated session.

#### Scenario: Admin and siswa hits are bounced

- **WHEN** an admin or a siswa opens `/` while signed in
- **THEN** each is redirected to their own role's dashboard address

### Requirement: The landing shows the product, not mockups

The how-it-works strip SHALL present SIPRAS's real flow using the app's own
status vocabulary rendering (`<x-status-badge>`) rather than invented imagery,
staying within the design tokens (`DESIGN.md`), with no new fonts, no external
assets, and no JS dependencies.

#### Scenario: Badges on the landing come from the shared vocabulary

- **WHEN** the landing's flow strip is inspected (markup, as the badge tests
  do elsewhere)
- **THEN** its status chips are `<x-status-badge>` renders of the real
  statuses with their established labels and tints
