# Proposal: landing-page

## Why

Teacher's revision point 1 ("Landing page diberi pengantar") and the decided
placement (standalone at `/`): today `/` blindly redirects guests to the login
card, so a visitor landing on the school's URL sees a form, not the app. The
app deserves a one-screen introduction — what SIPRAS is, who it is for (the
SMK Negeri 5 Surakarta community), and how to use it — ending the one action
registration being closed allows: **Masuk**.

## What Changes

- **A standalone guest landing at `/`** replaces the redirect: Indonesian
  intro of the app (what/for whom/how), brand-consistent with DESIGN.md, one
  `<h1>`, page named via the guest shell's `:title` contract.
- **The single visible action is Masuk**, linking to `/login` — no Daftar CTA
  (registration stays closed per the teacher).
- **Logged-in visitors at `/` bounce to their role's dashboard** — the landing
  is a guest surface only.
- The landing reuses the existing shared status vocabulary visually (real
  `x-status-badge` chips in the how-it-works strip) so the product is shown,
  not described with invented mockups.

## Capabilities

### New capabilities

- `landing-page`: the public guest entry at `/` — intro content contract,
  guest/auth branching, and title/rule conformance.

## Impact

- `routes/web.php` root route → `LandingController` (or a view-direct route
  with the branching closure); actual impl choice in design.
- New guest blade under the guest layout shell; no new navigation, no JS.
- Tests: `PageTitleTest` gains the landing; a small `LandingTest` covers
  guest sees intro + auth branch behavior; `NavigationTest` untouched.
- No token or count changes; no report/PDF changes.
