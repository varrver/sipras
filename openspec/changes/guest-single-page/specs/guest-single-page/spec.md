# guest-single-page Specification (delta)

## ADDED Requirements

### Requirement: Single public entry

GET `/` SHALL render the combined public page (introduction beside the login card) for guests; GET `/login` SHALL redirect to `/`; POST `/login` behavior SHALL stay unchanged.

#### Scenario: Guest sees intro and form

- **WHEN** a guest opens `/`
- **THEN** the page carries the introduction headline and steps **and** the login form (email, password, Masuk) on the same screen

#### Scenario: Login bookmark

- **WHEN** a visitor requests `GET /login`
- **THEN** they are redirected to `/`, which renders the combined page

### Requirement: Authed bounce kept

Signed-in visitors at `/` SHALL bounce to the role-routed shared dashboard, unchanged by the merge.

#### Scenario: Signed-in visitor follows a public link

- **WHEN** a signed-in user opens `/`
- **THEN** they are redirected to the shared dashboard route, which routes by their role

### Requirement: Closed entry rules

The combined page SHALL show only the email/password form with Masuk — no registration affordance and no forgot-password hint.

#### Scenario: Form contents on the merged page

- **WHEN** the combined page renders
- **THEN** no Daftar action and no "Lupa sandi" hint appear anywhere on it
