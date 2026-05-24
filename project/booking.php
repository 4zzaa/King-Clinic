<?php
session_start();
include 'koneksi.php';

$isLoggedIn = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'pasien';
$userId     = $isLoggedIn ? (int)$_SESSION['user']['id_pengguna'] : 0;
$username   = $isLoggedIn ? htmlspecialchars($_SESSION['user']['username']) : '';

$success = '';
$error   = '';

// ── Auto-insert layanan yang mungkin belum ada di DB ───────────────────────
$defaultServices = [
    ['Konsultasi Gigi', 150000],
    ['Root Canal',      2000000],
];
foreach ($defaultServices as [$sNama, $sHarga]) {
    $chkSvc = $koneksi->prepare("SELECT id_layanan FROM layanan WHERE nama_layanan=? LIMIT 1");
    $chkSvc->bind_param('s', $sNama);
    $chkSvc->execute();
    if (!$chkSvc->get_result()->fetch_assoc()) {
        $insSvc = $koneksi->prepare("INSERT INTO layanan (nama_layanan, harga) VALUES (?, ?)");
        $insSvc->bind_param('sd', $sNama, $sHarga);
        $insSvc->execute();
        $insSvc->close();
    }
    $chkSvc->close();
}

// ── Definisi layanan (8 item) ───────────────────────────────────────────────
//    dbSearch : array kata kunci untuk cari di DB (LIKE %keyword%)
//    fallback : harga tampil di kartu jika tidak ada di DB
$serviceData = [
    'Konsultasi Gigi' => [
        'desc'     => 'Pemeriksaan menyeluruh kondisi gigi dan mulut untuk diagnosa yang tepat.',
        'dbSearch' => ['Konsultasi'],
        'fallback' => null,
        'doctors'  => ['Dr. Tirta', 'Drg. Khansa', 'Drg. Yudha Mahendra'],
    ],
    'Scaling & Polishing' => [
        'desc'     => 'Membersihkan karang dan plak gigi agar lebih bersih dan gusi tetap sehat.',
        'dbSearch' => ['Karang', 'Scaling'],
        'fallback' => null,
        'doctors'  => ['Dr. Tirta', 'Drg. Khansa'],
    ],
    'Tambal Gigi' => [
        'desc'     => 'Memperbaiki gigi berlubang menggunakan bahan tambal berkualitas tinggi.',
        'dbSearch' => ['Penambalan', 'Tambal', 'ambal'],
        'fallback' => 500000,
        'doctors'  => ['Dr. Tirta', 'Drg. Jocelin Sintano'],
    ],
    'Cabut Gigi' => [
        'desc'     => 'Pencabutan gigi bermasalah yang aman dan minim rasa sakit.',
        'dbSearch' => ['Cabut'],
        'fallback' => null,
        'doctors'  => ['Drg. Khansa', 'Drg. Adelia Susanto'],
    ],
    'Pemasangan Behel' => [
        'desc'     => 'Kawat gigi untuk meratakan posisi gigi demi estetika dan fungsi gigitan optimal.',
        'dbSearch' => ['Behel'],
        'fallback' => null,
        'doctors'  => ['Drg. Sayora'],
    ],
    'Root Canal' => [
        'desc'     => 'Perawatan saluran akar untuk menyelamatkan gigi yang terinfeksi parah.',
        'dbSearch' => ['Saluran', 'Canal', 'Akar', 'Root'],
        'fallback' => 2000000,
        'doctors'  => ['Drg. Chacha', 'Drg. Jocelin Sintano'],
    ],
    'Whitening' => [
        'desc'     => 'Pemutihan gigi profesional untuk senyum lebih cerah dan percaya diri.',
        'dbSearch' => ['Pemutihan', 'Whitening', 'Putih'],
        'fallback' => 500000,
        'doctors'  => ['Drg. Jocelin Sintano', 'Drg. Devya Linda'],
    ],
    'Gigi Tiruan' => [
        'desc'     => 'Gigi palsu custom menggantikan gigi yang hilang dan mengembalikan fungsi kunyah.',
        'dbSearch' => ['Tiruan'],
        'fallback' => null,
        'doctors'  => ['Drg. Devya Linda', 'Drg. Yudha Mahendra'],
    ],
];

function dokterSearchKey(string $nama): string {
    $stripped = preg_replace('/^(drg\.|dr\.)\s*/i', '', trim($nama));
    $parts    = explode(' ', trim($stripped));
    return $parts[0] ?? $stripped;
}

// ── Handle POST booking ─────────────────────────────────────────────────────
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reservasi') {
    $layananId = (int)($_POST['layanan_id'] ?? 0);
    $tanggal   = $_POST['tanggal'] ?? '';

    if (empty($tanggal)) {
        $error = 'Pilih tanggal terlebih dahulu.';
    } elseif ($layananId <= 0) {
        $error = 'Layanan yang dipilih belum tersedia untuk booking online. Silakan hubungi klinik kami.';
    } elseif ($tanggal < date('Y-m-d')) {
        $error = 'Tanggal reservasi tidak boleh di masa lalu.';
    } else {
        $stmt = $koneksi->prepare("SELECT nama_layanan, harga FROM layanan WHERE id_layanan = ?");
        $stmt->bind_param('i', $layananId);
        $stmt->execute();
        $layanan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$layanan) {
            $error = 'Layanan tidak ditemukan di database.';
        } else {
            $namaLayanan = $layanan['nama_layanan'];

            // ── Cek: user tidak boleh booking layanan+tanggal yang sama 2x ──
            $dupChk = $koneksi->prepare(
                "SELECT COUNT(*) AS c FROM transaksi_reservasi WHERE user_id=? AND nama_layanan=? AND tanggal=?"
            );
            $dupChk->bind_param('iss', $userId, $namaLayanan, $tanggal);
            $dupChk->execute();
            $dupCount = (int)$dupChk->get_result()->fetch_assoc()['c'];
            $dupChk->close();

            if ($dupCount > 0) {
                $error = 'Anda sudah memiliki reservasi untuk layanan ini pada tanggal tersebut.';
            } else {
                // ── Cari dokter pertama yang tersedia untuk layanan ini ──────
                $dokterNames = [];
                foreach ($serviceData as $uiName => $meta) {
                    foreach ($meta['dbSearch'] as $kw) {
                        if (stripos($namaLayanan, $kw) !== false) {
                            $dokterNames = $meta['doctors'];
                            break 2;
                        }
                    }
                    if (strtolower(trim($namaLayanan)) === strtolower(trim($uiName))) {
                        $dokterNames = $meta['doctors'];
                        break;
                    }
                }

                // Ambil dokter pertama dari list (tidak perlu cek konflik tanggal)
                $dokterId   = null;
                $dokterNama = '';
                foreach ($dokterNames as $dNama) {
                    $key  = dokterSearchKey($dNama);
                    $like = '%' . $key . '%';
                    $dS   = $koneksi->prepare("SELECT id_dokter, nama_dokter FROM dokter WHERE nama_dokter LIKE ? LIMIT 1");
                    $dS->bind_param('s', $like);
                    $dS->execute();
                    $dR = $dS->get_result()->fetch_assoc();
                    $dS->close();
                    if ($dR) { $dokterId = (int)$dR['id_dokter']; $dokterNama = $dR['nama_dokter']; break; }
                }

                if (!$dokterId) {
                    $error = 'Tidak ada dokter tersedia untuk layanan ini. Silakan hubungi klinik.';
                } else {
                    $ins = $koneksi->prepare(
                        "INSERT INTO transaksi_reservasi (user_id, dokter_id, nama_layanan, harga_layanan, tanggal) VALUES (?, ?, ?, ?, ?)"
                    );
                    $ins->bind_param('iisds', $userId, $dokterId, $layanan['nama_layanan'], $layanan['harga'], $tanggal);
                    if ($ins->execute()) {
                        $success = 'Reservasi berhasil! Silakan datang pada ' . date('d M Y', strtotime($tanggal)) . '.';
                    } else {
                        $error = 'Gagal membuat reservasi. Silakan coba lagi.';
                    }
                    $ins->close();
                }
            }
        }
    }
}

// ── Ambil semua layanan dari DB ─────────────────────────────────────────────
$layananAll = [];
$res = mysqli_query($koneksi, "SELECT id_layanan, nama_layanan, harga FROM layanan");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $layananAll[] = $r; }

// ── Data layanan sudah didefinisikan di awal file ───────────────────────────

// ── Bangun data tampilan kartu (merge dengan harga DB) ──────────────────────
$displayServices = [];
foreach ($serviceData as $nama => $meta) {
    $dbRow = null;
    // Cari di DB pakai keyword
    foreach ($meta['dbSearch'] as $kw) {
        foreach ($layananAll as $l) {
            if (stripos($l['nama_layanan'], $kw) !== false) {
                $dbRow = $l; break 2;
            }
        }
    }
    // Fallback: cocokkan nama persis
    if (!$dbRow) {
        foreach ($layananAll as $l) {
            if (strtolower(trim($l['nama_layanan'])) === strtolower(trim($nama))) {
                $dbRow = $l; break;
            }
        }
    }

    if ($dbRow) {
        $hargaStr  = 'Rp ' . number_format((float)$dbRow['harga'], 0, ',', '.');
        $layananId = (int)$dbRow['id_layanan'];
    } elseif ($meta['fallback'] !== null) {
        $hargaStr  = 'Rp ' . number_format($meta['fallback'], 0, ',', '.');
        $layananId = 0; // tidak ada di DB, tidak bisa booking
    } else {
        $hargaStr  = null;
        $layananId = 0;
    }

    $displayServices[] = [
        'id'      => $layananId,
        'nama'    => $nama,
        'desc'    => $meta['desc'],
        'doctors' => $meta['doctors'],
        'harga'   => $hargaStr,
    ];
}

// ── Slot waktu (per jam, 09:00–17:00) ──────────────────────────────────────
$timeSlots = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Reservasi — King Clinic</title>
    <meta name="description" content="Pesan layanan perawatan gigi terbaik di King Clinic secara online.">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #005d90;
            --blue:      #3b82f6;
            --blue-dk:   #2563eb;
            --text:      #0f172a;
            --muted:     #64748b;
            --border:    #e2e8f0;
            --bg:        #f0f4f8;
            --card:      #ffffff;
            --radius:    14px;
            --radius-sm: 8px;
            --shadow:    0 2px 16px rgba(0,75,144,.07);
            --shadow-lg: 0 8px 32px rgba(0,75,144,.14);
            --fn-head:   'Manrope', sans-serif;
            --fn-body:   'Inter',   sans-serif;
            --tr:        .2s ease;
        }

        html { scroll-behavior: smooth; }
        body { font-family: var(--fn-body); background: var(--bg); color: var(--text); min-height: 100vh; }

        /* ── Topbar ─────────────────────────────────────── */
        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 1px 12px rgba(0,75,144,.06);
        }
        .topbar-brand {
            font-family: var(--fn-head);
            font-size: 1.25rem; font-weight: 800;
            color: var(--primary); text-decoration: none; letter-spacing: -.5px;
        }
        .topbar-nav { display: flex; align-items: center; gap: 8px; }
        .topbar-nav a {
            font-family: var(--fn-head); font-size: .875rem; font-weight: 600;
            color: var(--muted); text-decoration: none;
            padding: 7px 14px; border-radius: var(--radius-sm); transition: var(--tr);
        }
        .topbar-nav a:hover { color: var(--primary); background: #f0f7ff; }
        .btn-nav {
            background: linear-gradient(135deg,#005d90,#0077b6) !important;
            color: #fff !important; border-radius: 999px !important; padding: 8px 20px !important;
        }
        .btn-nav:hover { box-shadow: 0 4px 16px rgba(0,93,144,.35) !important; transform: translateY(-1px); }
        .user-chip {
            display:flex; align-items:center; gap:7px;
            font-family: var(--fn-head); font-size:.875rem; font-weight:700;
            color:var(--primary); background:#eff6ff; border-radius:999px; padding:6px 16px;
        }

        /* ── Page wrap ──────────────────────────────────── */
        .page-wrap { max-width: 1260px; margin: 0 auto; padding: 34px 28px 60px; }

        .page-header { margin-bottom: 26px; }
        .page-header h1 {
            font-family: var(--fn-head);
            font-size: clamp(1.75rem,3.5vw,2.4rem);
            font-weight: 800; letter-spacing:-.03em; line-height:1.15;
        }
        .page-header h1 span { color: var(--blue); }
        .page-header p { color:var(--muted); margin-top:7px; font-size:.93rem; line-height:1.7; max-width:500px; }
        .page-header p a { color:var(--blue); font-weight:600; text-decoration:none; }
        .page-header p a:hover { text-decoration:underline; }

        /* ── Alerts ─────────────────────────────────────── */
        .alert {
            padding:12px 16px; border-radius:var(--radius-sm);
            font-size:.875rem; font-weight:500; margin-bottom:20px;
            display:flex; align-items:flex-start; gap:10px; line-height:1.55;
        }
        .alert svg { flex-shrink:0; margin-top:1px; }
        .alert-success { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .alert-error   { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }

        /* ── Layout ─────────────────────────────────────── */
        .layout { display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start; }

        /* ── Service Cards ──────────────────────────────── */
        .cards-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }

        .service-card {
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 16px;
            cursor: pointer;
            transition: border-color var(--tr), box-shadow var(--tr), transform var(--tr), background var(--tr);
            user-select: none;
            position: relative;
            box-shadow: var(--shadow);
            outline: none;
        }
        .service-card:hover {
            border-color: var(--blue);
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }
        .service-card:focus-visible { outline: 2px solid var(--blue); outline-offset: 2px; }

        /* Selected state */
        .service-card.selected {
            border-color: var(--blue);
            background: #f0f7ff;
            box-shadow: 0 0 0 3px rgba(59,130,246,.2), var(--shadow-lg);
            transform: translateY(-3px);
        }


        .card-price {
            display: inline-block;
            font-family: var(--fn-head);
            font-size: .76rem; font-weight: 700;
            color: var(--blue-dk); background: #eff6ff;
            border-radius: 999px; padding: 2px 11px; margin-bottom: 8px;
        }
        .card-name {
            font-family: var(--fn-head);
            font-size: .95rem; font-weight: 700;
            color: var(--text); margin-bottom: 4px; line-height: 1.3;
        }
        .card-desc { font-size: .77rem; color: var(--muted); line-height: 1.55; margin-bottom: 10px; }
        .card-doctors {
            display: flex; flex-wrap: wrap; gap: 5px;
            padding-top: 8px; border-top: 1px solid var(--border);
        }
        .doctor-tag {
            font-size: .68rem; font-weight: 600;
            color: #374151; background: #f3f4f6;
            border-radius: 999px; padding: 2px 9px; white-space: nowrap;
        }

        /* ── Schedule Panel ─────────────────────────────── */
        .schedule-panel {
            background: var(--card);
            border-radius: var(--radius);
            padding: 22px 18px;
            box-shadow: var(--shadow);
            position: sticky; top: 80px;
        }
        .panel-title {
            font-family: var(--fn-head);
            font-size: 1rem; font-weight: 800;
            color: var(--text); margin-bottom: 16px; letter-spacing: -.02em;
        }
        .section-label {
            font-family: var(--fn-head);
            font-size: .62rem; font-weight: 700;
            color: var(--muted); letter-spacing: .1em;
            text-transform: uppercase; margin-bottom: 8px;
        }

        /* Date input */
        .date-input {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: #f8fafc;
            font-family: var(--fn-body);
            font-size: .875rem;
            color: var(--text);
            cursor: pointer;
            margin-bottom: 16px;
            transition: border-color var(--tr), box-shadow var(--tr);
            appearance: none;
            -webkit-appearance: none;
        }
        .date-input:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 3px rgba(59,130,246,.12); }

        /* Time slots */
        .times-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
            margin-bottom: 16px;
        }
        .time-btn {
            padding: 8px 4px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: #f8fafc;
            font-family: var(--fn-head);
            font-size: .75rem; font-weight: 600;
            color: var(--muted);
            cursor: pointer; text-align: center;
            transition: var(--tr);
        }
        .time-btn:hover { border-color:var(--blue); color:var(--blue); background:#eff6ff; }
        .time-btn.selected { background:var(--blue); border-color:var(--blue); color:#fff; }

        /* Divider */
        .divider { border:none; border-top:1px solid var(--border); margin:14px 0; }

        /* Layanan terpilih */
        .svc-summary {
            background: #eff6ff; border: 1.5px solid #bfdbfe;
            border-radius: var(--radius-sm);
            padding: 8px 12px; margin-bottom: 12px;
            font-size: .79rem; color: #1e40af; font-weight: 600;
            display: none; line-height: 1.5;
        }

        /* Login required */
        .login-required {
            background: #f8fafc; border: 1.5px dashed var(--border);
            border-radius: var(--radius-sm); padding: 18px 14px;
            text-align: center; margin-bottom: 12px;
        }
        .login-required p { font-size:.82rem; color:var(--muted); margin-bottom:12px; line-height:1.6; }
        .btn-masuk {
            display:inline-block; padding:10px 24px;
            background:linear-gradient(135deg,#1d4ed8,#3b82f6);
            color:#fff; border-radius:999px;
            font-family:var(--fn-head); font-size:.875rem; font-weight:700;
            text-decoration:none; transition:box-shadow var(--tr);
        }
        .btn-masuk:hover { box-shadow:0 4px 16px rgba(37,99,235,.4); }

        /* Confirm button */
        .btn-konfirmasi {
            width:100%; padding:13px;
            background:linear-gradient(135deg,#1d4ed8,#2563eb,#3b82f6);
            color:#fff; border:none; border-radius:999px;
            font-family:var(--fn-head); font-size:.92rem; font-weight:700;
            cursor:pointer; transition:box-shadow var(--tr),transform var(--tr);
            margin-bottom:10px; letter-spacing:-.01em;
        }
        .btn-konfirmasi:hover { box-shadow:0 6px 24px rgba(37,99,235,.4); transform:translateY(-2px); }
        .btn-konfirmasi:active { transform:scale(.98); }
        .btn-konfirmasi:disabled { opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }

        .catatan { text-align:center; font-size:.69rem; color:var(--muted); line-height:1.5; }

        /* Responsive */
        @media (max-width:900px)  { .layout { grid-template-columns:1fr; } .schedule-panel { position:static; } }
        @media (max-width:560px)  { .cards-grid { grid-template-columns:1fr; } .topbar { padding:0 16px; } .page-wrap { padding:20px 14px 48px; } .times-grid { grid-template-columns:1fr 1fr; } }

        /* Fade in */
        @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
        .fade-in    { animation:fadeUp .4s ease both; }
        .fade-d1    { animation-delay:.06s; }
        .fade-d2    { animation-delay:.12s; }
    </style>
</head>
<body>

<!-- ── Topbar ──────────────────────────────────────────────────────────────── -->
<header class="topbar">
    <a href="Home.php" class="topbar-brand">King Dental</a>
    <nav class="topbar-nav">
        <a href="Home.php">Beranda</a>
        <?php if ($isLoggedIn): ?>
            <div class="user-chip">👤 <?= $username ?></div>
            <a href="logout.php" class="btn-nav">Keluar</a>
        <?php else: ?>
            <a href="login.php">Masuk</a>
            <a href="register.php" class="btn-nav">Daftar</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ── Main ───────────────────────────────────────────────────────────────── -->
<main class="page-wrap">

    <!-- Judul Halaman -->
    <div class="page-header fade-in">
        <h1>Pesan Layanan <span>Perawatan Gigi</span><br>Terbaik Anda.</h1>
        <p>
            Pilih layanan, tentukan tanggal dan jam kunjungan, lalu konfirmasi reservasi Anda.
            <?php if (!$isLoggedIn): ?>
                <a href="login.php">Masuk</a> untuk melakukan booking.
            <?php endif; ?>
        </p>
    </div>

    <!-- Notifikasi -->
    <?php if ($success): ?>
    <div class="alert alert-success fade-in">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error fade-in">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Layout dua kolom -->
    <div class="layout">

        <!-- Kiri: Kartu Layanan -->
        <div class="cards-grid fade-in fade-d1">
            <?php foreach ($displayServices as $i => $svc): ?>
            <div class="service-card"
                 id="card-<?= $i ?>"
                 tabindex="0"
                 role="button"
                 aria-label="Pilih layanan <?= htmlspecialchars($svc['nama']) ?>"
                 data-idx="<?= $i ?>"
                 data-id="<?= (int)$svc['id'] ?>"
                 data-nama="<?= htmlspecialchars($svc['nama'], ENT_QUOTES) ?>"
                 data-harga="<?= htmlspecialchars($svc['harga'] ?? '', ENT_QUOTES) ?>">



                <!-- Harga -->
                <?php if ($svc['harga']): ?>
                <div class="card-price"><?= htmlspecialchars($svc['harga']) ?></div>
                <?php endif; ?>

                <!-- Nama & Deskripsi -->
                <div class="card-name"><?= htmlspecialchars($svc['nama']) ?></div>
                <div class="card-desc"><?= htmlspecialchars($svc['desc']) ?></div>

                <!-- Daftar Dokter -->
                <div class="card-doctors">
                    <?php foreach ($svc['doctors'] as $doc): ?>
                    <span class="doctor-tag"><?= htmlspecialchars($doc) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Kanan: Panel Jadwal -->
        <aside class="schedule-panel fade-in fade-d2">
            <div class="panel-title">Jadwalkan Kunjungan Anda</div>

            <!-- Layanan terpilih -->
            <div class="svc-summary" id="svcSummary">
                <span id="svcSummaryText">—</span>
            </div>

            <!-- Pilih Tanggal -->
            <div class="section-label">Pilih Tanggal</div>
            <input type="date"
                   class="date-input"
                   id="tanggalInput"
                   min="<?= date('Y-m-d') ?>"
                   value="<?= date('Y-m-d') ?>">

            <!-- Pilih Waktu -->
            <div class="section-label">Pilih Waktu</div>
            <div class="times-grid" id="timeGrid">
                <?php foreach ($timeSlots as $k => $t): ?>
                <button type="button"
                        class="time-btn<?= $k === 0 ? ' selected' : '' ?>"
                        id="timebtn-<?= $k ?>"
                        data-time="<?= $t ?>"
                        onclick="selectTime(this)">
                    <?= $t ?>
                </button>
                <?php endforeach; ?>
            </div>

            <hr class="divider">

            <?php if ($isLoggedIn): ?>
            <!-- Form Booking (hanya jika sudah login) -->
            <form method="POST" action="booking.php" id="bookingForm">
                <input type="hidden" name="action"     value="reservasi">
                <input type="hidden" name="layanan_id" id="fLayananId" value="">
                <input type="hidden" name="tanggal"    id="fTanggal"   value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="jam"        id="fJam"       value="<?= $timeSlots[0] ?>">

                <button type="submit" class="btn-konfirmasi" id="btnKonfirmasi" disabled>
                    Konfirmasi Reservasi
                </button>
            </form>

            <?php else: ?>
            <!-- Belum login -->
            <div class="login-required">
                <p>Anda harus masuk terlebih dahulu untuk melakukan reservasi.</p>
                <a href="login.php" class="btn-masuk">Masuk untuk Booking</a>
            </div>
            <?php endif; ?>

            <!-- <p class="catatan">Pembatalan gratis hingga 24 jam sebelum perawatan.</p> -->
        </aside>

    </div>
</main>

<script>
// ── State ──────────────────────────────────────────────────────────────────
let activeCardIdx  = -1;
let selectedTime   = '<?= $timeSlots[0] ?>';

// ── Kartu Layanan: Event Listener ──────────────────────────────────────────
document.querySelectorAll('.service-card').forEach(function(card) {
    card.addEventListener('click', function() {
        const idx       = parseInt(this.dataset.idx);
        const layananId = this.dataset.id;      // string dari data-id
        const nama      = this.dataset.nama;
        const harga     = this.dataset.harga;

        // Hapus pilihan sebelumnya
        if (activeCardIdx >= 0) {
            const prev = document.getElementById('card-' + activeCardIdx);
            if (prev) prev.classList.remove('selected');
        }

        // Tandai kartu yang dipilih
        this.classList.add('selected');
        activeCardIdx = idx;

        // Update ringkasan layanan
        const svcSummary = document.getElementById('svcSummary');
        const svcText    = document.getElementById('svcSummaryText');
        svcText.textContent = nama + (harga ? ' — ' + harga : '');
        svcSummary.style.display = 'block';

        // Isi hidden input layanan_id (kirim apa adanya, server yang validasi)
        const fL = document.getElementById('fLayananId');
        if (fL) fL.value = layananId || '0';

        // Aktifkan tombol konfirmasi (selalu aktif saat kartu dipilih)
        const btn = document.getElementById('btnKonfirmasi');
        if (btn) btn.disabled = false;
    });

    // Keyboard support
    card.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
});

// ── Tanggal ────────────────────────────────────────────────────────────────
document.getElementById('tanggalInput')?.addEventListener('change', function() {
    const fT = document.getElementById('fTanggal');
    if (fT) fT.value = this.value;
});

// ── Waktu ──────────────────────────────────────────────────────────────────
function selectTime(btn) {
    document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedTime = btn.dataset.time;
    const fJ = document.getElementById('fJam');
    if (fJ) fJ.value = selectedTime;
}

// updateBtn tidak lagi dipakai — tombol diaktifkan langsung saat kartu diklik
</script>

</body>
</html>