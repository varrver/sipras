# Tasks

Reference specs — landing-page/specs/landing-page/spec.md; design decisions D1–D3 in design.md.

## 1. Route and view

- [x] 1.1 Replace the root redirect with the guest/auth branch (authed → their dashboard; guest → the landing view, D1); verify both roles' bounce and the guest render live
- [x] 1.2 Author `landing.blade.php` under the guest shell — bar (logo + Masuk capsule), hero intro copy per D2, how-it-works strip of three steps with `<x-status-badge>` renders, school footer; tokens only, one h1, page named via `:title`; verify no register CTA exists anywhere on the page

## 2. Tests and verification

- [x] 2.1 New `LandingTest` (guest sees the intro; no register CTA in markup; admin/siswa bounce to their own dashboards) + add the landing's title assertion to `PageTitleTest`; full `composer test` green
- [x] 2.2 Run `vendor/bin/pint`; verify the browser at 390px and desktop manually (spacing, no overflow); note the Edge instaboot pre-warm of `/` (design risk) as fine in the AGENTS.md hosting section only if the deploy is re-run during this change

## Workflow follow-up

- Archive the change after the project's review requirements are satisfied.
