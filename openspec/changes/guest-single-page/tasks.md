# Tasks: guest-single-page

## 1. Merge the surfaces

- [x] 1.1 Rework the landing blade into the combined public page: two-column grid (intro left / login card right) in a `max-w-5xl` container; form fields and error flash carried over verbatim
- [x] 1.2 Condense mobile: intro h1 + one supporting line above the form; the three-step strip below both columns
- [x] 1.3 Remove the "Kembali ke pengantar" back row and the standalone login card surface
- [x] 1.4 Route `GET /login` as a redirect to `/`; leave `POST /login` and account-security routes untouched

## 2. Tests

- [x] 2.1 Flip `LandingTest`: the combined page serves the intro AND the login form to guests (form action, fields, no Daftar)
- [x] 2.2 Drop the `UiuxFlowTest` login-card back-row test; collapse `PageTitleTest` to one merged-page title row
- [x] 2.3 Update guest-view rows that assume a standalone `/login` GET to the redirect expectation; authed `/` bounce rows unchanged

## 3. Docs and housekeeping

- [x] 3.1 DESIGN.md: guest entry notes the merged page; AGENTS.md flow contract: login-card return rule replaced by the merged-page rule (with the `/login` redirect)
- [x] 3.2 Full suite + Pint; `openspec validate`
