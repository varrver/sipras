# Tasks

Reference specs — deploy-to-wasmer/specs/edge-hosting/spec.md; design decisions D1–D6 in design.md.

## 1. Account, spike, and the D4 decision

- [x] 1.1 Install the Wasmer CLI and authenticate (`wasmer --version` prints; `wasmer whoami` identifies the account)
- [x] 1.2 Spike probe deploy of a minimal PHP app that prints `phpinfo()`; verify the runtime exposes the extensions SIPRAS needs (`pdo_mysql`, `dom`, `mbstring`, `gd` presence or absence for dompdf image rendering) and record findings in this change dir
- [x] 1.3 Probe platform-attached MySQL connectivity from the deploy machine (PDO or mariadb client using the `DB_*` envs); outcome records the D4 init path — remote `migrate --seed` from local (preferred) or the guarded setup route fallback

## 2. Deployment configuration

- [x] 2.1 Write `app.yaml`: volume `uploads` mounted at the docroot-relative uploads directory, `scaling.mode: single_concurrency`, instaboot pre-warm of `/`; verify with the CLI's config preflight/validate before deploying
- [x] 2.2 Write `wasmer.toml` (module `php/php` pinned `=8.3.4`, `PHPRC` to config dir, docroot filesystem map) and `config/php.ini` (`upload_max_filesize` 20M, `post_max_size` 25M, `opcache.validate_timestamps=0`); verify preflight passes and later phpinfo reflects the settings
- [x] 2.3 Make the `public` filesystem disk root env-driven with Laravel's default preserved when unset; verify with the full local suite (`composer test`) green and default config equals `storage_path('app/public')`

## 3. Secrets and first real deploy

- [x] 3.1 Generate `APP_KEY` locally and set Wasmer app secrets/env (APP_KEY, APP_URL, APP_ENV=production, APP_DEBUG=false, filesystem root override, DB_CONNECTION=mysql); verify a repository scan of deploy files shows no secret values
- [x] 3.2 Build assets (`npm run build`) and run the real `wasmer deploy`; verify `GET /up` is 200 and guest `GET /` redirects to login (302)

## 4. Database init and end-to-end verification

- [x] 4.1 Initialize the platform DB per D4 (`migrate --seed` from deploy machine, or guarded route if 1.3 chose the fallback); verify seeded admin can log in at the Edge URL and the admin dashboard lists the seeded complaints
- [x] 4.2 Verify upload persistence on the live app: file an evidence upload, capture its `/storage/…` URL, redeploy, and re-fetch the same URL successfully
- [x] 4.3 Verify the PDF report exports on the Edge (`GET /admin/pengaduan/laporan/pdf` after admin login downloads a PDF)

## 5. Cleanup and documentation

- [x] 5.1 Remove spike-only artifacts (probe app/route, temporary files); verify the repo diff contains no probe leftovers and deploy files are final
- [x] 5.2 Add a hosting section to AGENTS.md: CLI auth, exact deploy command, the `never rename the uploads volume` warning (renaming deletes its contents), and the one-shot init rule; verify commands match what was actually run
- [x] 5.3 Final integration check: full local suite green (`composer test`) and `openspec validate "deploy-to-wasmer"` passes

## Workflow follow-up

- Archive the change after the project's review requirements are satisfied.
