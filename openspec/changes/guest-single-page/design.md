# Design: guest-single-page

## Decisions

- **D1 — One page, two columns.** `/` renders the intro on the left (headline, 2-line what/for-whom, the three-step strip as a compact row) and the login card (email + password + Masuk + error flash, carried over verbatim) on the right, inside a `max-w-5xl` container so neither column swims.
- **D2 — Equal halves.** A `lg:grid-cols-2` split (not a narrow 2:1) fills the width: the teacher's "too wide space" complaint dies because both halves are occupied.
- **D3 — Mobile stack.** Intro condenses to the h1 + one supporting line above the form; the three-step strip stays beneath (it reads fine on a phone).
- **D4 — Routes.** `GET /login` → redirect to `/`; `POST /login` untouched; the merged page posts to the same login route. Guest-shell "kembali" semantics disappear with the surface.
- **D5 — Authed bounce unchanged.** Any signed-in visitor hitting `/` bounces to the shared `/dashboard` (role-routed), exactly as today.
- **D6 — Back link removed, test row deleted** (not repointed): the login card's return link only made sense across two pages.
- **D7 — Closed entry rules carry forward**: no Daftar, no "Lupa sandi" hint.
- **D8 — Supersession timing.** The `landing-page` change (implemented, unarchived) describes the two-page design; when both changes archive, `landing-page` syncs first and this change's delta invalidates only its placement assumptions — the main specs tree records the merged outcome as final.

## Risks

- Old links/bookmarks to `/login` — handled by the redirect (D4).
- Test drift: `LandingTest`/`PageTitleTest`/`RoleTest` rows assume two pages; the flipped set is enumerated in tasks so nothing sneaks through unreviewed.
- Copy must shrink — the one-liner + step strip must state what/for-whom exactly once, with no duplicated sentence between the columns.

## Verification

- Guest GET `/` contains both the intro headline and the form; guest GET `/login` redirects (asserted); admin GET `/` bounces (unchanged row); full suite green.
