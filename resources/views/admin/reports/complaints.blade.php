<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekapitulasi Pengaduan Sarana — SIPRAS</title>
    <style>
        * { box-sizing: border-box; }

        /* A printed document, not app chrome: ink is #1c1c1e, structure is hairlines,
           and the app's blue is spent only on the wordmark and the toolbar. Fonts
           stay on DejaVu Sans because dompdf has no SF Pro — the on-screen preview
           therefore matches the PDF exactly, WYSIWYG for the printed artifact.
           Palette values mirror the --color-* tokens in resources/css/app.css, and
           ReportTest pins every one of them — with no exception. */
        body {
            margin: 0;
            padding: 32px;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1c1c1e;
        }

        .toolbar { text-align: right; margin-bottom: 20px; }
        .toolbar a, .toolbar button {
            display: inline-block;
            margin-left: 8px;
            padding: 8px 18px;
            border: none;
            border-radius: 999px;
            background: #0066cc;
            color: #ffffff;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            font-family: inherit;
            cursor: pointer;
        }
        .toolbar .ghost { background: #e5e5ea; color: #1c1c1e; }
        /* The active period chip echoes the period already written in the
             meta sentence. Underline keeps it palette-free: no new colors in
             the hand-mirrored set this document pins. */
        .toolbar .ghost.current { text-decoration: underline; }
        .toolbar a:focus-visible { outline: 2px solid #0066cc; outline-offset: 2px; }

        .letterhead { border-bottom: 2px solid #1c1c1e; padding-bottom: 12px; margin-bottom: 16px; }
        /* The wordmark is decoration beside the document title, so it is a <p>
           (two classes out-specify `.letterhead p`, resetting the margin). */
        .letterhead .brand { margin: 0; font-size: 20px; letter-spacing: 0.5px; color: #0066cc; }
        .letterhead p { margin: 2px 0 0; color: #6e6e73; font-size: 12px; }
        /* The document title is the page's h1, so it must keep the exact spacing
           the <p> had: `margin` is set in full because a heading's default
           bottom margin would otherwise change the printed layout. */
        .letterhead .title { margin: 12px 0 0; font-size: 13px; font-weight: bold; color: #1c1c1e; }

        .meta { color: #6e6e73; font-size: 12px; margin-bottom: 14px; }
        .meta strong { color: #1c1c1e; }

        .table-wrap { overflow-x: auto; }
        .table-wrap table { min-width: 640px; }
        .table-wrap:focus-visible { outline: 2px solid #0066cc; outline-offset: 2px; }

        .summary { width: 100%; margin-bottom: 18px; }
        .summary td {
            width: 20%;
            border: 1px solid #e5e5ea;
            padding: 8px 10px;
            text-align: center;
            background: #f2f2f7;
        }
        .summary .num { display: block; font-size: 18px; font-weight: bold; }
        .summary .label { font-size: 12px; color: #6e6e73; }

        /* Each count carries the hue of the status it counts, mirroring the
           dashboard stat cards. The label keeps it readable without colour. */
        .num-total { color: #1c1c1e; }
        .num-pending { color: #a54a00; }
        .num-in_progress { color: #5856d6; }
        .num-resolved { color: #1e7b34; }
        .num-rejected { color: #6d0f26; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #c6c6c8; padding: 6px 8px; vertical-align: top; }
        table.data thead th { background: #f2f2f7; text-align: left; font-size: 12px; text-transform: uppercase; color: #1c1c1e; }
        table.data tbody tr:nth-child(even) { background: #fafafa; }
        table.data .center { text-align: center; }
        .ticket { font-family: 'DejaVu Sans Mono', 'DejaVu Sans', monospace; font-weight: bold; white-space: nowrap; }

        /* Status pills mirror the app's status chips. The Indonesian label carries
           the meaning, so the recap still reads in black-and-white printing. */
        .st { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: bold; white-space: nowrap; }
        .st-pending { background: #ffe9d6; color: #a54a00; }
        .st-in_progress { background: #e8e8fd; color: #5856d6; }
        .st-resolved { background: #e7f9e9; color: #1e7b34; }
        .st-rejected { background: #f7e4ea; color: #6d0f26; }

        .foot { margin-top: 24px; position: relative; height: 90px; }
        .foot .sign-wrap { position: absolute; right: 0; top: 0; text-align: center; font-size: 12px; color: #6e6e73; }
        .foot .sign-wrap .sig { margin-top: 52px; font-weight: bold; color: #1c1c1e; }

        .note-empty { padding: 20px; border: 1px dashed #c6c6c8; text-align: center; color: #6e6e73; }

        /* Keep the tints when the recap is printed from the browser. */
        .summary td, table.data thead th, .st {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @media print {
            .toolbar { display: none !important; }
            body { padding: 0; }
            .table-wrap { overflow: visible; }
            .table-wrap table { min-width: 0; }
        }
    </style>
</head>
<body>
<main>
    @unless ($pdf ?? false)
        {{-- Screen-only period controls: the print/PDF sheet must not show
             them, the toolbar row below is pure chrome. Same chip language as
             the list so the two surfaces read as one system. --}}
        <div class="toolbar" role="group" aria-label="Filter periode laporan">
            @if ($period)
                <a class="ghost" href="{{ route('admin.pengaduan.report', array_filter(['status' => request('status'), 'q' => request('q'), 'category' => request('category')])) }}">{{ __('Semua periode') }}</a>
            @endif
            @foreach (\App\Models\Complaint::presetPeriods() as $preset)
                @php
                    $presetActive = $period !== null
                        && $period['from'] === $preset['from']
                        && $period['to'] === $preset['to'];
                @endphp
                <a class="ghost{{ $presetActive ? ' current' : '' }}"
                    href="{{ route('admin.pengaduan.report', array_filter(['status' => request('status'), 'q' => request('q'), 'category' => request('category'), 'from' => $preset['from'], 'to' => $preset['to']])) }}"
                    @if ($presetActive) aria-current="true" @endif>
                    {{ __($preset['name']) }}
                </a>
            @endforeach
        </div>
        <div class="toolbar">
            <a class="ghost" href="{{ route('admin.pengaduan.index', array_filter(['status' => request('status'), 'q' => request('q'), 'category' => request('category'), 'from' => request('from'), 'to' => request('to')])) }}">Kembali</a>
            <a class="ghost" href="#" onclick="window.print()">Cetak</a>
            <a href="{{ route('admin.pengaduan.report-pdf', request()->query()) }}">Unduh PDF</a>
        </div>
    @endunless

    <div class="letterhead">
        <p class="brand">SIPRAS</p>
        <p>Sistem Informasi Pengaduan Sarana Sekolah</p>
        {{-- The h1 names this document, matching the <title>; the wordmark is not
             a heading, so the page no longer announces itself as "SIPRAS". --}}
        <h1 class="title">Rekapitulasi Pengaduan Sarana</h1>
    </div>

    @php
        // One sentence about the window this stack covers: the period label
        // when one is active, the status qualifier when one is active, and
        // "Semua waktu" for the whole history otherwise.
        // One sentence about the window this stack covers: the period label
        // when one is active, the status and category qualifiers when one is
        // active, and "Semua waktu" for the whole history otherwise. The
        // recap cannot print a window its filter did not select.
        $qualifiers = [
            request('status') ? ' — status '.\App\Models\Complaint::statusLabel((string) request('status')) : '',
            $category !== null ? ' — kategori '.$category : '',
        ];
        $periodText = match (true) {
            $period !== null || request('status') || $category !== null => ($period !== null ? $period['label'] : 'Semua waktu').implode('', $qualifiers),
            default => 'Semua waktu',
        };
    @endphp
    <div class="meta">
        <strong>Periode:</strong> {{ $periodText }}
        &nbsp;&middot;&nbsp;
        <strong>Dicetak pada:</strong> {{ now()->translatedFormat('d F Y H:i') }}
        &nbsp;&middot;&nbsp;
        <strong>Oleh:</strong> {{ auth()->user()->name }}
    </div>

    {{-- A scrollable box that is keyboard-focusable (WCAG 2.1.1) is an unnamed
         tab stop unless it is given a role and a name: `<div>` has none. The
         role is `region`, not `group`, so the name is announced as a landmark. --}}
    <div class="table-wrap" tabindex="0" role="region" aria-label="Ringkasan pengaduan per status">
        {{-- Named with `aria-label` rather than an `sr-only` <caption>: this page is
             the dompdf source too, and dompdf ignores `position: absolute`, so a
             visually-hidden caption would print as visible ink. An attribute cannot
             change the layout. --}}
        <table class="summary" aria-label="Tabel ringkasan pengaduan per status">
            <tr>
                <td><span class="num num-total">{{ $summary['total'] }}</span><span class="label">Total</span></td>
                {{-- The four status cells come from the one map, so the recap cannot
                     disagree with a chip or a card, and a fifth status cannot be
                     forgotten here. --}}
                @foreach (\App\Models\Complaint::STATUSES as $status)
                    <td><span class="num num-{{ $status }}">{{ $summary[$status] }}</span><span class="label">{{ \App\Models\Complaint::STATUS_LABELS[$status] }}</span></td>
                @endforeach
            </tr>
        </table>
    </div>

    @if ($complaints->isEmpty())
        <div class="note-empty">Tidak ada data pengaduan untuk ditampilkan.</div>
    @else
        <div class="table-wrap" tabindex="0" role="region" aria-label="Daftar rekapitulasi pengaduan">
            <table class="data" aria-label="Tabel rekapitulasi pengaduan">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Tiket</th>
                    <th>Tanggal</th>
                    <th>Pelapor</th>
                    <th>Kategori</th>
                    <th>Ruang</th>
                    <th>Judul Pengaduan</th>
                    <th class="center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($complaints as $complaint)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="ticket">{{ $complaint->ticket_number }}</td>
                        <td>{{ $complaint->created_at->translatedFormat('d M Y') }}</td>
                        <td>{{ $complaint->user->name }}</td>
                        <td>{{ $complaint->category->name }}</td>
                        <td>{{ $complaint->room->name }}</td>
                        <td>{{ $complaint->title }}</td>
                        <td class="center"><span class="st st-{{ $complaint->status }}">{{ \App\Models\Complaint::statusLabel($complaint->status) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    @endif

    <div class="foot">
        <div class="sign-wrap">
            <div>Mengetahui,<br>Admin Sekolah<br><br><span class="sig">{{ auth()->user()->name }}</span></div>
        </div>
    </div>
</main>
</body>
</html>