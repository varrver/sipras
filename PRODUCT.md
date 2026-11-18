# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Two primary audiences, both Indonesian-speaking in a school environment:

- **Siswa (students):** log in to report broken or damaged school facilities, attach photo evidence, and follow up on the status of their reports.
- **Admin (school staff):** log in to review incoming reports, assign work (in-progress), reject reports that lack evidence or are off-scope, and close resolved reports with proof and notes.

## Product Purpose

SIPRAS (Sistem Informasi Pengaduan Sarana Sekolah) is a school facilities complaint-reporting app. It lets students file tracked complaints about school facilities and lets staff verify and resolve them, ending with a resolvable, status-transparent flow (pending → in progress → resolved / rejected). Success means a complaint a student files is seen, acted on, and either fixed with documentation or explicitly rejected with a reason.

## Positioning

A facility-complaint system built for schools whose mechanism is the ticket: every complaint is a `TKT-YYYYMMDD-XXXX` ticket that carries status, photo evidence, admin notes, and a resolution timeline from first report to close.

## Operating Context

- Students and staff use browser on school computers; layouts must remain usable on small screens (mobile web).
- Students attach photo evidence (`jpeg/png/jpg`, ≤2 MB) when filing; staff may attach a resolution photo.
- Statuses: `pending`, `in_progress`, `resolved`, `rejected`. Rejection requires a note; resolution records a timestamp (`resolved_at`).
- Evidence and resolution images live on the `public` storage disk (`complaints/evidence/`, `complaints/resolved/`).
- Master data (categories, rooms) is maintained by the admin.

## Capabilities and Constraints

- Role-based access via `role:admin` / `role:siswa` middleware; Breeze auth.
- Full siswa flow: file, list own, and view own complaints only.
- Full admin flow: list/search all complaints, detail view, status updates.
- Stack constraint (binding): Blade + Tailwind CSS 4 (Vite) + Alpine.js only — no Vue, React, or Livewire.
- All UI copy, labels, and validation messages are Indonesian; code identifiers and table/column names are English.
- Localization: `APP_LOCALE=id`, dev DB is MariaDB (XAMPP), tests run on in-memory SQLite.
- Ticket number auto-generated on model create; not user-editable.
- Undecided: none recorded.

## Brand Commitments

- Product name: **SIPRAS**.
- UI language: Indonesian; identifiers English.
- Visual constraint volunteered by the user (binding): the interface follows an **Apple/iOS design language** — systemBlue-family primary (`#0066CC`, AA-adjusted from iOS systemBlue), grouped `#F2F2F7` background with flat white cells (no elevation shadows), capsule controls, and the Apple system font stack. Token names stay M3-compatible (`surface/container/on-*`) so views keep compiling untouched, but the implemented world of record is `DESIGN.md`'s Apple/iOS system, not Material 3.

## Evidence on Hand

- `PRD.md` — authoritative feature specification (routes, schema, business rules, execution phases).
- Seeded dev data: 1 admin + 3 siswa users, 4 categories, 4 rooms (password `password` for all seeded users).
- Currently shipped world: Breeze Blade chrome with indigo-600 primary, white cards on gray-100 ground, gray-800 branding-derived buttons replaced by indigo in earlier polish.

## Product Principles

- **Traceability:** every complaint has a ticket and a legible status trail students can trust.
- **Clarity for non-technical users:** students are schoolchildren; statuses, forms, and outcomes must read instantly.
- **Admin efficiency:** triage (search, filter, bulk-visibility of state) beats flashy reporting.
- **Honest status communication:** rejection always carries a reason; resolution is proven with a timestamp and photo.
- **Constraint respect:** the required stack (Blade/Tailwind/Alpine) is non-negotiable and the visual system must live entirely inside it.

## Accessibility & Inclusion

No product-specific accessibility requirement was established beyond web-platform baseline (semantic HTML, keyboard-operable controls, sufficient contrast).