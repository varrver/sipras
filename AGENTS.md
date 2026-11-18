# AGENTS.md

SIPRAS (Sistem Informasi Pengaduan Sarana Sekolah) is a Laravel 13 school-facilities
complaint app. All PRD phases are implemented; most work is refinement and regression
protection. `PRD.md` is the authoritative spec and `DESIGN.md` is the UI system — read
them before changing behavior or UI. `PRODUCT.md` (the impeccable tool's product
snapshot — it has drifted), `revisi.md` (root; the teacher's revision list — applied
as batches), and the `docs/templates/` UKK trio (distilled for the user's NEXT school
project) are context, not specs. `scripts/*.py` rebuild the school-report
deliverables in `docs/` — documentation tooling, not app code. This file is
hand-maintained: `CLAUDE.md` is a
stock Laravel Boost placeholder, and `php artisan boost:install` would overwrite this
file — do not run it without restoring these notes.

## Stack and layout

- Laravel 13 / PHP ≥ 8.3 (the CLI here is 8.5), Blade + Tailwind CSS 4 (Vite) +
  Alpine.js only. Do not add Vue, React, or Livewire.
- Breeze Blade auth with roles `admin`, `teknisi`, and `siswa`. `role:...` maps to
  `App\Http\Middleware\RoleMiddleware`; register middleware/aliases in
  `bootstrap/app.php` (there is no `app/Http/Kernel.php`). The role vocabulary is
  `User::ROLES` — form rules, role pickers, and any role check read it.
- `routes/web.php` has the `/admin/*`, `/teknisi/*`, and `/siswa/*` groups (plus the
  guest landing at `/` and a shared `/dashboard` that dispatches by role);
  `routes/auth.php` has login/logout and account-security routes. Controllers live in
  `app/Http/Controllers/{Admin,Siswa,Teknisi,Auth}`; validation lives in FormRequests.
- Feature map: admin dashboard, complaint triage, and report under `/admin` — the
  report renders in-page and as a dompdf PDF at `/pengaduan/laporan/pdf`; master
  data at `/admin/master/{categories,rooms,users}` (users includes role assignment,
  password reset, and the CSV round-trip — export/import via `UsersCsvController`,
  import strictly all-or-nothing with a row-numbered error report); siswa flow at
  `/siswa/pengaduan` plus `/siswa/dashboard`; teknisi flow at `/teknisi/pengaduan`
  (the work queue + a full-authority detail page: advance, finish with proof, or
  reject with its own justification), scoped to the
  technician's assigned category (`users.category_id` — required for teknisi,
  nulled for every other role; the queue drops the category picker because the
  page IS one category).
- UI text, labels, and validation messages are Indonesian; identifiers, table, and
  column names are English. `APP_LOCALE=id` in `.env`; `.env.example` still uses
  SQLite and `APP_LOCALE=en` — do not copy its DB block.
- Public registration and self-service password reset are deliberately removed.
  Admins provision accounts and reset passwords at `/admin/master/users`; do not
  re-add the Breeze flows.
- Models use `#[Fillable([...])]` / `#[Hidden([...])]` attributes, not `$fillable` /
  `$hidden` (see `app/Models/User.php`).

## Commands

- Tests: `composer test` (`config:clear`, then `artisan test`; plain PHPUnit, no
  Pest). `phpunit.xml` forces in-memory SQLite, so no MariaDB is needed.
- One test/file: `php artisan test --filter=test_name` or
  `php artisan test tests/Feature/Admin/ReportTest.php`.
- Dev server: `composer serve` (clears compiled views, then `artisan serve`) or
  `composer dev` (server + queue + logs + Vite). Never serve through XAMPP Apache:
  its PHP is 8.2.12.
- Assets: `npm run build`; `npm run dev` for Vite. `.npmrc` disables install
  lifecycle scripts.
- Format: `vendor/bin/pint` (Laravel preset; no `pint.json`). There is no CI or
  pre-commit hook, so run tests and Pint yourself.
- Dev DB: XAMPP MariaDB 10.4.32 at `127.0.0.1:3306`, user `root`, no password,
  database `sipras`. Start it with XAMPP's `mysql start` before browsing dev routes.
  `php artisan migrate --seed` creates 1 admin, 3 siswa, 4 categories, and the full
  SMK Negeri 5 Surakarta room list (105 rooms distilled from the school floor
  plan into `database/seeders/data/school_rooms.php`) plus 12 demo complaints
  distributed across
  `Lab. RPL 1/2`, `Ruang 1.01`, and `Ruang Perpustakaan` — their demo photos copied
  from committed `database/seeders/fixtures` (re-seeding clears both image dirs
  first). No teknisi account is seeded — make one at `/admin/master/users`.
- `php artisan db:show` fails on MariaDB 10.4 (`performance_schema.session_status`
  is missing); use `migrate:status` or a raw query.

## Commit conventions

- Plain messages only: subject in the imperative, no AI attribution, no
  `Co-Authored-By:` trailer, and no "Generated with …" footer.

## Process (OpenSpec)

- Feature work runs through OpenSpec (`openspec/`, `spec-driven`): a change is a
  directory under `openspec/changes/<id>/` (proposal + design + tasks + delta specs),
  and landed deltas sync into `openspec/specs/`. Drive the steps with the openspec
  skills (propose / apply / update / sync / archive) instead of hand-editing its
  artifacts, and never edit a change under `changes/archive/`.
- Completed changes are not archived promptly here — `openspec/changes/archive/`
  plus `openspec/specs/` are the truth of what landed. Product decisions worth
  protecting are pinned inside these changes (e.g. `teknisi-role` pins the
  specialist's full process authority), so check them before changing behavior.

## Domain invariants

- Status vocabulary: `Complaint::STATUSES` plus `STATUS_LABELS` / `statusLabel()` are
  the only source. The badge, filter pickers, dashboard cards, status selects, the
  detail timeline, and printed recap read them; tints stay in `<x-status-badge>`
  and `DESIGN.md` §3.
  `ComplaintStatusTest` fails if the list and labels drift.
- Counts: `Complaint::statusCounts()` is the one source (it ignores `status`
  always). Dashboard cards call it bare, so their numbers are all-time and no
  list filter can make two screens disagree. **No list renders a count:** the
  status chip rows were replaced by capsule pickers whose menus carry the
  vocabulary only. The admin index still passes the filtered counts
  (`$period`, `$search`, `$category`) as view data and `DashboardTest` /
  `CategoryFilterTest` / `ComplaintPeriodTest` / `ComplaintTriageTest` pin that
  scoping, so if counts ever return to a list they must inherit the same tiers
  the rows use. Cards link into the matching filtered list.
- Status changes only on the admin detail page and the teknisi detail page — the
  shared contract is `Complaint::applyTransition()` / `Complaint::TRANSITIONS`
  (proof required when finishing, justification required when rejecting, final
  states closed; both update requests re-assert the final-state rule at
  validation so a crafted POST gets a validation error, never a model 500).
  `rejected` requires `admin_notes`; `resolved` stamps `resolved_at` and may
  store a proof image (stamped once — a same-status re-save never moves it).
  The bulk status endpoint was removed (feature pruned); do not re-add any
  status control on either list — `ComplaintTriageTest` and
  `TeknisiWorkflowTest` guard that no such control exists there.
- `?from`/`?to` is the period grammar: only `Y-m-d` pairs surface; presets
  materialize into URLs (no `period=` token); `Complaint::resolvePeriod()` swaps
  a reversed pair and silences garbage — a hand-edited URL filters nothing, never
  a 500. `ComplaintPeriodTest` guards the semantics. `?category` is its sibling:
  `Complaint::resolveCategory()` ignores missing/array/non-numeric/nonexistent
  ids, and views echo the scalar-safe `$activeCategory` (never raw
  `request('category')` — an array URL would 500 in `e()`). `CategoryFilterTest`
  guards both the grammar and the count-scoping tier.
- The list is always newest-first. The old `?sort=attention` toggle was
  removed: it was a sort masquerading as a triage control. The applied pickers
  use `aria-current`. See `DESIGN.md` §2.7.
- Tickets are `TKT-YYYYMMDD-XXXX`, generated in `Complaint::booted()`. Evidence goes
  to `complaints/evidence/`, proof to `complaints/resolved/`, both on the `public`
  disk (`storage:link` has been run).
- Deleting a user must go through `User::deleting`: it Eloquent-deletes their
  complaints, unlinks both images, and clears `sessions`; both delete paths wrap it
  in `DB::transaction`. A raw delete or DB cascade orphans files. In master data,
  admins cannot delete their own account or change their own role; profile
  self-delete (password-confirmed) is the only self-service path.
- In-use categories/rooms refuse deletion with a `session('error')` flash rather than
  letting the FK throw a 500. For categories "in use" is both halves: complaints
  reference it OR a teknisi is assigned to it — the flash names the refused reason
  (pengaduan ditugaskan / masih dipakai), and reassigning the specialist frees the
  field.
- The catch-all in `routes/web.php` is intentional: unknown addresses get the
  localized 404 inside the `web` group (so the card knows the visitor's role), while
  JSON clients keep Laravel's JSON 404. It accepts every verb and skips CSRF; do not
  remove or "simplify" it. The four error pages (403/404/419/500) render localized
  cards via `<x-error-card>`, not stock English Laravel pages.
- Every table is named. App tables use an `sr-only` `<caption>`; the report's two
  tables use `aria-label` because that Blade is also the dompdf source and dompdf
  ignores `position: absolute` (an `sr-only` caption would print as ink).
- Every page must name itself: `@section('title', …)` for `x-app-layout` or `:title`
  for `x-guest-layout`. The `<title>` is the page's `<h1>` plus ` — SIPRAS`, and each
  page has exactly one `<h1>`. Both shells throw if the title is missing or blank;
  `PageTitleTest` pins every routed page; `ErrorPageTest` covers the four error pages.
- Shared components throw on blank/missing required props: `<x-error-card>` (`code`,
  `title`, `message`), `<x-modal>` (`name`), `<x-status-badge>` (`status`).
  `<x-status-badge>` deliberately allows an unknown status (raw label + neutral tint);
  add a new status to both `STATUSES` and `STATUS_LABELS`.
- Detail pages use the vintage single-card layout: ALL audiences read one
  `max-w-3xl` record card (ticket header, info `dl`, timeline, description,
  evidence, tinted status banners and notes); admin/teknisi put the status/action
  card BELOW the record — never beside it, never sticky — so a decision follows
  the evidence. The later two-column redesign (`.detail-col`),
  the ticket banner strip, `x-status-callout`, and the timeline `spread` prop
  were all rolled back by user choice — don't reintroduce them (the blades at
  commit `654cebe` are the reference). Keep the staff action card unpinned —
  sticky there travels its containing block and covers the cards below
  (`ComplaintManagementTest` / `TeknisiWorkflowTest` / `DetailLayoutTest`).
- Dialogs: `<x-modal>`'s panel needs `relative z-10` above the fixed scrim,
  `role="dialog"`, `aria-modal`, and a heading with `id="{{ $name }}-title"`.
  Destructive actions use this modal, never `confirm()`.
- The admin mobile drawer declares `z-50` against the scrim's `z-40`; do not remove
  that pair. `NavigationTest` guards it.

## Blade and UI rules

- Never put a Blade directive (`@if`, `@unless`, `@php`) inside an `<x-…>` tag's
  attribute list: Blade emits the tag verbatim and the element silently disappears.
  Put the condition on a wrapper element instead.
- One `<label>` per control. When label text changes with Alpine state, keep one
  label and switch its text (`x-text` seeded server-side); duplicate labels make the
  control effectively unlabelled.
- Dropdowns are `<x-select-menu>` (in-page trigger + iOS-style option panel, plus a
  no-JS native `<select>` leg). They exist because native selects were the mobile
  pain point — do not reintroduce bare `<select>` controls or re-solve mobile
  dropdowns at the shell level.
- Design tokens live only in `resources/css/app.css`'s `@theme` and `DESIGN.md`:
  primary `#0066CC`, error maroon `#8B1D2C`, in-progress indigo `#5856D6`, etc.
  `tailwind.config.js` is an inert Breeze leftover — Tailwind 4 compiles from
  app.css alone (`@config` is absent there), so tokens added to the JS config do
  nothing. `DesignTokensTest` fails on an unused token (the lone exception is
  `--color-surface-zebra`, used only by the printed recap); `ReportTest` pins the
  report's hand-mirrored palette. Add a token and its consumer together.
- The report (`resources/views/admin/reports/complaints.blade.php`) is a standalone
  document: inline `<style>`, dompdf-safe fonts, no Vite/Tailwind. Keep its pills,
  counts, and palette in sync with the app; the two table wrappers are intentionally
  focusable scroll regions (`tabindex="0"`, `role="region"`, Indonesian `aria-label`).
- Focus styling: `.field` controls use `focus:ring-4` (box-shadow; `outline: none`);
  links and buttons use a `2px solid` outline.
- Flow contract: every addressed page hands the visitor somewhere — all of
  them carry the pill-class back arrow stacked above the heading
  (`x-back-button` — visible arrow + destination-named label, `hover:text-primary`;
  the row reads `flex flex-col gap-2`, never inline beside the `<h1>`; labels:
  detail pages name `Kembali ke daftar/antrean/pengaduan saya`, master forms
  name their list), the
  public entry at `/` merges the
  intro and login form on one page (guest GET `/login` redirects to `/`; there
  is no standalone login screen — the old `auth/login.blade.php` view and
  `AuthenticatedSessionController::create()` are deleted, do not re-add them),
  and master forms keep their ghost
  `Batal` beside the submit, post-create the student list carries
  `?created=<ticket>` (the ticket number, not the id) and rings the matching row
  (`ring-2 ring-primary`; an unknown ticket renders harmlessly), dashboard
  recent-rows are
  links with a muted chevron, and the report's `Kembali` carries the active
  filters. The first-run/filter-empty branches on student lists already held
  walkthrough + next-step CTA — `UiuxFlowTest` guards these (report path:
  `/admin/pengaduan/laporan`, not the master `?from` route).
- The UI follows `DESIGN.md` (Apple/iOS: `#F2F2F7` grouped background, capsule
  buttons, system font stack, no external fonts). Read it before changing spacing,
  color, motion, or copy.

## Test quirks

- Feature tests use `RefreshDatabase` on in-memory SQLite. GD is not installed in the
  PHP 8.5 CLI, so `UploadedFile::fake()->image()` fails; use committed real-JPEG bytes
  (`database/seeders/fixtures`) or the pattern in `ComplaintTest` /
  `ComplaintManagementTest`.
- `Tests\TestCase::assertFailsLoudly()` unwraps Blade's `ViewException` to the root
  `LogicException`; use it for shell/component guard tests.
- Invariant tests to run after touching the area: `ComplaintTriageTest`,
  `DashboardTest`, `ReportTest`, `UsersCsvTest`, `CategoryFilterTest`,
  `TeknisiWorkflowTest`, `TeknisiSpecializationTest`, `UiuxFlowTest`,
  `DesignTokensTest`, `PageTitleTest`, `ErrorPageTest`, `NavigationTest`,
  `LandingTest`, `ProfileTest`, `DetailLayoutTest`, `ComplaintStatusTest`,
  `RoleTest`.
- The README cites no test numbers (they drift), and its feature list lags the code:
  it still names the removed bulk status update and the `?sort=attention` pin, and
  its auth line omits `teknisi`. Source behavior from code and `routes/auth.php`;
  sync the README only if asked.

## Operational gotchas

- Stale Blade renders: a long-running `artisan serve` can serve an old compiled view
  (CLI OPcache), even after `storage/framework/views/*.php` changes. Use
  `composer serve` / `composer dev` (they clear compiled views); if an edit still does
  not appear, restart the process — clearing alone does not fix an already-running
  `php -S`. A fresh-process test is the quickest proof the code is fine.
- Orphaned servers hold the port: a backgrounded server can outlive the tool call, so
  a "fresh" start fails to bind but looks successful and the orphan serves stale
  output. Check `ss -ltnp | grep <port>`; if the listener PID is not the one you
  started, kill it before starting again.
- Prove the probe landed: check the HTTP status before trusting a response body. An
  unauthenticated request returns a `302` to `/login` whose HTML can look exactly
  like a stale render, and a swallowed `tinker --execute` error looks like a code
  failure. Do not send verification output to `/dev/null`.
- Do not `pkill -f "<pattern>"` from a script containing that pattern — it can kill
  its own shell. Capture `$!` after backgrounding and `kill "$PID"`; to sweep, filter
  on `comm`, e.g.
  `ps -eo pid,comm,args | awk '$2=="php" && (/artisan serve/ || /php -S/) {print $1}'`,
  then confirm the port is free.
- CSRF probes: Laravel 13 accepts same-origin browser posts with
  `Sec-Fetch-Site: same-origin` even without a valid token, so a same-origin "expired
  session" test logs in instead of rendering 419. Use `curl` or a cross-origin form.
- Vite: a stale `public/hot` after an unclean exit makes the app render unstyled;
  delete it or restart `npm run dev`.
- Axe false positives: `agent-browser a11y` reports `[serious] color-contrast`
  **incompletes** (not violations) for the `x-select-menu` dropdowns (`#status`,
  `#role`, `#category_id`, `#room_id` — figures measured while they were native
  selects; re-verify before trusting), text under a dialog scrim, and off-screen
  table cells
  at 390px. Contrast measures fine (17.01:1 / 5.07:1); do not change colors or layout
  to chase them. After navigating, verify `location.pathname` and the `<h1>` — a
  failed logout once made three audits measure the dashboard instead of the auth page.

## Hosting (Wasmer Edge) — https://sipras.wasmer.app

- Deploy is a two-step dance — `wasmer deploy`'s own uploader dies on this uplink
  (its delegated push carries a hard ~2 m total timeout; the ~53 MB pruned webc
  barely fits the good windows on a 440 KB/s link, and 88 MB never did), so push
  first and let deploy reference the release instead of re-uploading:
  1. `composer install --no-dev` (regenerates the autoloader WITHOUT phpunit —
     the push then ships a `require`-clean vendor; excluding `/vendor/phpunit`
     alone 500s the Edge because `autoload_files.php` hard-requires its file),
  2. commit, then `source ~/.wasmer/wasmer.sh && wasmer package push
     --non-interactive --timeout 30m --namespace varrver .` (the long timeout is
     required; plain `wasmer deploy` cannot survive this payload),
  3. restore local dev vendor for tests: `composer install`,
  4. copy the printed `sha256:…` into `app.yaml`'s `package:` line and run
     `wasmer deploy --non-interactive --no-wait` — with no same-named local
     `[package]` block in `wasmer.toml` (it is parked there commented out;
     deploy refuses "deprecated" when manifest name == app.yaml ref) this
     registers the new app version against the registry release with **no
     upload at all**. The `sha256:` line is the deployed truth between deploys
     — it names the last pushed tree, so code changed but never pushed stays
     invisible on the Edge even after a deploy run. Then verify
     `https://sipras.wasmer.app/` returns 200.
  Reruns are the answer to transient R2 failures (a failed push was a connect
  timeout once; a single retry cleared it).
- **Never rename or remount the `uploads` volume in `app.yaml`** — renaming/removing
  a volume deletes its contents on deploy (evidence + proof images live there).
- Database init is a one-shot run from THIS machine against the Edge MySQL, after a
  fresh `wasmer app database list varrver/sipras --with-password`:
  `DB_CONNECTION=mysql DB_HOST=<host> DB_PORT=<port> DB_DATABASE=db_2b0961fa
  DB_USERNAME=<user> DB_PASSWORD=<pw> MYSQL_ATTR_SSL_INSECURE=1
  php artisan migrate --force` then `db:seed --force`. The server is MySQL 8.4.8
  (not MariaDB); the XAMPP `mysql` client needs `--ssl`. Grant only covers
  `db_2b0961fa`, and the platform never injects `DB_DATABASE` — hence the explicit
  `DB_CONNECTION: mysql` + `DB_DATABASE: db_2b0961fa` in `app.yaml` env (without
  them Laravel silently falls back to sqlite and sessions 500 on the second visit).
- The Edge runs PHP 8.3.4 while local dev is 8.5: `composer.json` pins
  `config.platform.php=8.3.4` so one lockfile serves both; don't "modernize" it.
- `.wasmerignore` excludes `.env`, `node_modules`, `/docs`, `/vendor/phpunit`,
  `/storage/app`, `/storage/logs` (but NOT `storage/pail` — it is commented out there) —
  `storage/framework/**` STAYS in the package:
  Laravel's `config/view.php` `realpath()`s the views dir at boot and 500s when
  it's absent, and `/vendor/fakerphp` must STAY TOO (it is a runtime `require` —
  excluding it has the landing render 200-with-PHP-warnings; `/vendor/phpunit`
  is fine ONLY after the `--no-dev` autoloader regeneration above). The pruned
  webc is ~53 MB. `LOG_CHANNEL=stderr` in `app.yaml` (the package fs can't take
  log file writes; logs reach `wasmer app logs varrver/sipras`, retold as
  stderr), and `config/php.ini` sets `display_errors = Off` for the Edge only.
- Uploads land on the volume via `PUBLIC_DISK_ROOT=/app/public/storage` (D2);
  no `storage:link` is needed on the Edge, and the local default is untouched.
- Seeded logins match the seeder (`admin@sipras.test` / `password`,
  `budi@siswa.test` / `password`, …); DNS alias is `sipras.wasmer.app` and the PHP
  runtime config (`app.yaml`, `wasmer.toml`, `config/php.ini`) is deploy-local.
