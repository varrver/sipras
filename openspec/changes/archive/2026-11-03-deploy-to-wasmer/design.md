# Design

**Context** — see proposal.md. Relevant current state: Laravel 13 on a local XAMPP MariaDB 10.4; uploads go to the `public` filesystem disk (`storage/app/public`, served via a `storage:link` symlink); sessions/cache/queue use the `database` driver; `phpunit.xml` runs in-memory SQLite; the local CLI is PHP 8.5 but `composer.json` requires `^8.3`. No CI, no pre-commit hooks. Wasmer Edge provides PHP 8.3 via the WASM `php/php` package (docs pin `=8.3.4`), an auto-attached MySQL with `DB_HOST/DB_PORT/DB_NAME/DB_USERNAME/DB_PASSWORD` prepopulated, and persistent volumes mounted into the app filesystem.

**Goals / Non-Goals**

- Goals: one-command deploy from the project root; persistent uploads; attached MySQL; no secrets in git; minimal, reversible app-code delta.
- Non-Goals: GitHub-connected CI pipeline (future change); custom domain; email deliverability (the teacher revision that adds register/forgot-password will revisit mail); queue workers/scheduler; performance tuning; landing page/auth revisions (separate changes from the teacher's list).

## Decisions

**D1 — Deploy shape: CLI + descriptor files.** `app.yaml` + a `wasmer.toml` (pinning module `php/php` =8.3.4) + `config/php.ini` (`upload_max_filesize` 20M / `post_max_size` 25M — the app's own 2 MB cap stays the effective limit; `opcache.validate_timestamps = 0` for an immutable artifact), with `PHPRC` pointing at the config dir and `scaling.mode: single_concurrency` (the platform's recommended PHP server mode; sidesteps the multi-instance edge cases of an app that was never built for horizontal scale). Alternatives considered: the GitHub auto-detect flow — dropped for v1 because it assumes a pushed repository and reduces control; models remain valid to add later.

**D2 — Uploads: shared storage rules without a symlink.** The `public` disk root becomes an env-driven path with the default preserved exactly as Laravel default (`storage_path('app/public')`), so local dev changes nothing. On the Edge, env sets the root to a real directory (`/app/public/storage`, the docroot-relative `public/storage`) and `app.yaml` mounts the persistent volume there; the disk `url` stays `/storage`, so all existing Blade URL generation is untouched. `storage:link` remains a local-only step. Alternatives rejected: shipping the `public/storage` symlink in the package (WASI symlink behavior is unverified — too load-bearing to guess); an S3-compatible storage provider (extra account and driver for we need zero capacity).

**D3 — Database: the attached MySQL.** `DB_CONNECTION=mysql` plus platform `DB_*` env vars; the schema already targets the MariaDB dialect locally, sessions/cache/queue continue on the `database` driver. Alternative rejected: SQLite shipped on a volume (it makes the app a single artifact but replaces the platform's intended DB path and moves dialect compatibility risk on us).

**D4 — Database init: decided by spike, before final config.** There is no shell on the Edge (`artisan` cannot be run remotely). Spike first verifies whether the attached MySQL accepts external connections; if yes, `migrate --seed` runs once from the deploy machine against the platform DB (zero app code — preferred default). If not, fallback is a secret-token-guarded setup controller in the app executing `migrate --force` + seeder when the `users` table is empty, giving the idempotency the spec requires; it must be removed or hardened after use. Both paths keep the one-shot property; the spike records the choice.

**D5 — Secrets and env.** `APP_KEY` (generated locally), `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, the filesystem root override, and DB env are Wasmer app secrets/env — never in the repo; local `.env` stays untouched. Mail stays on Laravel's default (`log`) for now.

**D6 — Assets.** `npm run build` runs on the deploy machine right before `wasmer deploy`; the built `public/build` is part of the deployed package. Committed dist or CI build are non-goals for v1.

**Risk mitigations**: [php-wasm extension coverage unverified — dompdf needs dom/xml/mbstring, uploads need PDO/MySQL] → spike deploys an extension probe before any real wiring; [PHP pin 8.3.4 vs deps resolved under 8.5] → spike smoke-tests the running app at 8.3; [volume rename deletes its contents] → the volume name is recorded and never renamed; [DB 100 MB free-tier cap] → schema and seeded data are tiny (~12 complaints), uploads live on the volume not the DB; [cold starts] → instaboot pre-warm request; [rollback] → the previous deployment stays live until the next deploy; deploy-file regressions revert via git and redeploy.

## Migration Plan

1. Spike (probe deploy): phpinfo + extension list, attached-DB connectivity check → fixes D4.
2. Write descriptor files + filesystem-disk env change; local tests must still pass.
3. First real deploy (secrets set); health check green.
4. One-shot DB init per D4 outcome; seeded admin login smoke test on the Edge URL.
5. Remove spike-only artifacts; final deploy; AGENTS.md gains a hosting section.

## Open Questions

- Custom domain: later, only if the teacher wants one.
- CI (GitHub-connected builds): future change, deliberately out of scope here.
