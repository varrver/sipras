#!/usr/bin/env python3
"""Redraw the stale SIPRAS lo-fi wireframes to the current structure.

Matches the style of the Oct-4 originals: 2560x1600 lo-fi greyscale
mockups, Liberation Sans, white cards on light grey, charcoal buttons.
The four structurally-correct originals (08, 10, 04, 13) are kept.

Output: docs/figma/01-wireframes/<name>.png (12 files total).
"""
from PIL import Image, ImageDraw, ImageFont
from pathlib import Path

OUT = Path("/opt/lampp/htdocs/sipras/docs/figma/01-wireframes")

W, H = 2560, 1600
BG = "#F2F3F5"
CARD = "#FFFFFF"
BORDER = "#E2E4E8"
INK = "#1E242C"
MUTED = "#8B929C"
DARK_BTN = "#3E444D"
PILL = "#E4E6E9"
PILL_INK = "#2A3038"
TABLE_HEAD = "#ECEEEF"
NAV_ACTIVE = "#E9EAEC"
HAIR = "#DDDFE3"
BANNER = "#E9EAEC"

FDIR = "/usr/share/fonts/liberation"
def font(sz, bold=False):
    return ImageFont.truetype(f"{FDIR}/LiberationSans-{'Bold' if bold else 'Regular'}.ttf", sz)


def card(d, xy, r=28, fill=CARD, outline=BORDER, w=3):
    d.rounded_rectangle(xy, radius=r, fill=fill, outline=outline, width=w)

def txt(d, xy, s, sz=28, bold=False, fill=INK, anchor=None):
    d.text(xy, s, font=font(sz, bold), fill=fill, anchor=anchor)

def pill(d, xy, s, sz=26, bold=True, fill=PILL, ink=PILL_INK, r=None):
    x0, y0, x1, y1 = xy
    rr = r if r is not None else (y1 - y0) // 2
    d.rounded_rectangle(xy, radius=rr, fill=fill)
    tw = d.textlength(s, font=font(sz, bold))
    d.text(((x0 + x1) / 2 - tw / 2, (y0 + y1) / 2 - sz * 0.62), s,
           font=font(sz, bold), fill=ink)

def btn_dark(d, xy, s, sz=28):
    x0, y0, x1, y1 = xy
    d.rounded_rectangle(xy, radius=14, fill=DARK_BTN)
    tw = d.textlength(s, font=font(sz, True))
    d.text(((x0 + x1) / 2 - tw / 2, (y0 + y1) / 2 - sz * 0.66), s,
           font=font(sz, True), fill="#FFFFFF")

def input_box(d, xy, placeholder, sz=27):
    card(d, xy, r=12)
    d.text((xy[0] + 26, (xy[1] + xy[3]) / 2 - sz * 0.62), placeholder,
           font=font(sz), fill=MUTED)

def capsule(d, xy, s, sz=26):
    """Capsule filter picker: grey capsule, label + chevron."""
    x0, y0, x1, y1 = xy
    d.rounded_rectangle(xy, radius=(y1 - y0) // 2, fill=CARD, outline=BORDER, width=3)
    tw = d.textlength(s, font=font(sz, True))
    cx = (x0 + x1) / 2 - (tw + 40) / 2
    d.text((cx, (y0 + y1) / 2 - sz * 0.62), s, font=font(sz, True), fill=INK)
    chx = cx + tw + 12
    chy = (y0 + y1) / 2
    d.polygon([(chx, chy - 5), (chx + 16, chy - 5), (chx + 8, chy + 4)], fill=INK)

def chevron(d, x, y, s=16, fill=MUTED):
    d.line([(x, y - s), (x + s, y), (x, y + s)], fill=fill, width=4)

def header(d, title):
    """Top-left page header block used by all originals."""
    txt(d, (64, 40), title, 44, True)
    txt(d, (64, 100), "SIPRAS · lo-fi wireframe", 28, fill=MUTED)

def sidebar(d, active):
    """Admin/teknisi left sidebar."""
    d.rectangle([0, 0, 480, H], fill="#F7F8F9")
    d.line([(480, 0), (480, H)], fill=HAIR, width=2)
    txt(d, (64, 100), "SIPRAS", 42, True)
    items = ["Dashboard", "Pengaduan", "Laporan", "Kategori", "Ruang", "Pengguna"]
    y = 240
    for it in items:
        if it == active:
            d.rounded_rectangle([28, y - 18, 452, y + 62], radius=16, fill=NAV_ACTIVE)
            txt(d, (68, y + 4), it, 32, True)
        else:
            txt(d, (68, y + 4), it, 32, fill="#5F666E")
        y += 124
    d.line([(42, H - 200), (438, H - 200)], fill=HAIR, width=2)
    txt(d, (64, H - 160), "Profil", 32, fill="#5F666E")

def topbar(d, title, x=610):
    d.rectangle([480, 0, W, 95], fill=CARD)
    d.line([(0, 95), (W, 95)], fill=HAIR, width=2)
    txt(d, (x, 28), title, 40, True)
    d.rectangle([480, 95, W, H], fill=BG)

def topbar_siswa(d, active):
    """Siswa horizontal topbar."""
    d.rectangle([0, 0, W, 130], fill=CARD)
    d.line([(0, 130), (W, 130)], fill=HAIR, width=2)
    txt(d, (64, 44), "SIPRAS", 42, True)
    x = 400
    for it in ["Dashboard", "Pengaduan Saya", "Buat Pengaduan"]:
        if it == active:
            txt(d, (x, 50), it, 32, True)
        else:
            txt(d, (x, 50), it, 32, fill="#7A828C")
        x += d.textlength(it, font=font(32, it == active)) + 190
    d.ellipse([W - 160, 38, W - 88, 110], fill="#E3E5E8")
    txt(d, (W - 66, 56), "B", 30, True, anchor=None)

def back_pill(d, x, y, label):
    """x-back-button: pill arrow + destination label, stacked."""
    d.rounded_rectangle([x, y, x + 64, y + 64], radius=32, fill=CARD,
                        outline=BORDER, width=3)
    d.line([(x + 40, y + 20), (x + 24, y + 32), (x + 40, y + 44)], fill=INK, width=5)
    d.text((x + 84, y + 14), label, font=font(28), fill=INK)

def info_row(d, x, y, label, value, lab_w=460):
    d.rounded_rectangle([x, y, x + lab_w, y + 74], radius=10, fill="#F5F6F7")
    d.text((x + 26, y + 20), label, font=font(26), fill=MUTED)
    d.text((x + lab_w + 40, y + 20), value, font=font(28), fill=INK)

def timeline(d, x, y, steps, w=960):
    """Vertical timeline: dots on a rail, label + time."""
    d.text((x, y - 66), "Linimasa", font=font(30, True), fill=INK)
    n = len(steps)
    gap = 108
    d.line([(x + 18, y + 14), (x + 18, y + gap * (n - 1) + 14)], fill=HAIR, width=4)
    for i, (label, time) in enumerate(steps):
        cy = y + gap * i + 14
        if i == 0:
            d.ellipse([x + 4, cy - 14, x + 32, cy + 14], fill=INK)
        else:
            d.ellipse([x + 4, cy - 14, x + 32, cy + 14], outline="#B9BEC5", width=3)
        d.text((x + 64, cy - 22), label, font=font(27, True), fill=INK)
        d.text((x + 64, cy + 12), time, font=font(24), fill=MUTED)

def img_ph(d, xy, label="Foto bukti"):
    card(d, xy, r=20, fill="#E3E5E8", outline="#D8DBDF")
    tw = d.textlength(label, font=font(26))
    d.text(((xy[0] + xy[2]) / 2 - tw / 2, (xy[1] + xy[3]) / 2 - 16), label,
           font=font(26), fill=MUTED)

def table(d, x, y, cols, widths, rows, pill_col=None, chev_col=None, rh=118):
    """Header row + zebra rows. rows = list of str-lists; pill_col renders a pill."""
    d.rounded_rectangle([x, y, x + sum(widths), y + rh + rh * len(rows)],
                        radius=20, fill=CARD, outline=BORDER, width=3)
    d.rounded_rectangle([x, y, x + sum(widths), y + rh], radius=20, fill=TABLE_HEAD)
    d.rectangle([x, y + rh - 20, x + sum(widths), y + rh], fill=TABLE_HEAD)
    cx = x + 40
    for c, wd in zip(cols, widths):
        d.text((cx, y + rh / 2 - 16), c, font=font(25, True), fill="#6A7078")
        cx += wd
    ry = y + rh
    for i, row in enumerate(rows):
        if i:
            d.line([(x, ry), (x + sum(widths), ry)], fill=HAIR, width=2)
        cx = x + 40
        for j, (cell, wd) in enumerate(zip(row, widths)):
            if j == pill_col and cell:
                pw = d.textlength(cell, font=font(25, True)) + 56
                pill(d, (cx, ry + rh / 2 - 32, cx + pw, ry + rh / 2 + 32), cell, 25)
            elif j == chev_col:
                chevron(d, cx + wd - 90, ry + rh / 2)
            else:
                d.text((cx, ry + rh / 2 - 16), cell, font=font(27),
                       fill=INK if j == 0 else "#3C424A")
            cx += wd
        ry += rh


# ---------------------------------------------------------------- landing ---
def draw_login():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Landing / Login")
    # topbar with crest
    d.rectangle([0, 190, W, 320], fill=CARD)
    d.line([(0, 320), (W, 320)], fill=HAIR, width=2)
    d.ellipse([64, 212, 148, 296], fill="#E3E5E8")
    txt(d, (172, 232), "SIPRAS", 40, True)
    # left intro column
    txt(d, (410, 420), "Laporkan kerusakan", 64, True)
    txt(d, (410, 500), "sarana sekolah,", 64, True)
    txt(d, (410, 580), "pantau sampai selesai.", 64, True, fill="#4A5560")
    for i, line in enumerate([
            "SIPRAS adalah sistem pengaduan sarana SMK Negeri 5",
            "Surakarta. Petugas membuka tiket, teknisi memproses di",
            "lapangan, dan status tiap laporan tampak sampai tuntas."]):
        txt(d, (410, 700 + i * 46), line, 28, fill="#565D66")
    d.line([(410, 880), (1340, 880)], fill=HAIR, width=2)
    feats = ["Unggah foto bukti langsung saat melapor",
             "Penanganan tepat oleh teknisi spesialis kategori",
             "Pantau status pengerjaan transparan hingga tuntas"]
    for i, f in enumerate(feats):
        cy = 950 + i * 90
        d.ellipse([410, cy, 466, cy + 56], fill="#E7E9EC")
        txt(d, (490, cy + 12), f, 28)
    # right login card
    card(d, (1460, 420, 2140, 1120))
    txt(d, (1510, 480), "Masuk", 44, True)
    txt(d, (1510, 548), "Gunakan akun SIPRAS yang telah", 26, fill=MUTED)
    txt(d, (1510, 584), "didafarkan admin.", 26, fill=MUTED)
    txt(d, (1510, 660), "Email", 26, True)
    input_box(d, (1510, 700, 2090, 770), "email@sekolah.sch.id")
    txt(d, (1510, 810), "Kata Sandi", 26, True)
    input_box(d, (1510, 850, 2090, 920), "••••••••")
    d.rounded_rectangle([1510, 960, 1546, 996], radius=8, outline="#B9BEC5", width=3)
    txt(d, (1562, 964), "Ingat saya", 26)
    btn_dark(d, (1510, 1030, 2090, 1090), "Masuk")
    # steps row
    txt(d, (W / 2, 1220), "Cara kerjanya", 40, True, anchor="ma")
    txt(d, (W / 2, 1278), "Alur penanganan pengaduan fasilitas yang terukur dan transparan.",
        26, fill=MUTED, anchor="ma")
    steps = [("1. Buka tiket", "Menunggu", "Siswa atau admin mencatat kerusakan", "lengkap dengan foto bukti dan lokasi."),
             ("2. Diproses", "Dalam Proses", "Teknisi spesialis mengambil", "pengaduan dan menanganinya di lapangan."),
             ("3. Selesai", "Selesai", "Status berganti Selesai bersama foto", "perbaikan yang bisa dipantau siswa.")]
    sx = 410
    for t, p, l1, l2 in steps:
        card(d, (sx, 1350, sx + 680, 1560))
        txt(d, (sx + 40, 1382), t, 30, True)
        pill(d, (sx + 480, 1382, sx + 640, 1426), p, 24)
        txt(d, (sx + 40, 1444), l1, 26, fill="#565D66")
        txt(d, (sx + 40, 1480), l2, 26, fill="#565D66")
        sx += 700
    im.save(OUT / "12-login.png")


# -------------------------------------------------------- admin dashboard ---
def draw_admin_dashboard():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Dashboard Admin")
    sidebar(d, "Dashboard")
    topbar(d, "Dashboard Admin")
    stats = [("Total Pengaduan", "12"), ("Menunggu", "4"), ("Dalam Proses", "3"),
             ("Selesai", "4"), ("Ditolak", "1")]
    x = 610
    for label, val in stats:
        card(d, (x, 250, x + 360, 470))
        txt(d, (x + 40, 290), label, 27, fill=MUTED)
        txt(d, (x + 40, 340), val, 56, True)
        x += 378
    card(d, (610, 540, 2496, 1180))
    txt(d, (650, 600), "Pengaduan Terbaru", 34, True)
    rows = [("TKT-20260909-0042", "Kursi patah di kelas 8B", "Menunggu"),
            ("TKT-20260908-0039", "Pipa bocor di toilet guru", "Dalam Proses"),
            ("TKT-20260907-0030", "Lampu mati di aula", "Ditolak")]
    ry = 700
    for i, (t, title, st) in enumerate(rows):
        if i:
            d.line([(610, ry - 14), (2496, ry - 14)], fill=HAIR, width=2)
        txt(d, (650, ry), t, 28, True)
        txt(d, (650, ry + 46), title, 27, fill="#3C424A")
        pw = d.textlength(st, font=font(25, True)) + 56
        pill(d, (2296 - pw, ry + 8, 2296, ry + 72), st, 25)
        chevron(d, 2426, ry + 40)
        ry += 150
    im.save(OUT / "01-admin_dashboard.png")


# ---------------------------------------------------------- siswa index ----
def draw_siswa_index():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Pengaduan Saya")
    topbar_siswa(d, "Pengaduan Saya")
    txt(d, (64, 210), "Pengaduan Saya", 44, True)
    btn_dark(d, (2180, 196, 2496, 266), "Buat Pengaduan")
    # filter row: search + capsules
    input_box(d, (64, 330, 900, 404), "Cari keluhan…", 26)
    capsule(d, (940, 330, 1240, 404), "Kategori")
    capsule(d, (1280, 330, 1580, 404), "Periode")
    capsule(d, (1620, 330, 1920, 404), "Status")
    txt(d, (64, 450), "Filter aktif menempel pada tautan (URL), buka menu kapsul untuk mengganti.",
        24, fill=MUTED)
    # table
    cols = ["Status", "Tipe kerusakan", "Ruang", "Keluhan", ""]
    wds = [300, 560, 460, 780, 80]
    rows = [
        ("Menunggu", "Monitor tidak menyala", "Lab. RPL 1", "Dua monitor di ruang lab tidak menyala sejak pagi."),
        ("Dalam Proses", "AC tidak dingin", "Ruang 1.01", "AC ruang kelas mati total, ruangan panas."),
        ("Selesai", "Meja patah", "Ruang Perpustakaan", "Kaki meja baca patah, tidak stabil dipakai."),
        ("Menunggu", "Kran bocor", "Toilet Siswa", "Kran wastafel menetes terus sejak siang."),
    ]
    table(d, 64, 540, cols, wds, rows, pill_col=0, chev_col=4, rh=140)
    im.save(OUT / "09-siswa_pengaduan_index.png")


# ---------------------------------------------------------- admin index ----
def draw_admin_index():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Manajemen Pengaduan")
    sidebar(d, "Pengaduan")
    topbar(d, "Pengaduan")
    # filter row
    input_box(d, (610, 250, 1450, 324), "Cari judul, nomor tiket, atau deskripsi…", 26)
    btn_dark(d, (1490, 250, 1680, 324), "Cari")
    capsule(d, (610, 370, 910, 444), "Kategori")
    capsule(d, (950, 370, 1250, 444), "Periode")
    capsule(d, (1290, 370, 1590, 444), "Status")
    txt(d, (610, 490), "Filter aktif menempel pada tautan (URL); nomor penghitung berdiri sendiri.",
        24, fill=MUTED)
    cols = ["Tiket", "Judul", "Pelapor", "Ruang", "Status", "Diajukan", ""]
    wds = [420, 480, 330, 360, 320, 300, 80]
    rows = [
        ("TKT-20260909-0042", "Kursi patah di kelas 8B", "Budi Santoso", "Kelas 8B", "Menunggu", "09 Sep 2026"),
        ("TKT-20260908-0039", "Pipa bocor toilet guru", "Siti Aminah", "Toilet Guru", "Dalam Proses", "08 Sep 2026"),
        ("TKT-20260908-0035", "AC tidak dingin", "Andi Wijaya", "Lab Fisika", "Selesai", "08 Sep 2026"),
        ("TKT-20260907-0030", "Lampu mati di aula", "Budi Santoso", "Aula", "Ditolak", "07 Sep 2026"),
    ]
    table(d, 610, 570, cols, wds, rows, pill_col=4, chev_col=6, rh=130)
    im.save(OUT / "02-admin_pengaduan_index.png")


# ---------------------------------------------------------- admin detail ---
def draw_admin_detail():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Detail Pengaduan")
    sidebar(d, "Pengaduan")
    topbar(d, "Detail Pengaduan")
    back_pill(d, 610, 190, "Kembali ke daftar")
    # record card
    card(d, (610, 290, 2496, 1360))
    txt(d, (660, 350), "TKT-20260909-0042", 30, True)
    pill(d, (2296, 340, 2446, 404), "Menunggu", 26)
    txt(d, (660, 440), "Kursi patah di kelas 8B", 44, True)
    for i, (l, v) in enumerate([("Pelapor", "Budi Santoso"), ("Kategori", "Perabot"),
                                ("Ruang", "Kelas 8B"), ("Diajukan", "09 Sep 2026, 07:40")]):
        info_row(d, 660, 540 + i * 96, l, v)
    timeline(d, 660, 990, [("Menunggu", "09 Sep 2026 07:40 · diajukan siswa"),
                           ("Dalam Proses", "—"),
                           ("Selesai", "—")], w=1000)
    txt(d, (660, 1250), "Deskripsi", 26, fill=MUTED)
    txt(d, (900, 1250), "Kursi di pojok kelas patah di sandaran, bahaya dipakai siswa.", 28)
    txt(d, (660, 1250), "Deskripsi", 26, fill=MUTED)
    txt(d, (900, 1250), "Kursi di pojok kelas patah di sandaran, bahaya dipakai siswa.", 28)
    # action card below
    card(d, (610, 1400, 2496, 1560))
    txt(d, (660, 1440), "Perbarui Status", 32, True)
    capsule(d, (1000, 1436, 1300, 1510), "Status")
    input_box(d, (1340, 1436, 2100, 1510), "Catatan (wajib bila status Ditolak)", 24)
    btn_dark(d, (2140, 1436, 2446, 1510), "Perbarui Status")
    im.save(OUT / "03-admin_pengaduan_detail.png")


# ---------------------------------------------------------- siswa detail ---
def draw_siswa_detail():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Detail Pengaduan")
    topbar_siswa(d, "Pengaduan Saya")
    txt(d, (64, 210), "Detail Pengaduan", 44, True)
    back_pill(d, 64, 300, "Kembali ke pengaduan saya")
    card(d, (64, 420, 1500, 1560))
    txt(d, (114, 480), "TKT-20260909-0001", 30, True)
    pill(d, (1230, 470, 1440, 534), "Menunggu", 26)
    txt(d, (114, 570), "Monitor tidak menyala", 44, True)
    for i, (l, v) in enumerate([("Kategori", "Elektronik"), ("Ruang", "Lab Komputer 1"),
                                ("Diajukan pada", "09 Sep 2026, 08:15"), ("Status", "Menunggu")]):
        info_row(d, 114, 670 + i * 96, l, v, lab_w=520)
    # timeline (the caption promises it)
    txt(d, (114, 1080), "Linimasa", 30, True)
    steps = [("Menunggu", "09 Sep 2026 08:15 · diajukan"),
             ("Dalam Proses", "—"),
             ("Selesai", "—")]
    d.line([(132, 1160), (132, 1326)], fill=HAIR, width=4)
    for i, (lab, t) in enumerate(steps):
        cy = 1160 + i * 84
        if i == 0:
            d.ellipse([118, cy - 12, 146, cy + 12], fill=INK)
        else:
            d.ellipse([118, cy - 12, 146, cy + 12], outline="#B9BEC5", width=3)
        txt(d, (180, cy - 26), lab, 26, True)
        txt(d, (180, cy + 6), t, 23, fill=MUTED)
    txt(d, (700, 1080), "Deskripsi", 26, fill=MUTED)
    txt(d, (700, 1120), "Dua monitor di ruang komputer tidak menyala sejak", 28)
    txt(d, (700, 1160), "tadi pagi, sehingga peserta didik tidak bisa praktik.", 28)
    img_ph(d, (700, 1240, 1060, 1440))
    txt(d, (700, 1450), "Status", 26, fill=MUTED)
    d.rounded_rectangle((700, 1490, 1440, 1545), radius=12, fill=BANNER)
    txt(d, (724, 1504), "•  Menunggu tinjauan Tim Sarana Sekolah…", 25)
    im.save(OUT / "11-siswa_pengaduan_detail.png")


# ------------------------------------------------------- master pengguna ---
def draw_users():
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)
    header(d, "Master Data · Pengguna")
    sidebar(d, "Pengguna")
    topbar(d, "Master Data · Pengguna")
    capsule(d, (1680, 196, 1930, 266), "Export CSV", 25)
    capsule(d, (1950, 196, 2200, 266), "Import CSV", 25)
    btn_dark(d, (2230, 196, 2496, 266), "Tambah Pengguna")
    cols = ["#", "Nama", "Email", "Peran", "Pengaduan", "Aksi"]
    wds = [120, 420, 480, 260, 260, 440]
    rows = [
        ("1", "Admin SIPRAS", "admin@sipras.test", "Admin", "0", "Edit · Reset · Hapus"),
        ("2", "Budi Santoso", "budi@siswa.test", "Siswa", "3", "Edit · Reset · Hapus"),
        ("3", "Teknisi Elektronik", "elektronik@teknisi.sipras", "Teknisi", "2", "Edit · Reset · Hapus"),
        ("4", "Siti Aminah", "siti@siswa.test", "Siswa", "1", "Edit · Reset · Hapus"),
    ]
    table(d, 610, 330, cols, wds, rows, rh=120)
    txt(d, (610, 960), "Impor CSV bersifat all-or-nothing: satu baris gagal, seluruh berkas ditolak",
        26, fill=MUTED)
    txt(d, (610, 1000), "dengan laporan kesalahan bernomor baris.", 26, fill=MUTED)
    im.save(OUT / "07-master_pengguna.png")


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    draw_login()
    draw_admin_dashboard()
    draw_siswa_index()
    draw_admin_index()
    draw_admin_detail()
    draw_siswa_detail()
    draw_users()
    print("done:", ", ".join(sorted(p.name for p in OUT.glob("*.png"))))


if __name__ == "__main__":
    main()
