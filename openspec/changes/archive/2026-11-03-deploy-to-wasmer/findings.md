# Spike findings — 1.2 (extensions), live probe https://sipras-probe.wasmer.app

Probed 2026-11-03 with a raw PHP app (`php/php` =8.3.4 pin honored, `main-args = ["-t","/app"]`).

## Extensions (required by Laravel/dompdf)

| extension | present | needed by |
|---|---|---|
| pdo_mysql | **yes** | Laravel MySQL |
| dom | **yes** | dompdf PDF export |
| mbstring | **yes** | Laravel/dompdf |
| gd | **yes** | dompdf image rendering |
| fileinfo | **yes** | Laravel uploads (mime validation) |
| openssl, curl, sqlite3 | yes | Laravel basics / tests parity |
| zip | **no** | — (not used; Composer unzip only runs at build) |
| exif | **no** | — (not used by SIPRAS) |
| intl | **no** | — (Laravel does not hard-require it; Carbon works) |

Verdict: runtime can host SIPRAS as-is; no extension blockers. Raw phpinfo probe app was deleted after the spike (`wasmer app delete varrver/sipras-probe`).

## php.ini defaults on the Edge

`upload_max_filesize=2M`, `post_max_size=8M` — exactly at/above the app's 2 MB evidence cap but hostile (2M limit equals the app limit; a 2 MB file failing platform-side would shadow Laravel's own validation). Task 2.2's `config/php.ini` (20M/25M) is therefore REQUIRED, as designed (D1).

## Template quirk

`wasmer app create --template php-starter` scaffolds `app.yaml` (`package: .`) but no `wasmer.toml` — the first deploy then errors on the missing manifest. The fix used here: hand-write `wasmer.toml` (Laravel guide's minimal form) next to `app.yaml`.

# Findings — 1.3 / first real deploy (addenda)

## D4 RESOLVED — external connectivity works (preferred path chosen)

The platform MySQL is reachable from the deploy machine over TLS:

- `wasmer app database list varrver/sipras --with-password` exposes host/port/user/password.
- Runtime `DB_DATABASE` env is NULL (probe: `/debug-db` showed host/port/user with pw set but name null); the granted database is `db_2b0961fa` (shown by `SHOW GRANTS`). The app self-compensated by connecting with config's default name, so `DB_DATABASE: db_2b0961fa` is set explicitly in `app.yaml` env (non-secret).
- `migrate --force` + `db:seed --force` ran FROM THE LOCAL MACHINE against the Edge DB and landed the full spec world: 4 users (1 admin + 3 siswa), 4 categories, 105 rooms, 12 complaints. SSH inside the app was NOT needed (`SSH is not enabled for this app`; the guarded-route fallback was never built).
- Client trick (MariaDB client, XAMPP): connect with `--ssl`; the server is MySQL 8.4.8 (not MariaDB) but Laravel's mysql dialect is fine.

## Platform behaviors discovered while booting Laravel

1. **Package filesystem is read-only EXCEPT deletions-were.miss** — `storage/` must ship the `framework/{views,...}` subtree INSIDE the package (Laravel's `config/view.php` does `realpath(storage_path('framework/views'))`; a missing dir makes realpath() false → "Please provide a valid cache path" 500). `.wasmerignore` excludes `/storage/app`, `/storage/logs`, `/storage/pail` but KEEPS `storage/framework`. Writes INTO the runtime work (probe confirmed writable `/app/storage/framework/views` after the dir shipped), so no ephemeral volume is needed for compiled views.
2. **Local bundling uses `.wasmerignore`** (gitignore only feeds remote builds). Without it, everything would be packaged — including `.env`, `node_modules`, and the local `public/storage` symlink (which also has an ABSOLUTE target and is outright rejected by the packer).
3. **Composer platform mismatch** — local PHP 8.5 resolved deps needing `>=8.4.1`; the Edge runs 8.3.4 → platform_check 500s. Fixed by `composer config platform.php 8.3.4 && composer update` (design mitigation, also keeps local dev working).
4. **Monolog must stream to stderr** — `LOG_CHANNEL=stderr` in app.yaml env (the fallback `stack` writes to /app/storage/logs, which the bundle excludes and the package fs cannot create).
5. **SSH is disabled for this app** — no in-app shell; D4's machine-side path made it unnecessary.
6. **Deploy uploads occasionally fail transiently** (R2) — plain retry of `wasmer deploy` succeeded each time.
7. **app.yaml full schema validated** — `capabilities.database.engine: mysql`, `volumes` (uploads → /app/public/storage), `instaboot.requests` (/ and /up), `locality.regions: [fr-roub1]` (database is region-limited), `scaling.mode: single_concurrency` all accepted; the database capability attached a MySQL 8.4.8 instance automatically.
8. **php -S (single worker) renders the login page in ~30ms** with instaboot pre-warming `/` and `/up`.

## Secrets posture

- APP_KEY lives as a Wasmer app secret (`wasmer app secret create --app varrver/sipras APP_KEY ...`); repo contains no key and `.env` is excluded from the bundle by `.wasmerignore`.
- All other runtime env (APP_NAME/APP_ENV/APP_DEBUG/APP_URL/APP_LOCALE/APP_FALLBACK_LOCALE/LOG_CHANNEL/LOG_LEVEL/DB_DATABASE/PUBLIC_DISK_ROOT/MYSQL_ATTR_SSL_INSECURE) is non-secret and lives in `app.yaml`.
