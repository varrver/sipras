<?php

/**
 * Room inventory for SMK Negeri 5 Surakarta, distilled from the school's
 * floor plan — the full labeled room list a student can pick when filing a
 * complaint.
 *
 * Conventions:
 * - Plain classrooms keep the plan's room number as both name and code
 *   (`Ruang 2.07` / `R-2.07`).
 * - Where the plan clearly pairs a number with a named room, the name
 *   carries the function and the code carries the number (the four
 *   `Lab. ASM` rooms sit at R.1.13–1.16; the Bengkels SKT/PSA/SKE/Otomasi
 *   sit at Rg 2.13–2.17; `T. ASM` rooms at R.1.16 and R.2.19).
 * - Names stay verbatim to the plan otherwise; codes are unique uppercase
 *   slugs invented by the import.
 * - Plan labels that are annotations or brands, not rooms ("Lantai atas",
 *   "AXIOO", "Doorlop") and the bare ambiguous "WKS"/"WMM" flyers are
 *   skipped; the two restroom labels collapse into a single `Toilet`.
 */
return [
    // Gedung utama, lantai 1 — kelas bernomor (dari denah `Rg 1.xx`)
    ['name' => 'Ruang 1.01', 'code' => 'R-1.01'],
    ['name' => 'Ruang 1.02', 'code' => 'R-1.02'],
    ['name' => 'Ruang 1.03', 'code' => 'R-1.03'],
    ['name' => 'Ruang 1.04', 'code' => 'R-1.04'],
    ['name' => 'Ruang 1.05', 'code' => 'R-1.05'],
    ['name' => 'Ruang 1.06', 'code' => 'R-1.06'],
    ['name' => 'Ruang 1.07', 'code' => 'R-1.07'],
    ['name' => 'Ruang 1.08', 'code' => 'R-1.08'],
    ['name' => 'Ruang 1.09', 'code' => 'R-1.09'],
    ['name' => 'Ruang 1.10', 'code' => 'R-1.10'],
    ['name' => 'Ruang 1.11', 'code' => 'R-1.11'],
    ['name' => 'Ruang 1.12', 'code' => 'R-1.12'],
    ['name' => 'Ruang 1.18', 'code' => 'R-1.18'],
    ['name' => 'Ruang 1.19', 'code' => 'R-1.19'],
    ['name' => 'Ruang 1.20', 'code' => 'R-1.20'],
    ['name' => 'Ruang 1.21', 'code' => 'R-1.21'],
    // Blok ASM (denah `R.1.13`–`R.1.16`)
    ['name' => 'Lab. ASM 1', 'code' => 'R-1.13'],
    ['name' => 'Lab. ASM 2', 'code' => 'R-1.14'],
    ['name' => 'Lab. ASM 3', 'code' => 'R-1.15'],
    ['name' => 'T. ASM 1', 'code' => 'R-1.16'],

    // Gedung utama, lantai 2 — kelas bernomor (denah `Rg 2.xx`)
    ['name' => 'Ruang 2.01', 'code' => 'R-2.01'],
    ['name' => 'Ruang 2.02', 'code' => 'R-2.02'],
    ['name' => 'Ruang 2.03', 'code' => 'R-2.03'],
    ['name' => 'Ruang 2.04', 'code' => 'R-2.04'],
    ['name' => 'Ruang 2.05', 'code' => 'R-2.05'],
    ['name' => 'Ruang 2.06', 'code' => 'R-2.06'],
    ['name' => 'Ruang 2.07', 'code' => 'R-2.07'],
    ['name' => 'Ruang 2.08', 'code' => 'R-2.08'],
    ['name' => 'Ruang 2.09', 'code' => 'R-2.09'],
    ['name' => 'Ruang 2.10', 'code' => 'R-2.10'],
    ['name' => 'Ruang 2.11', 'code' => 'R-2.11'],
    ['name' => 'Ruang 2.12', 'code' => 'R-2.12'],
    ['name' => 'Ruang 2.18', 'code' => 'R-2.18'],
    ['name' => 'Ruang 2.20', 'code' => 'R-2.20'],
    ['name' => 'Ruang 2.21', 'code' => 'R-2.21'],
    ['name' => 'Ruang 2.22', 'code' => 'R-2.22'],
    ['name' => 'Ruang 2.23', 'code' => 'R-2.23'],
    ['name' => 'Ruang 2.24', 'code' => 'R-2.24'],
    ['name' => 'Ruang 2.25', 'code' => 'R-2.25'],
    // Bengkel yang menempati Rg 2.13–2.17 (denah)
    ['name' => 'Bengkel SKT', 'code' => 'R-2.13'],
    ['name' => 'Bengkel PSA', 'code' => 'R-2.14'],
    ['name' => 'Bengkel SKE', 'code' => 'R-2.15'],
    ['name' => 'Bengkel Otomasi 1', 'code' => 'R-2.16'],
    ['name' => 'Bengkel Otomasi 2', 'code' => 'R-2.17'],
    ['name' => 'T. ASM 2', 'code' => 'R-2.19'],

    // Laboratorium dan ruang penunjang DPIB
    ['name' => 'Lab. Fisika', 'code' => 'LPF'],
    ['name' => 'Lab. Komputer DPIB Lt.2', 'code' => 'LKPD-2'],
    ['name' => 'R. Gambar DPIB Lt.1', 'code' => 'RGBD-1'],

    // Laboratorium gedung selatan
    ['name' => 'Lab. RPL 1', 'code' => 'LRP-1'],
    ['name' => 'Lab. RPL 2', 'code' => 'LRP-2'],
    ['name' => 'Lab. RPL 3', 'code' => 'LRP-3'],
    ['name' => 'Lab. RPL 4', 'code' => 'LRP-4'],
    ['name' => 'Lab. Bahasa Inggris 1', 'code' => 'LBI-1'],
    ['name' => 'Lab. Bahasa Inggris 2', 'code' => 'LBI-2'],
    ['name' => 'Lab. Bahasa Inggris 3', 'code' => 'LBI-3'],
    ['name' => 'Lab. Multimedia', 'code' => 'L-MM'],
    ['name' => 'Lab. KKP 1', 'code' => 'LKKP-1'],
    ['name' => 'Lab. KKPI 2', 'code' => 'LKKPI-2'],
    ['name' => 'R. Serbaguna/Indoor', 'code' => 'RSI'],

    // Ruang Praktik Siswa (RPS)
    ['name' => 'RPS TKP', 'code' => 'RPS-TKP'],
    ['name' => 'RPS TKR', 'code' => 'RPS-TKR'],
    ['name' => 'RPS TSM 1', 'code' => 'RPS-TSM-1'],
    ['name' => 'RPS TSM 2', 'code' => 'RPS-TSM-2'],
    ['name' => 'RPS TSM 3', 'code' => 'RPS-TSM-3'],
    ['name' => 'RPS Teknik Otomotif', 'code' => 'RPS-TKM'],
    ['name' => 'RPS Teknik Pemesinan', 'code' => 'RPS-TPM'],
    ['name' => 'RPS Elektronika', 'code' => 'RPS-ELK'],

    // Bengkel (Bkl) — di luar gedung kelas
    ['name' => 'Bengkel Listrik 2', 'code' => 'BL-2'],
    ['name' => 'Bengkel Listrik 3 (IML)', 'code' => 'BL-3'],
    ['name' => 'Bengkel Listrik 4', 'code' => 'BL-4'],
    ['name' => 'Bengkel Listrik 5', 'code' => 'BL-5'],
    ['name' => 'Bengkel Listrik 6 (ITL)', 'code' => 'BL-6'],
    ['name' => 'Bengkel DLE', 'code' => 'B-DLE'],
    ['name' => 'Bengkel Elektro 1', 'code' => 'BE-1'],
    ['name' => 'Bengkel Elektro 2', 'code' => 'BE-2'],
    ['name' => 'Bengkel Elektro 3', 'code' => 'BE-3'],
    ['name' => 'Bengkel Elektro 4', 'code' => 'BE-4'],
    ['name' => 'Bengkel SKEE', 'code' => 'B-SKEE'],

    // Kantor, layanan, dan fasilitas umum
    ['name' => 'Ruang Guru', 'code' => 'R-GRU'],
    ['name' => 'Ruang Guru Listrik', 'code' => 'R-GLS'],
    ['name' => 'Ruang Guru NK', 'code' => 'R-GNK'],
    ['name' => 'R. Guru PPLG', 'code' => 'R-GPP'],
    ['name' => 'R. BP/BK', 'code' => 'R-BPBK'],
    ['name' => 'Ruang Waka 2', 'code' => 'R-WK2'],
    ['name' => 'R. TEFA', 'code' => 'R-TEFA'],
    ['name' => 'R. Dapodik', 'code' => 'R-DPO'],
    ['name' => 'R. Seni Rupa', 'code' => 'R-SRP'],
    ['name' => 'R. Meeting', 'code' => 'R-MTG'],
    ['name' => 'R. Lobby', 'code' => 'R-LBB'],
    ['name' => 'Ruang KS', 'code' => 'R-KS'],
    ['name' => 'Ruang TU', 'code' => 'R-TU'],
    ['name' => 'R. Satpam', 'code' => 'R-SAT'],
    ['name' => 'R. OSIS', 'code' => 'R-OSIS'],
    ['name' => 'UKS', 'code' => 'UKS'],
    ['name' => 'Ruang Perpustakaan', 'code' => 'PRP'],
    ['name' => 'Ruang Aula', 'code' => 'AULA'],
    ['name' => 'Masjid', 'code' => 'MSJ'],
    ['name' => 'Gudang', 'code' => 'GDG'],
    ['name' => 'Toilet', 'code' => 'WCL'],
    ['name' => 'WKS 1', 'code' => 'WKS-1'],
    ['name' => 'WKS 3', 'code' => 'WKS-3'],
    ['name' => 'WKS 4', 'code' => 'WKS-4'],
    ['name' => 'WKS SDM', 'code' => 'WKS-SDM'],
    ['name' => 'LSP P1', 'code' => 'LSP-P1'],
    ['name' => 'Showroom', 'code' => 'SHR'],
];
