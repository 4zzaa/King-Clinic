<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['user']['username']);

// Stats
$totalUsers = 0;
$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pasien WHERE role = 'pasien'");
if ($res) $totalUsers = mysqli_fetch_assoc($res)['total'];

$totalDokter = 0;
$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM dokter");
if ($res) $totalDokter = mysqli_fetch_assoc($res)['total'];

$bulanIni = date('Y-m');
$totalTransaksi = 0;
$res = mysqli_query($koneksi, "SELECT 
    (SELECT COUNT(*) FROM transaksi_alat WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni') +
    (SELECT COUNT(*) FROM transaksi_reservasi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni') as total");
if ($res) $totalTransaksi = mysqli_fetch_assoc($res)['total'];

// Jadwal dokter hari ini
$hariIni = date('Y-m-d');
$jadwalHariIni = [];
$res = mysqli_query($koneksi, "SELECT d.nama_dokter, d.tersedia, 
    (SELECT COUNT(*) FROM transaksi_reservasi tr WHERE tr.dokter_id = d.id_dokter AND tr.tanggal = '$hariIni') as ada_pasien
    FROM dokter d ORDER BY d.id_dokter ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $jadwalHariIni[] = $row;
    }
}

// Format tanggal Indonesia
$hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$bulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$tanggalFormatted = $hari[date('l')] . ', ' . date('d') . ' ' . $bulan[date('F')] . ' ' . date('Y');

// Logika greeting dihapus sesuai permintaan
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard — King Clinic Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar_admin.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title">Dashboard</div>
                <div class="topbar-user">
                    <div>
                        <div class="user-name"><?= $adminName ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                </div>
            </div>

            <div class="content-area">
                <div class="page-header">
                    <div class="page-header-row">
                        <div>
                            <h2>Selamat datang Admin</h2>
                            <!-- <p>Ringkasan operasional King Clinic hari ini.</p> -->
                        </div>

                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total User Terdaftar</div>
                        <div class="stat-value"><?= number_format($totalUsers) ?></div>
                        <div class="stat-sub">Pasien yang terdaftar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Dokter</div>
                        <div class="stat-value"><?= $totalDokter ?></div>
                        <div class="stat-sub">Dokter aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Transaksi Bulan Ini</div>
                        <div class="stat-value"><?= number_format($totalTransaksi) ?></div>
                        <div class="stat-sub">Pembelian alat + reservasi</div>
                    </div>
                </div>

                <!-- Jadwal Dokter Hari Ini -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Jadwal Dokter Hari Ini — <?= $tanggalFormatted ?></h3>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Dokter</th>
                                    <th>Jam Praktik</th>
                                    <th>Status</th>
                                    <th>Pasien Hari Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jadwalHariIni)): ?>
                                <tr>
                                    <td colspan="5" class="text-muted" style="text-align:center; padding:32px;">Belum ada data dokter.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($jadwalHariIni as $i => $dok): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($dok['nama_dokter']) ?></td>
                                    <td>08:00 — 16:00</td>
                                    <td>
                                        <?php if ($dok['tersedia']): ?>
                                            <?php if ($dok['ada_pasien'] > 0): ?>
                                                <span class="badge badge-warning">Penuh</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Tersedia</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Tidak Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($dok['ada_pasien'] > 0): ?>
                                            <span class="badge badge-info"><?= $dok['ada_pasien'] ?> pasien</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
