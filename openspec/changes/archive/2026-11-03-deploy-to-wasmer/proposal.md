# Proposal

## Why

SIPRAS currently runs only on a local XAMPP machine, so teachers and peers cannot see the project live. The teacher's revision list (revisi.txt) calls for self-service flows and a public landing page — a hosting target is needed regardless. Wasmer Edge (wasmer.io) was chosen after comparing hosts: it is free at this scale (100k requests/month, 1 app, 100 MB MySQL, persistent volumes) and bundles the two things SIPRAS actually needs — an auto-attached MySQL database and persistent storage for uploaded evidence/proof images.

## What Changes

- New deployment configuration at the project root so `wasmer deploy` serves the whole app on Wasmer Edge:
  - `app.yaml` — volumes, scaling (`single_concurrency`), instaboot pre-warm.
  - `config/php.ini` + `wasmer.toml` — PHP runtime pin (`php/php =8.3.4`), `upload_max_filesize`/`post_max_size` above SAPRAS's 2 MB evidence limit.
- Uploads become persistent on the Edge: a volume mounted at `public/storage` (real directory, no symlink in the chain), with the `public` filesystem disk root made relocatable via env so local development keeps Laravel's default layout (`storage/app/public` + `storage:link`).
- The auto-attached Wasmer MySQL is adopted: `DB_CONNECTION=mysql` plus the prepopulated `DB_*` env vars. No schema work — migrations already run on MariaDB locally.
- One-shot database initialization strategy (`migrate --seed`) determined by a spike before wiring (candidate: a key-guarded setup route; alternative: pre-seeded SQLite file shipped on a volume).
- Secrets and runtime env are set as Wasmer app secrets (`APP_KEY`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`), not committed.
- No application behavior changes: routes, controllers, Blade, tests, and the dev XAMPP workflow are untouched.

## Capabilities

### New Capabilities

- `edge-hosting`: SIPRAS runs as a Wasmer Edge app — deploy configuration, persistent upload storage adopted to the Edge runtime, the attached remote MySQL database, secrets handling, and the health/verification checks that prove a deployment works.

### Modified Capabilities

- (none — the project has no existing specs)

## Impact

- **New files** (deploy-only): `app.yaml`, `wasmer.toml`, `config/php.ini`.
- **Small code change**: `config/filesystems.php` public disk root becomes env-driven (default unchanged locally).
- **No changes** to routes, controllers, models, Blade views, migrations, or tests; dev workflow (XAMPP MariaDB + `composer serve`) unaffected. `phpunit.xml` still runs SQLite in-memory.
- **Dependencies**: none new beyond install of the Wasmer CLI on the deploy machine.
- **Docs**: `AGENTS.md` gains a hosting section after implementation.
- Requires a Wasmer account (CLI auth); app entry URL will be `https://<app>.wasmer.app`.
