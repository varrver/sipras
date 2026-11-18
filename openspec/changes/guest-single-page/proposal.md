# Proposal: guest-single-page

## Why
The teacher's round-2 review named the standalone landing and the standalone login page "too wide" — each sits alone in its own page with big empty margins, and a guest must two-hop (intro → Masuk → form) for what is one act. The entry act is small: introduction + sign-in belong on one public screen at `/`.

## What Changes
- Merge the introduction and the login form into one public page at `/`: intro (left column, tightened copy) beside the login card (right column); on mobile the intro condenses and stacks above the form.
- `GET /login` redirects to `/` (bookmarks keep working); `POST /login` is untouched.
- The signed-in bounce at `/` (shared dashboard, role-routed) is unchanged.
- The "Kembali ke pengantar" back link dies with the standalone login surface — it existed only because the pages were separate.
- No registration and no forgot-password affordance is added (round-1 rules stand: registration closed, accounts admin-fixed, recovery via admin).

## Impact
- Affected specs: `guest-single-page` (new capability)
- Affected code: the landing blade, the login blade, routes (`GET /login`), `LandingTest`, `UiuxFlowTest`, `PageTitleTest`, guest-view rows, DESIGN.md flow contract, AGENTS.md flow note
- Supersession bookkeeping: the still-unarchived `landing-page` change documents the two-page design this change reverses; both changes archive together in one session so the synced main specs stay coherent.
