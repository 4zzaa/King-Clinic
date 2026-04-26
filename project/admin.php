<?php
session_start();
include "koneksi.php";

// Guard: hanya admin yang boleh masuk
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user']['role'] !== 'admin') {
    // Kalau sudah login tapi bukan admin, tendang ke Home pasien
    header("Location: Home.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['user']['username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin — Poli Gigi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --primary: #7c3aed;
        --primary-hover: #6d28d9;
        --primary-light: #f5f3ff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --bg: #f8fafc;
        --font: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font);
        background: var(--bg);
        min-height: 100vh;
        background-image:
            radial-gradient(ellipse 60% 40% at 5% 0%, rgba(124,58,237,.07) 0%, transparent 60%),
            radial-gradient(ellipse 50% 40% at 95% 100%, rgba(109,40,217,.05) 0%, transparent 60%);
    }

    /* ── Topbar ── */
    header {
        background: #fff;
        border-bottom: 1px solid var(--border);
        padding: 0 32px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 50;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }

    .topbar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--primary);
        letter-spacing: -.02em;
    }

    .topbar-brand .icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px; height: 36px;
        background: var(--primary);
        border-radius: 10px;
    }

    .topbar-brand .icon svg { width: 18px; height: 18px; fill: #fff; }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .user-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--primary-light);
        border: 1px solid #ddd6fe;
        border-radius: 99px;
        padding: 6px 14px 6px 6px;
        font-size: .8125rem;
        font-weight: 600;
        color: var(--primary);
    }

    .user-avatar {
        width: 28px; height: 28px;
        background: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .75rem;
        font-weight: 700;
    }

    .btn-logout {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        border: 1.5px solid #fecaca;
        background: #fef2f2;
        color: #b91c1c;
        font-family: var(--font);
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .2s, border-color .2s;
    }

    .btn-logout:hover { background: #fee2e2; border-color: #fca5a5; }
    .btn-logout svg { width: 15px; height: 15px; }

    /* ── Main layout ── */
    main {
        max-width: 1100px;
        margin: 0 auto;
        padding: 40px 24px;
        animation: fadeUp .45s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .page-title {
        font-size: 1.625rem;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -.03em;
        margin-bottom: 4px;
    }

    .page-sub {
        font-size: .9rem;
        color: var(--text-muted);
        margin-bottom: 36px;
    }

    /* ── Stat Cards ── */
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 18px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 16px;
        padding: 24px 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        transition: transform .2s, box-shadow .2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,.08);
    }

    .stat-label {
        font-size: .775rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -.04em;
    }

    .stat-icon {
        float: right;
        width: 42px; height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg { width: 20px; height: 20px; }
    .stat-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .stat-icon.blue   { background: #eff6ff; color: #3b82f6; }
    .stat-icon.green  { background: #f0fdf4; color: #16a34a; }
    .stat-icon.orange { background: #fff7ed; color: #ea580c; }

    /* ── Info card ── */
    .info-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 16px;
        padding: 28px 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        margin-bottom: 24px;
    }

    .info-card h2 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }

    .info-row:last-child { border-bottom: none; }
    .info-row .key { color: var(--text-muted); font-weight: 500; }
    .info-row .val { font-weight: 600; color: var(--text-main); }

    .badge-role {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 99px;
        font-size: .725rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        background: #f5f3ff;
        color: var(--primary);
        border: 1px solid #ddd6fe;
    }
    </style>
</head>
<body>

    <!-- Topbar -->
    <header>
        <div class="topbar-brand">
            <div class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C7.03 2 3 6.03 3 11c0 2.85 1.3 5.4 3.34 7.1L6 21a1 1 0 001 1h10a1 1 0 001-1l-.34-2.9C19.7 16.4 21 13.85 21 11c0-4.97-4.03-9-9-9zm-2 14H8v-2h2v2zm0-4H8V9h2v3zm4 4h-2v-2h2v2zm0-4h-2V9h2v3z" />
                </svg>
            </div>
            Dashboard Admin
        </div>
        <div class="topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                <?= $adminName ?>
            </div>
            <a href="logout.php" class="btn-logout" id="btn-logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </a>
        </div>
    </header>

    <main>
        <div class="page-title">Selamat datang, <?= $adminName ?>!</div>
        <p class="page-sub">Panel administrasi — Sistem Informasi Poli Gigi</p>

        <!-- Stat Cards -->
        <?php
        // Hitung jumlah pasien
        $totalPasien = 0;
        $res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pasien WHERE role = 'pasien'");
        if ($res) {
            $totalPasien = mysqli_fetch_assoc($res)['total'] ?? 0;
        }
        ?>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon purple" style="float:right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                        <path d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                </div>
                <div class="stat-label">Total Pasien</div>
                <div class="stat-value"><?= $totalPasien ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green" style="float:right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="stat-label">Status Sistem</div>
                <div class="stat-value" style="font-size:1.25rem;padding-top:6px;color:#16a34a">Online</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue" style="float:right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div class="stat-label">Tanggal</div>
                <div class="stat-value" style="font-size:1rem;padding-top:8px"><?= date('d M Y') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange" style="float:right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="stat-label">Waktu Login</div>
                <div class="stat-value" style="font-size:1rem;padding-top:8px"><?= date('H:i') ?> WIB</div>
            </div>
        </div>

        <!-- Session Info -->
        <div class="info-card">
            <h2>Informasi Sesi Admin</h2>
            <div class="info-row">
                <span class="key">Username</span>
                <span class="val"><?= $adminName ?></span>
            </div>
            <div class="info-row">
                <span class="key">Role</span>
                <span class="val"><span class="badge-role">Admin</span></span>
            </div>
            <div class="info-row">
                <span class="key">ID Pengguna</span>
                <span class="val">#<?= htmlspecialchars($_SESSION['user']['id_pengguna'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="key">Session ID</span>
                <span class="val" style="font-size:.75rem;color:var(--text-muted);font-family:monospace"><?= substr(session_id(), 0, 20) ?>...</span>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="info-card">
            <h2>Navigasi Cepat</h2>
            <div class="info-row">
                <span class="key">Halaman Home Pasien</span>
                <a href="Home.php" style="color:#3b82f6;font-weight:600;font-size:.875rem;text-decoration:none">Buka →</a>
            </div>
            <div class="info-row">
                <span class="key">Halaman Login</span>
                <a href="login.php" style="color:#3b82f6;font-weight:600;font-size:.875rem;text-decoration:none">Buka →</a>
            </div>
            <div class="info-row">
                <span class="key">Portal Utama</span>
                <a href="../index.php" style="color:#3b82f6;font-weight:600;font-size:.875rem;text-decoration:none">Buka →</a>
            </div>
            <div class="info-row">
                <span class="key">Logout dari sistem</span>
                <a href="logout.php" style="color:#b91c1c;font-weight:600;font-size:.875rem;text-decoration:none">Logout →</a>
            </div>
        </div>
    </main>

</body>
</html>
