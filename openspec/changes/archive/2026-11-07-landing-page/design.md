# Design: landing-page

## Context

`/` currently returns `redirect()->route('login')`. The guest shell
(`x-guest-layout`) names pages through `:title` and shells the one-h1 rule;
`PageTitleTest` pins every routed page; design tokens live in `app.css`'s
`@theme` only; the auth surface has no landing. The design skeleton discussed
in exploration: bar, hero (what/for whom), how-it-works strip with real
status badges, SMK Negeri 5 Surakarta footer.

## Goals / Non-Goals

**Goals**

- A calm, brand-true one-screen intro — no marketing theater, no fake
  screenshots, no new assets to ship.
- Two-line branching logic (guest vs signed-in) implemented where the route
  already is.

**Non-Goals**

- No registration CTA anywhere (closed; the teacher overrode it).
- No new routes beyond replacing the root's behavior.
- No copy experiments against DESIGN.md's tone.

## Decisions

### D1 — Route + controller shape

Keep the root as a closure that branches: `Auth::check()` →
`redirect()->route('dashboard')` (the shared dashboard controller already
routes by role); otherwise return the landing view. No `LandingController`
class for one copy-plus-branch action; the filename-only `landing.blade.php`
sits under the guest view area, so the shell contract (`:title`) stays
mechanical.

### D2 — Content contract (copy)

Indonesian in a plain voice: one sentence naming what SIPRAS is (school
facilities complaints), one naming the audience (warga SMKN 5 Surakarta), and
three steps matching the real flow (Ajukan → Diproses → Selesai) with the real
badges; the footer names the school. The Masuk button is the only CTA (capsule
button, primary token).

### D3 — Test surface

`LandingTest` (guest sees intro + has no register link; signed-in bounces per
role) + one `PageTitleTest` entry. Landing is static: no Alpine, no JS, so no
beyond-default a11y work beyond the standard rules (one h1, named table rules
not applicable, tokens only).

## Risks / Trade-offs

- The `<x-status-badge>` strip shows four statuses whose tints the app uses
  for state — on a marketing-ish page they read as informational chips; the
  badges are the same components, so `DesignTokensTest` stays intact.
- Instaboot pre-warm currently targets `/` on the Edge; serving real HTML
  there makes the pre-warm warmer (still valid).

## Open Questions

- None — placement (standalone at `/`) was decided by the user.
