# DESIGN — SIPRAS (Apple / iOS language)

> **One-line brief:** SIPRAS is styled as a native Apple app — iOS system
> colors, SF-style system typography, iOS grouped cells, capsule buttons,
> hairline separators, translucent sticky chrome. No external fonts, no
> Material 3 tokens. Light mode only.

> **This file is the design source-of-truth.** PRD.md defines *what* the app
> does; this file defines *how it looks*. A change to a semantic ("Menunggu"
> tint, primary color, radius, font stack) belongs here **and** in the
> matching `--color-*` / `--font-sans` tokens in `resources/css/app.css`
> (`@theme`), which every Blade view consumes by name. Keep the two in sync.

---

## 1. Design principles

1. **System first.** Use the platform's own fonts, colors and rhythm.
   SIPRAS runs in a Safari/desktop webview; the Apple system palette and
   SF text feel native in any of them and require **zero web-font download**.
2. **Deference.** Content is primary; chrome is faint. Navigation, borders
   and non-essential elements recede behind dark-gray hairlines on light
   gray, never compete with it.
3. **Clarity.** A self-evident layout clears one screen at a time, uses
   plenty of air, and lets information rise to the top with subtle emphasis.
4. **Status via color, not prose.** Complaint workflow states are
   immediately readable as iOS tint chips (`Menunggu` orange, `Dalam Proses`
   indigo/blue, `Selesai` green, `Ditolak` red) — no legend needed.
5. **Definition.** Hairslines + tint fills create crisp edges where shadow
   depth isn't invited. We stay flat: no drop shadows anywhere.

---

## 2. Core design language

### 2.1 Geometry & anatomy

| Piece | Spec |
|---|---|
| **Button** | Capsule (pill) `rounded-full`, `min-h-11`, `min-w-11` click target, `px-5 py-2.5`, `text-sm font-semibold`. Press → `scale-[0.98]` + `active:bg-…/90`. |
| **Icon button** | `rounded-full`, `h-11 w-11`, hairline focus, SF-symbol strokes `stroke-width 1.8` 44px tap area. Hand-rolled and neutral (`text-on-surface-variant`) — the drawer/menu triggers and the back affordance (`x-back-button`, its `aria-label` names the destination — see §3) all consume this one skin. |
| **Field / input / select / textarea** | `rounded-lg (rounded-xl)`, hairline `border-outline-variant`, `bg-surface-container-low`, `placeholder:on-surface-variant`, `focus:ring-4 focus:ring-primary/20 focus:border-primary`, `py-2.5 px-3.5 text-sm`. |
| **Status chip** | `rounded-full`, `font-semibold text-xs`, tint fill (`*-container`) + `*-container` text. `<x-status-badge status="…" />` emits exactly this. |
| **Status note box** | `rounded-xl bg-*-container p-4 text-on-*-container`, small semibold heading (`h3 font-medium`), sm body; siswa status banners and stored-decision notes (Catatan/Tolakan) hand-roll this tint anatomy from §3. |
| **Filter chip** | `chip-filter` → `rounded-full min-h-11 px-4 text-sm`, outlined (`border-outline bg-surface text-primary`), active = `border-primary bg-primary text-on-primary`. |
| **Card / group header / section** | `rounded-2xl(rounded-xl) bg-surface-container`, `visible border-outline-variant hairline`, iOS grouped-cell cell. The `.card` class is the one implementation and carries all three — views must not re-type the shell. |
| **Table row / cell** | body `bg-surface-container` + `divide-y divide-outline-variant` hairlines, row hover `bg-surface-container-high`. |
| **Drawer / sidebar admin** | `bg-surface-container-low`, `w-64`, desktop `lg:sticky lg:h-screen`, hairline `border-outline-variant`, active nav → `bg-primary/10 text-primary` capsule pill. |

### 2.2 Typography

- **Family:** Apple system stack — `-apple-system, BlinkMacSystemFont, "SF Pro
  Text", "SF Pro Display", "Segoe UI", Roboto, "Helvetica Neue", Arial,
  sans-serif` (set once in `app.css` `@theme` as `--font-sans`; **do not import
  any remote font file**). Weights: 400/500/600.
- **Monospace (`--font-mono`) is for ticket numbers only:** `ui-monospace,
  "SF Mono", "Cascadia Mono", "Segoe UI Mono", Menlo, Consolas, monospace` —
  SF Mono on Apple platforms, Cascadia on modern Windows, so `font-mono`
  never falls straight through to Consolas.
- **Scale:**
  - Page title: `text-2xl font-bold tracking-tight` (24px/32px) — the `<h1>`/`<title>` heading every page must name (`@section('title', …)`).
  - Section title: `text-2xl font-bold`.
  - Sub-list/cell title: `text-[17px] font-semibold`.
  - Body / label: `text-sm font-medium`, secondary `text-xs`.
- All copy localised Indonesian via `__('/Trans…')`.

### 2.3 Color & tokens

Semantic tokens are defined **once** in `app.css` `@theme`, then referenced as
`bg-primary`, `text-primary-container`, `bg-surface`, etc. Porting/rebranding
is a single-file change.

| Token | iOS color | Value | Use |
|---|---|---|---|
| `bg-surface` | systemGroupedBackground | `#F2F2F7` | app / grouped chrome |
| `bg-surface-container` | systemWhite (cells) | `#FFFFFF` | cards, cells, fields |
| `bg-surface-container-low` | systemWhite | `#FFFFFF` | cells / fields fill |
| `bg-surface-zebra` | faint neutral (print zebra) | `#FAFAFA` | alternating table rows — the printed recap |
| `bg-primary` | Apple web link blue | `#0066CC` | primary actions / links |
| `bg-error` | deep maroon (danger) | `#8B1D2C` | destructive buttons (`.btn-error`) |
| `bg-primary-container` | blue (tint) | `#E6EEFF` | primary tint: selection wash, avatar circle, subtle hovers |
| `text-on-primary-container` | deep navy | `#123E86` | text on the primary tint |
| `bg-success-container` | systemGreen (tint) | `#E7F9E9` | *Selesai* chip, resolved banner, success flash |
| `text-on-success-container` | systemGreen deep | `#1E7B34` | text on the green tint |
| `bg-success` | systemGreen (solid) | `#1E7B34` | timeline completion node — filled check on a finished track |
| `text-on-success` | white | `#FFFFFF` | check icon on the success fill |
| `bg-warning-container` | systemOrange (tint) | `#FFE9D6` | *Menunggu* chip, pending banner |
| `text-on-warning-container` | systemDeepOrange | `#A54A00` | text on the orange tint |
| `bg-tertiary-container` | systemIndigo (tint) | `#E8E8FD` | *Dalam Proses* chip + banner |
| `text-on-tertiary-container` | systemIndigo deep | `#5856D6` | text on the indigo tint |
| `bg-error-container` | rosier maroon tint | `#F7E4EA` | *Ditolak* chip, rejection banner, errors |
| `text-on-error-container` | wine | `#6D0F26` | text on the error tint |
| `bg-secondary-container` | systemGray5 fill | `#E5E5EA` | **neutral metadata**: category / room / role chips, `.btn-secondary`, icon hover, file button |
| `text-on-secondary-container` | systemGray deep | `#656569` | text on the neutral fill |
| `text-on-surface` | labelPrimary | `#1C1C1E` | primary text |
| `text-on-surface-variant` | labelSecondary | `#6E6E73` | secondary text / placeholders |
| `border-outline` / `border-outline-variant` | systemGray hairline | `#C6C6C8` / `#E5E5EA` | separators |

> **Neutral surfaces are flat, and they are one set.** `bg-surface` is the
> grouped background the cells sit on. The app is flat, so every
> `surface-container-*` elevation step resolves to the same white — the M3
> levels exist as slots, not as a ladder. `surface-zebra` (`#FAFAFA`) is the
> one faint fill between the two, spent only on the printed recap's alternating
> rows. `on-surface` / `on-surface-variant` are the label colours that sit on
> all of them. The recap mirrors every one of these by hand; `ReportTest` keeps
> that honest (§5).

> **One hue, one meaning.** A colour token is spent on exactly one role, and
> status hue is reserved for workflow status — so metadata (category, room,
> role) is **neutral gray**, never a status tint. Action/selection is blue,
> *Selesai* green, *Menunggu* orange, *Dalam Proses* indigo, *Ditolak* red, and
> nothing else borrows those hues. Two rules follow:
>
> 1. **Never spend a status hue on metadata.** A category chip in *Menunggu*
>    orange makes the status chip stop meaning anything.
> 2. **Never let a success message and the primary action share a token.** The
>    success flash and the *Selesai* chip use `*-success-*`; `primary-container`
>    is a genuine tint of `primary`, never a borrowed hue.
> 3. **Primary and error stay far apart.** `primary` is Apple's web link blue
>    `#0066CC` and `error` is a deep maroon `#8B1D2C` — a full hue-family apart
>    (cool vs warm), so a filled button never reads as both "act" and "danger".
>    Do not let `error` drift toward any warm bright hue, and do not spend the
>    blue family on anything but the action/selection role.

> **Note:** the `--color-*` token *names* are Material-3-compatible so every
> Blade view that references `bg-primary-container`, `on-primary-container`, etc.
> keeps compiling untouched. **Only the values** are Apple system colors.

> **Accessibility (WCAG AA):** raw Apple systemBlue `#007AFF` measures only
> 4.02:1 with a white label — it can fill a link, but not a 14px filled button.
> Primary is therefore held at the blue Apple itself ships for web links,
> `#0066CC` (white label 5.56:1, on `#F2F2F7` 4.99:1, hover `/90` 4.65:1) and
> `error` at deep maroon `#8B1D2C` (9.11:1), so every state clears 4.5:1.
> The *Dalam Proses* chip left the blue family for true systemIndigo `#5856D6`
> (4.69:1 on its `#E8E8FD` tint) so "in progress" and "action" stay different
> hues. Keep any future token change passing that same set — the numbers are
> recorded in `app.css` beside each token. Do **not** lighten a text colour on
> hover (`hover:text-primary/80`): at 80% alpha it drops below AA.

### 2.4 Motion

- **Easing:** Apple's `cubic-bezier(0.16, 1, 0.3, 1)` on completes, `ease-in-out`
  for emphasis; iOS-like `250ms`.
- **Buttons:** press → `scale-[0.98]` (`active:`), lift on tap.
- **Dropdown / drawer:** slide + fade `duration-300`, `ease-out`.
- **Reduced motion:** respect `prefers-reduced-motion` — a global rule in
  `app.css` snaps every transition/animation to ~0ms (focus is never lost;
  no non-essential animation).
- **The success flash dismisses itself** after 5 s with a 300 ms fade
  (`role="alert"` announces it on arrival, so the later removal cannot
  surprise a screen reader). It is Alpine-progressive: without JS the flash
  simply stays — dismissal must never depend on the script having booted,
  so unlike the admin form panels it carries **no `x-cloak`**.
- **Focus:** `focus-visible` on interactive elements, `2px solid var(--color-primary)`
  + `ring-offset-2`.

### 2.5 States & quirks

- **Hover:** `bg-surface-container-high` (1.5% black) / `hover:bg-secondary-container/80`
  (neutral) / `hover:bg-primary-container/40` (blue wash, for primary actions).
- **Active/pressed:** `scale-[0.98]`, tint fill.
- **Disabled:** `disabled:opacity-40 disabled:pointer-events-none`.
- **In-flight submits swap the button for its busy label.** The posting form pins
  `submitting` (`@submit="submitting = true"`), the primary button carries
  `x-bind:disabled="submitting"` — never the `:disabled=` shorthand, which Blade
  compiles as a PHP expression and 500s the page — and the label swaps to a
  `x-spinner` plus a context verb (`Mengirim…` / `Menerapkan…` / `Menyimpan…`),
  with the swapping span `aria-live="polite"`. The flag is progressive: no JS, no
  flag, and the form still posts; a validation redirect resets it on reload.
- **Native bubble accepted — do not mimic it in Alpine.** `required`/`maxlength`
  on the inputs fire the browser's own bubble (in the browser's locale,
  unstyleable). That is an accepted tradeoff, not a bug: mimicking it would
  duplicate a validation policy (Alpine + server) and drift from it; the
  Indonesian server messages under each field (`<x-input-error>`, announced
  `role="alert"`) remain the copy that carries the "why" after a real submit,
  and helper lines speak the rule in Indonesian where the choice happens.
- **Severity / status semantics:** never hardcode raw `#c92f26`; always leave to token
  `bg-error` / `bg-error-container`.

### 2.6 Copy & terminology (Bahasa Indonesia)

- **Glossary — one word per concept.** `Pengaduan` (never "laporan"/"aduan"),
  `Ruang` (never "kelas"/"lokasi"), `No. Tiket` as a column header (`nomor tiket`
  in prose), statuses exactly `Menunggu` / `Dalam Proses` / `Selesai` / `Ditolak`,
  and `Perbarui Status` for both the section heading and its submit button.
- **A note is two different things.** `Alasan Penolakan` is required and will be
  read by the reporter; `Catatan Admin (opsional)` is optional. Never one neutral
  label covering both. It is a single `<label>` element whose text switches with
  the status, not two labels pointing at the same control.
- **Requirements come before submission.** A rule the server enforces must be
  visible in the form — label the field, then say what happens (*Wajib diisi saat
  menolak — alasan ini akan dibaca siswa*) — and bind `x-bind:required` so the
  browser enforces the same rule instead of letting the admin discover it as a
  422.
- **The form reads correctly before Alpine boots.** Server-render the state the
  page loaded in (`old('status', $complaint->status)`) and let the `x-show` /
  `x-bind` bindings take over only *changes*. Otherwise a status-dependent label
  or hint renders twice or states a rule that doesn't apply yet.
- **Required vs optional is always stated**; optional fields say `(opsional)` in
  the label.- **Formats are stated, not discovered:** `JPG atau PNG, maksimal 2 MB`, matching the FormRequest rules and messages.
- **The file trigger is authored, not native.** Every upload field (create form,
  admin & teknisi proof updates, CSV import) opens the picker from an authored
  capsule: a `<label class="file-field">` wrapping an `sr-only` input (kept in
  the DOM for native validation and tests). The exposed native button printed
  the browser's own English *"Choose File"* — the only text in the UI that no
  locale switch could translate. The capsule label text is Alpine-switched
  (`x-data` + `x-text`) from the invitation (`Pilih foto bukti…`) to the picked
  filename; without JS the invitation still opens the picker and the format
  sentence under it states the rule (`Wajib.` / `Opsional.`). The whole skin
  lives in one class, `app.css`'s `.file-field` — the neutral `secondary-container`
  capsule with `min-h-11/active-scale/focus-within:ring-4`, same as the buttons.
  Nested forms re-declare nothing.
- **Below-the-fold photos wait their turn:** detail-view images carry
  `loading="lazy" decoding="async"` — an evidence photo 900px down must not
  compete with the status timeline for first paint.
- **Dashboard stat cards are pages, not paragraphs.** On both dashboards each
  card is an `<a class="card card-hover">` into a status-filtered list (the
  total → the bare list). The visible number carries `aria-hidden` and an
  `sr-only` unit (`— N pengaduan`) because "21" alone means nothing to a screen
  reader; the label names the filter it opens.
- **A missing image never renders the browser's broken-image glyph.** Detail views hide the glyph (`onerror`)
  and show a labelled callout instead (`Berkas foto bukti tidak ditemukan di penyimpanan.` on admin;
  `Foto bukti tidak dapat ditampilkan.` on siswa) — same neutral `secondary-container` fill both sides.
- **Tickets and status chips never wrap.** Ticket numbers and `x-status-badge` carry `whitespace-nowrap` —
  a ticket broken across lines reads as two different tickets.
- **Alpine-revealed blocks carry `x-cloak`** (the `[x-cloak] { display: none !important; }` rule lives in the
  base layer of `app.css`): a server-side `style="display:none"` and a bare `x-show` flash hidden content
  for one frame before Alpine boots. The admin status form's conditional sections and the create form's
  preview are the instances.
- **Labels name the outcome**, not the gesture or the mechanism: `Hapus filter`
  (it clears the query *and* the status chip) rather than `Reset`.
- **Every control has an accessible name,** even when the design has no room for
  a visible label: the admin search field carries a `sr-only` label
  (`Cari pengaduan`) so the placeholder stays an example.
- **Every data table has a name too.** A nameless table is announced as just
  "table" and cannot be listed or jumped to. The app tables carry a `sr-only`
  `<caption>` (`Daftar pengguna aplikasi`) — visually silent because the intro
  paragraph above already says it. The printable report is the exception: it
  names its two tables with `aria-label`, because the same markup is the dompdf
  source and dompdf ignores `position: absolute`, so an `sr-only` caption would
  print as visible ink.
- **Every page names itself, in the same words, twice.** The `<title>` is the
  page's own `<h1>` plus the ` — SIPRAS` suffix, on both shells
  (`@section('title', …)` for the app shell, `:title="…"` for the guest shell).
  WCAG 2.4.2 is invisible to axe (`document-title` passes on any non-empty
  string), so the pair is pinned per page by `tests/Feature/PageTitleTest.php`.
  Two consequences worth remembering: the brand wordmark is never the `<h1>`
  (the printable report's wordmark is a `<p class="brand">` and its document
  title is the `h1`), and a heading does not repeat the product name the shell
  already shows — so the sign-in card's heading is `Masuk`, not `Masuk ke SIPRAS`
  (the card sits inside the landing, whose own `h1`/`<title>` is the hero sentence).
- **Neither shell has a fallback title.** A page that arrives at
  `x-app-layout` without `@section('title', …)`, or at `x-guest-layout` without
  `:title="…"`, throws (naming the route) instead of rendering a tab that says
  only `SIPRAS`, so the omission cannot ship as a silent WCAG 2.4.2 failure.
  `<x-error-card>` holds the same line — see §4.
- **Filters are symmetric:** the search form carries the active status and
  period, the picker forms carry the active keyword. One interaction never
  silently discards the other's choice.
- **Empty states separate first use from no-results** (`Belum ada pengaduan masuk.`
  vs `Tidak ada pengaduan yang cocok dengan filter ini.`) and offer the next action.
- **The first-use card is also the onboard surface.** It answers "what happens
  next?" with the *live* status vocabulary — real `<x-status-badge>` chips
  (Menunggu → Dalam Proses → Selesai) and one sentence per status, plus the
  ticket-number and rejection-reason promises. Static Blade, no script and no
  stored state; it renders exactly where the promise is made and shows no colors
  the detail page won't honor. No tutorial mode, no storage-gated tooltip: the
  audience is captive, not exploratory.
- **Error copy** follows the same rules — see the error pages in §4.

### 2.7 Admin triage (complaint list)

- **Counts live on the dashboard cards, never on a list.** The status tallies
  are the dashboard's job: each card calls `statusCounts()` bare, so its number
  is all-time and no list filter can make two screens disagree. A list is a
  filtered view of rows and shows no tallies at all — no count chips, no count
  in a picker menu. The numbers a list would have quoted already sit on the
  dashboard, where they are clickable.
- **A dashboard stat card is a link, not a label.** Each card links to the list
  filtered to what it counts (Total → the bare list, Each status → `?status=…`),
  and the count is part of its accessible name (`Menunggu — 12 pengaduan`)
  rather than a bare `Menunggu` link: the visible number is `aria-hidden` and a
  `sr-only` span carries the unit, so a card is never read as "Menunggu 12".
- **Status changes live on the detail page, and only there.** The list finds and
  reads; a row links into the detail page, which owns the status form — reason,
  proof, and all. No row-level status control, so there is exactly one place a
  status can change and the list can never be a second source of truth.
- **The mobile card is structured content, not one link.** A single `<a>` around
  ticket + badge + title + meta is announced as one run-on name
  (`TKT-… Dalam Proses pc rusak Budi Santoso · Lab Komputer 1 · 23 Sep 2026`).
  The card is a `relative` container whose *title* is the link (an `h2`), with
  the link's `::after` stretched over the card (`after:absolute after:inset-0`)
  so it stays one tap target while assistive tech reads the title, reporter, room
  and date as separate content. No `<h3>` inside a card: the page's only other
  heading is the `h1`, and the jump trips `heading-order`.
- **The shared list pattern has three surfaces** — admin triage, teknisi queue,
  and since the siswa-table change the student's own list: a named table (per-
  cell `Detail` anchor in the Aksi column) / below the breakpoint the same
  mobile card. The breakpoint is role-aware because the available width is:
  siswa and teknisi show the table at `lg` (1024px), admin only at `xl`
  (1280px) — its sidebar takes 16rem from `lg`, which would squeeze the seven
  columns into ~656px and force horizontal scrolling. The admin table's Judul
  cell also carries the category under the title: category is a filter
  dimension, so a filtered row must name why it matched. The student table
  speaks the student-scoped column set (Status ·
  Tipe kerusakan · Ruang · Keluhan · Tiket · Tanggal — no Pelapor column:
  the student's reports are all their own) and its fresh-created `?created`
  ring attaches to the body that renders (the `<tr>` or the card), not an
  anchor.
- **Filter architecture is one toolbar of capsule pickers, built from the
  app's shared class vocabulary.** Search owns the top row; category, status
  and period sit on the row below it. There is no status-chip tier and no
  active-filter feedback band — every dimension announces itself at its own
  control, so a strip that re-stated them could only be noise.
  - **Tier 1 (Unified Action Toolbar):** A compact single-row toolbar uniting
    search (`#q` — matches every field the row displays: title, ticket
    number, description, and the reporter and room name columns, via the
    shared `scopeSearch`), the category picker, the status picker (the shared
    vocabulary only — no counts in the menu), period dropdown/popover
    (`<details>` carrying presets + custom range inputs with bounds sync
    *and* both dates `required`, so a half-filled range cannot silently
    no-op; outside-click and Escape close it), and the `Cetak Sesuai Filter`
    report link on the right. Every trigger wears the capsule family (44px
    floor) — the category picker through `x-select-menu`'s opt-in `capsule`
    shell (primary fill while a category is applied), the period trigger
    through its `list-none` summary — no `rounded-xl`, no size overrides.
    A trigger names its dimension until a value is applied — `Kategori`,
    `Status`, `Periode` (via `placeholder-when-empty`) — then reads the
    choice; the menu's first row stays the descriptive clear row (`Semua
    kategori`, `Semua`), and the period popover keeps `Semua periode`. The
    three triggers share one compact, equal-width grid (`sm:grid-cols-3` at
    `10rem` a column, one full-width column when stacked on a phone), so the
    cluster keeps a single rhythm whichever labels the applied filters put in
    it while still reading as filters — a long pick truncates instead of
    resizing its trigger and pushing its neighbours.
    Form pickers (`siswa` create, master-data forms) keep the `.field` skin
    so they still line up with the inputs beside them; the capsule is
    opt-in, never a global restyle. There is no help caption under the
    toolbar: the search placeholder and the period trigger already name what
    each control takes, and a second line restating them was noise.
  - **Tier 2 is gone — status is the third capsule picker.** `x-select-menu`
    with `capsule` and `form-autosubmit`, its options carrying the shared
    `STATUS_LABELS` vocabulary only (`Semua`, `Menunggu`, `Dalam Proses`, …).
    Counts were removed from the menu: they belong to the dashboard cards, and
    in a picker they were hidden behind a tap while making the trigger long.
    The applied status fills the trigger and marks `aria-current`; a junk
    status resolves to no selection. **Every role's list speaks this one
    grammar.** The student list carries the status picker alone (one 10rem
    column); the technician queue carries period + status (two columns) and
    drops the category picker because its whole queue is already one category.
    Both reuse the same compact, equal-width grid, the same dimension-named
    triggers, the same keyword/period hidden-field hand-off between forms, and
    the same global `Hapus filter` reset end-aligned after the pickers. So
    `.chip-filter` now survives only where the control really is an action —
    the period popover's preset chips — never as a status tier.
  - **No active-filter feedback strip of any kind:** There is
    **no band, no "Filter aktif:" lead-in, and no keyword tag** — the
    keyword is still visible in the search field it was typed into, so a
    `Kata kunci: "…"` tag re-stated what the screen already says (the same
    reasoning that removed the header before it). **Status is deliberately
    not tagged** — its state lives entirely on the picker's trigger (filled
    capsule + `aria-current`), and a junk status selects nothing, so echoing
    it would contradict the picker on the same screen. **The period is deliberately not tagged either** — its state
    lives entirely on the trigger (filled capsule + window label; for a preset
    the matching preset chip is the marked one; the tanggal-masuk basis is
    stated once in the toolbar help line), and a resolved window already owns
    `aria-current`. The band carried no per-dimension ✕
    links when it existed — removal happens at source: the status picker
    offers `Semua`, the period popover carries `Semua periode`, and emptying
    the search field cancels the keyword. The **global `Hapus filter` reset
    rides the toolbar** — plain `btn-outline` capsule end-aligned with
    `ms-auto`, at the same 44px `min-h-11` height, as the filter cluster's
    visibly last item. (The earlier per-✕ hit-box geometry — negative-margin
    44px boxes, `gap-y-4` bleed guards — was removed with the ✕'s; the pins
    are `test_the_active_keyword_lives_in_the_field_not_a_tag`,
    `test_nothing_tags_the_status_dimension`, and the trigger-only period pins in
    `ComplaintPeriodTest`.)
- **The list is always newest-first; there is no sort control.** The former
  `?sort=attention` toggle was removed: a sort is not a triage action, and
  its label could only ever describe, not filter. Status ordering lives in
  the picker; the list's one ordering is newest-first.
- **The period is a filter, its presets are chips — one policy on every
  surface.** The list and the report show the same thing: the four presets
  always visible, the active window marked with the active treatment and
  `aria-current="true"` on the preset whose bounds equal the model-resolved
  pair (a custom range instead lights up the period trigger, the control that
  defines the view). Presets materialize `from`/`to` into hrefs (no `period=`
  token — a saved URL prints the same window forever). While a period is
  active, the trigger names the window (custom ranges carry `aria-current`; a
  reversed pair adds the "rentang dibalik otomatis" note to the trigger, its
  only honesty marker) and takes focus after applying.

---

## 3. Component checklist (`resources/views/components/*`)

### Status badge — `components/status-badge.blade.php`

Mapping (semantic status → iOS tint):

| Status | Label (id) | Chip |
|---|---|---|
| `pending` | Menunggu | `bg-warning-container text-on-warning-container` (orange) |
| `in_progress` | Dalam Proses | `bg-tertiary-container text-on-tertiary-container` (indigo) |
| `resolved` | Selesai | `bg-success-container text-on-success-container` (green) |
| `rejected` | Ditolak | `bg-error-container text-on-error-container` (red) |

In tables that don't use `chip`, `<x-status-badge>` still colours correctly.

The **labels** in that table come from the model (`Complaint::STATUS_LABELS`, via
`statusLabel()`), which is the one source the badge, the filter pickers, the
dashboard cards and the printed recap all read — so no view can
rename a status on its own. Only the *tints* are written here and in the
component: they are design tokens, not vocabulary.

A blank `status` is refused: an empty chip names nothing, and no axe rule catches
one. An unknown but *present* status is not refused — it falls back to its raw
label in the neutral fill, so introducing a status is never a hard failure. The
guard is blank-only, not a whitelist, and `tests/Feature/ComplaintStatusTest.php`
asserts the label map covers `STATUSES` exactly.

### Photo frame — `components/photo-frame.blade.php`

`<x-photo-frame>` is the one detail-photo preview, rendered by all three
complaint detail pages for the evidence photo and the resolved documentation.
It owns the section `h2`, the neutral `bg-outline-variant` frame with the whole
picture (`object-contain`, collapsed to `max-h-52 sm:max-h-96`), the single
`btn-secondary` expand control (`aria-expanded` bound live, `aria-controls`
pointing at the image), and the labelled fallback callout — `hidden` at rest and
unveiled by the image's own `onerror`, so a missing file never shows the
browser's broken-image glyph, even before Alpine boots.

Props: `id`, `src`, `alt`, `title`, `fallback`, and the opt-in `loading` flag.
Every photo with content below it passes it — the student's evidence photo and
both staff photos — so a slow school connection cannot slide the decision
notes or the status card as the image paints: it reserves a 4:3 box and says
`Memuat foto…` meanwhile. The student's resolved photo closes its card, so it
keeps the plain framed preview. Do not hand-roll the frame again — the student
preview regressed to `object-cover` exactly once because it was a copy.

`ComplaintManagementTest` / `TeknisiWorkflowTest` pin the photo ids, the
whole-picture classes and the one-control rule, so the cue arrives without
changing the frame's structure.

### Complaint header + facts — `components/complaint-header.blade.php`, `components/complaint-info.blade.php`

The three complaint detail pages render their page header and record facts
from one pair of components. `<x-complaint-header>` owns `page-heading-row`:
the role's back button plus the ticket title as the `h1` (only the back route
and its Indonesian label are passed in). `<x-complaint-info>` owns the info
`dl` — Kategori chip, Ruang with its code, Diajukan pada — and, with
`:staff="true"`, adds Pelapor/Email and a conditional Selesai pada (only when
the record actually carries `resolved_at`). Pass `staff` explicitly; the
template should not infer the role.

### Account chip — `components/account-chip.blade.php`

The one home of the account avatar token block — the `h-9` `rounded-full`
`bg-primary-container` / `text-on-primary-container` initial circle — so a
token migration edits this file, not three navs (dropdown trigger, mobile
hamburger panel, admin sidebar footer all consume it).

- `variant="inline"` (default) — dropdown trigger: avatar + name, the name
  revealed only from `md` (the trigger must stay narrow on phones).
- `variant="detailed"` — identity block: avatar, name and email, both
  `truncate` inside a `min-w-0` wrapper; email reads `text-xs` (support text
  below the `text-sm` name, one canonical step everywhere).
The initial letter is `aria-hidden`: it is decoration, the readable name
carries the accessible name, so a screen reader never announces a bare
letter first.

### Buttons — `components/buttons.blade.php` / `.btn-*` in `app.css`

- `.btn-primary` → `bg-primary text-on-primary hover:bg-primary/90`.
- `.btn-secondary` → `bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80`
  (neutral gray fill — it is a low-emphasis action, not a warning).
- `.btn-outline` → `border-outline bg-surface text-primary hover:bg-primary-container/40`.
- `.btn-error` → `bg-error text-on-error hover:bg-error/90`.
- `.btn-text` → `text-primary hover:bg-primary-container/40`.
- All share: `rounded-full px-5 py-2.5 min-h-11 text-sm font-semibold`.

**Hierarchy — one filled primary per task.**

- `.btn-primary` marks the screen's own commit: the header CTA (`Buat Pengaduan`,
  `Tambah …`), a form's submit, or a disclosure panel's commit (`Terapkan` in the
  period popover, `Impor` in the CSV dialog) — the last two bounded by their
  panel, never claimed page-wide. A pure list or detail screen may show none.
- `.btn-outline` carries every secondary move — `Cetak`, `Hapus filter`,
  `Tampilkan Semua`, a form's `Batal`, and the toolbar's `Cari`. A list search
  refines the view the page already owns; it is never a second primary next to
  the header CTA, and when a filter is active the filled selection state stays
  the toolbar's only primary fill.
- `.btn-secondary` is the neutral, lowest-emphasis action — modal `Batal`,
  `Ekspor/Impor CSV`, the file trigger. It must not borrow the action hue, so
  `x-secondary-button` compiles to `.btn-secondary`, not `.btn-outline`.
- `.btn-error` is the destructive commit and nothing else: it lives inside the
  confirm dialog. A resting row action is a text link (`Hapus`), and the siswa
  withdraw stays an outline capsule that turns `btn-error` only in its
  confirmation.
- **Selection is state, not action:** the active filter picker, the active
  period trigger and the current pagination page also spend the primary fill. They are
  small, state-carrying controls — the only other place the fill appears.  - **The back chevron is chrome, not an action.** `x-back-button` is a 44 pt
  neutral icon circle (`hover:bg-surface-container-high`) that sits inline
  before the page's `<h1>`, and whose accessible name
  names the destination; being icon-only and unfilled, it recedes behind the
  page's one primary action instead of competing with it. Navigation never
  spends the action fill — that belongs to actions and selection states alone.

### Fields — `components/fields.blade.php` / `.field`

```blade
<input … class="field" />   <!-- on-surface hairline, iOS focus ring -->
```

Native `<select>` elements just use `.field` and get the shared
`select.field` extension automatically: `appearance-none` (the platform's own
arrow never agrees with this world), a 44px `min-h-11` touch floor, and an
authored chevron — the account-dropdown trigger's own glyph in
`on-surface-variant` — painted as a data URI at `right 0.875rem center` with
`pr-10` clearance.

**Dropdowns are rendered in-page, not native** — `components/select-menu.blade.php`
(`x-select-menu`). A laptop browser's device-inspect mode draws native select
popups *outside* the simulated phone frame — the browser's own artifact, which
no page CSS can fix — so the app's dropdowns are real DOM instead: a `.field`
trigger button (same chevron, turning over when open) — or, opt-in, the
filter toolbar's capsule shell (`capsule`, with `active` painting an applied
filter and marking `aria-current`) — plus an absolutely positioned iOS-menu panel (`rounded-2xl`, hairline `border-outline-variant`,
`bg-surface-container`, group headers in 11px uppercase variant ink, options
`rounded-xl` with the `bg-primary-container text-on-primary-container` selection
wash and a check glyph on the chosen row). The form contract is a hidden input
synced by Alpine (`selectMenu` in `app.js`); keyboard: arrows/Home/End move,
Enter/Space pick, Escape closes; the selection reports as a bubbling
`changed` event for callers that bind state to it.
A long menu opts into a search field (`<x-select-menu searchable …>`, the
room picker's 105 options): typing filters the same in-page options by their
label (name + code), a group header survives only while one of its rooms
matches, and the no-match row says `Tidak ada ruang yang cocok`; the trigger
keeps the chosen room's label even while a query hides it. The search field
takes focus on open, the first match is pre-highlighted, ArrowDown/Up enters
the list, and Enter takes the first match. The category picker stays plain.
The account popup (`<x-dropdown>`, desktop top nav) wears the same in-page
menu shell — `rounded-2xl`, hairline border, `shadow-lg shadow-scrim/5`,
`p-1.5`, and `rounded-xl` rows on the same neutral hover — so the chrome's
menus and the form's menus are one family; its motion stays the shell's
200 ms scale/fade, and it keeps disclosure semantics (plain links/buttons,
Tab-nav) rather than the picker's arrow-key listbox.
The mobile drawer's account section is the same surface, not a mobile variant:
its Profil row is an `x-dropdown-link` and its Keluar row carries the same
`rounded-xl px-3 py-2.5 text-sm` geometry, so the two account rows keep one
shape at every viewport.
A pick can auto-submit its surrounding form: the blade prop
`<x-select-menu form-autosubmit …>` renders the opt-in `data-autosubmit="1"`
flag and the component commits the hidden input, then `requestSubmit()`s the
form — GET filter pickers only, never on POST forms. Mechanics that matter:
the component anchors its root/form in `init()` (during a pick, `$el` is the
clicked option row, which sees neither the flag nor the form), and the
submit runs in a zero-delay macrotask (a synchronous `requestSubmit`
inside the click's dispatch stack is ignored by Chrome without any error).
It is progressive at both ends: without JS the hidden input never renders and
a native `<select class="field">` (same options, native `required`) submits
instead — keeping `select.field` as the skin of last resort.

### Chips — `components/chips.blade.php` / `.chip`, `.chip-filter`

Status chips (`chip`): tint fill + tint text, `rounded-full`. Filter chips
(`chip-filter`): `rounded-full min-h-11`, outlined like the toolbar triggers
(`border-outline bg-surface text-primary`, active `border-primary bg-primary
text-on-primary`). They are no longer a status control — the lists' status
filter is the picker — so they survive as the period popover's preset actions,
where a chip really is a click that sets the window. No count pill rides a
chip: counts live on the dashboard cards (see §2.7).
Metadata chips (category, room, role) use the neutral fill
(`bg-secondary-container text-on-secondary-container`) — in a list they sit
beside the status chip, so they must not compete with it for hue. Role chips
distinguish Admin from Siswa by text contrast on the same fill, not by colour.
The dashboard stat numbers use the same `text-on-*-container` hue as the status
they count, so card and chip read as one system; the *Total* card stays neutral.

### Dialog — `components/modal.blade.php`

The panel is `relative z-10` **above its scrim, and that pair is load-bearing**:
the scrim is a `fixed` element, so it paints in the positioned layer, and a
static panel paints *below* it — every click inside the dialog would land on the
scrim and close the dialog (the mouse could not use the delete-account form at
all; only keyboard activation worked, because focus ignores paint order).
Tailwind 3's `transform` utility used to give the panel a stacking context by
accident; in Tailwind 4 it is a no-op, so the stacking must be explicit. The
admin mobile drawer was never affected because it already declares its own
`z-50` over the scrim's `z-40` — that is the pattern to copy for any new
overlay. `tests/Feature/Admin/NavigationTest.php` guards the pair.

Positioning follows the same discipline: the scroll container is
`flex min-h-full items-center justify-center` and the panel centers on auto
margins (`my-auto`), so a short dialog floats near the vertical centre while
tall content keeps scrolling to its top instead of clipping under a centered
flex box. The panel also carries `whitespace-normal text-left`: consumers may
mount a dialog anywhere (the admin tables mount theirs inside a
`whitespace-nowrap text-right` action cell), and `white-space`/`text-align`
are inherited, so without the normalization the dialog's copy refuses to wrap
and is clipped by the panel's own `overflow-hidden`. `ProfileTest` guards the
normalization.

The panel is a real dialog in the accessibility tree, not just a focus trap:
`role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title"`. `x-trap`
only owns the Tab key — it tells assistive tech nothing — so every `<x-modal>`
usage must give its heading `id="{{ $name }}-title"`; a dangling
`aria-labelledby` is an axe violation of its own. The component refuses a blank
or missing `name` outright — it also builds the `open-modal`/`close-modal`
listeners from it, so a blank one cannot be dispatched to, and a blank
`aria-labelledby` would point at nothing. That the reference *resolves* on the
real dialog is asserted in `tests/Feature/ProfileTest.php`.

Destructive actions never use the browser's `confirm()` — it is unstyleable,
CSP-hostile and its copy cannot name the record. Every master-data row instead
dispatches `open-modal` with a per-row name (`confirm-category-delete-{id}`)
to a per-row `<x-modal>` whose body names the record and states the constraint
("masih dipakai pengaduan tidak dapat dihapus"), and the server answers the
same promise: an in-use category/room is refused with a `session('error')`
flash, because the complaints FK is `restrictOnDelete` and a confirmed delete
that hit the raw constraint surfaced as a 500. The user-delete modal goes
further and names the *scale* of its cascade — "beserta :count pengaduan" when
the account owns complaints, a plainer sentence when it owns none — since the
cascade is by design (PRD) and a confirm button must never be pressed blind.

Known axe `color-contrast` **incomplete** (manual review, never a violation) for
the dialog's heading and description: the full-viewport scrim overlaps them
geometrically, so axe reports *“background color could not be determined because
it partially overlaps other elements.”* Measured on the white panel:
`#1c1c1e` = 17.01:1 and `#6e6e73` = 5.07:1.

---

## 4. Layouts

- `app.blade.php` — authenticated app chrome, admin gets `sidebar + main` via
  `navigation-admin`; siswa gets top nav.
- `navigation.blade.php` + `navigation-teknisi.blade.php` — siswa & teknisi top
  nav bars: `bg-surface-container/80 backdrop-blur-md` frosted glass (page
  content slides visibly beneath the 64px bar; the scrolled state adds the
  `border-outline-variant` hairline), sticky, hamburger mobile draw. Plain
  `bg-surface` painted the bar in the page's own gray and read as floating
  pills — do not go back.
- `navigation-admin.blade.php` + `partials/sidebar-nav.blade.php` — admin
  sidebar: `bg-surface-container-low`, hairline border-r, sticky `h-screen`,
  active nav = `bg-primary/10 text-primary` blue pill. Below `lg` the same
  container pins as a sticky top bar (`sticky top-0 z-30`) with the same
  `h-16` height and `h-5` trigger glyph as the siswa/teknisi bars — switching
  roles never shifts the bar or changes the hamburger's weight — so the drawer
  trigger stays reachable while a long complaint list is scrolled; the drawer
  (`z-50`) and its backdrop (`z-40`) ride above it, and the stacking context is
  reset at `lg` so page dialogs and menus keep their own layering. The drawer
  has a scriptless leg from one hidden checkbox (`#admin-nav-check`): the
  hamburger, the scrim and the drawer's close control are all `<label for>` it,
  so a tap opens and closes the drawer with CSS alone (`app.css`), and Alpine
  only mirrors the box for `aria-expanded`, Escape and the focus return — the
  same technique as the siswa/teknisi hamburger, locked at the sidebar's
  breakpoint instead of 640px.
- `guest.blade.php` — auth shell: centered `.card` cell (`rounded-2xl`,
  hairline, `p-6 sm:p-8` — the same shell as the landing's sign-in panel, so a
  visitor moving between `/` and an auth page never sees the cell change
  shape), Apple system font (no bunny). It is the shell for the auth pages *and* the four
  error pages, so it takes a `:title` and renders the app shell's `X — SIPRAS`
  form; the bare app name it used to hardcode fails WCAG 2.4.2 without tripping
  any axe rule.
- `landing.blade.php` — standalone public document (no shell): balanced
  `max-w-5xl` structure — hero 50/50 two-column grid (intro, h1, body, and 3
  value highlights left; login card right) served at `/`. Every surface on the
  page is the shared `.card` class, never a re-typed
  `rounded-2xl border … bg-surface-container` shell. Below the hero:
  symmetrical 3-column "Cara kerjanya" process strip and full-width "Untuk siapa"
  card. Guest GET `/login` redirects here. Authed visitors at `/` bounce to the
  shared dashboard. No registration affordance, no "Lupa sandi" hint. Header
  and footer match `max-w-5xl`.
- `{admin,teknisi,siswa}/pengaduan/show.blade.php` — complaint detail layout:
  unified hero header `<x-complaint-header>` (an icon-only circular neutral back
  button inline before the bold `h1` title, `flex items-center gap-3`, with the
  destination kept only in its `aria-label`; only the back route/label is passed in),
  then one centered `max-w-3xl` record card (`card p-6`) on every role. Inside:
  ticket-number row with `<x-status-badge>` (the number carries an `sr-only`
  `Nomor tiket:` label, not just the mono styling), the shared facts `dl`
  `<x-complaint-info>` (Kategori chip / Ruang / Diajukan pada, plus
  Pelapor/Email and a conditional Selesai pada for staff), `<x-status-timeline>`
  (stacked, no spread), Deskripsi text, framed lazy evidence photo
  (`<x-photo-frame>`: whole-picture preview, expand control, labelled
  fallback), resolved documentation, siswa status banners as hand-rolled tinted divs
  (`rounded-xl bg-*-container p-4` + small `h3` — pending has the walkthrough
  copy, waiting-for-admin pairing), and the stored decision notes
  (`Catatan Admin` / `Catatan Teknisi` / `Alasan Penolakan`), with every staff
  section labelled `h2` under the page `h1`. Staff read the record *before*
  acting: the status action card is a second card (`mt-5`) **below** the
  record, never a side rail, and it stays unpinned — sticky pins slid it over
  the content (`ComplaintManagementTest` / `TeknisiWorkflowTest` /
  `DetailLayoutTest` guard it). The order is deliberate: the evidence is the
  input to the decision, and a narrow column cannot place the form beside it.
- `errors/{403,404,419,500}.blade.php` — localised error pages built from the
  same shell: `<x-guest-layout>` + `<x-error-card>` (icon tile, status code as
  the largest type, Indonesian heading, one sentence naming the recovery, and a
  **role-aware** action — admin → admin dashboard, siswa → siswa dashboard,
  guest → sign-in). Tone marks the kind of failure, not decoration: `neutral`
  (404), `warning` (403 permission wall, 419 expired session), `error` (500).
  Unmatched addresses reach this card through the all-verb fallback route at the
  end of `routes/web.php`, which renders the 404 *inside* the web middleware
  group — the exception path used to render it with no session, offering every
  signed-in visitor the sign-in CTA instead of their own dashboard.
  Error copy follows `harden` discipline: name the problem, name the recovery,
  never leak internals. No stock English Laravel error page should ever ship.
  The card also refuses to render without its content: a missing or blank
  `code`, `title` or `message` throws, because an omitted prop is *undefined*
  (`@props` leaves it so) and a blank one used to render an empty heading that
  axe's `document-title` still accepts.

---

## 5. Contribute / keep in sync

1. Change tokens **only in `resources/css/app.css` `@theme`** (then `npm run build`).
   Every token must be consumed by a utility or a `var()` read — `DesignTokensTest`
   fails on a dead one, so a colour enters the palette only when something spends it.
2. Change layout chrome in the named `layouts/*` view; **do not** hardcode raw
   hex in a Blade view.
   - **One exception:** `admin/reports/complaints.blade.php` is a standalone print
     document that never loads Tailwind, so it cannot read `var(--color-*)`. It
     mirrors the token values literally (ink `#1C1C1E`, primary `#0066CC`, the
     four status tint/hue pairs, neutral fill `#E5E5EA`). **A token change must be
     mirrored there by hand** — it is also the only surface allowed to keep its own
     type (DejaVu Sans, because dompdf has no SF Pro). The hand-mirroring is
     *checked*: `ReportTest` reads the badge's tint classes, resolves them against
     `app.css`, and fails until the recap's pills and summary numbers carry those
     exact values; it also pins the rest of the palette (ink, primary, the neutral
     metadata fill, muted labels, the grouped surface and the hairlines) rule by
     rule, and sweeps the whole `<style>` block so a colour no `--color-*` token
     defines cannot creep in — there is **no exception list**: even the print-only
     zebra fill is a token (`--color-surface-zebra`, `#FAFAFA`).
3. Keep `status-badge` chip classes consistent with §2.3 — statuses are the one
   place semantic color lives.
4. Tests assert **text** (`assertSee`), not classes — so a redesign can restyle
   freely; but never change user-visible Indonesian copy (labels, status text,
   ambiguous var, etc.) without updating the corresponding test.
