<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['user']['username']);
$success = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'alat';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';


    if ($action === 'create_alat') {
        $nama = trim($_POST['nama_barang'] ?? '');
        $jumlah = (int)($_POST['jumlah'] ?? 1);
        $harga = (float)($_POST['harga_satuan'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

        if (empty($nama) || $harga <= 0) {
            $error = 'Nama barang dan harga wajib diisi.';
        } else {
            $stmt = $koneksi->prepare("INSERT INTO transaksi_alat (nama_barang, jumlah, harga_satuan, tanggal) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sids', $nama, $jumlah, $harga, $tanggal);
            if ($stmt->execute()) {
                $success = 'Transaksi pembelian alat berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan transaksi.';
            }
            $stmt->close();
        }
        $activeTab = 'alat';
    }

    if ($action === 'delete_alat') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $koneksi->prepare("DELETE FROM transaksi_alat WHERE id_transaksi=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $success = 'Transaksi alat berhasil dihapus.';
        }
        $activeTab = 'alat';
    }

    if ($action === 'update_alat') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_barang'] ?? '');
        $jumlah = (int)($_POST['jumlah'] ?? 1);
        $harga = (float)($_POST['harga_satuan'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

        if ($id > 0 && !empty($nama)) {
            $stmt = $koneksi->prepare("UPDATE transaksi_alat SET nama_barang=?, jumlah=?, harga_satuan=?, tanggal=? WHERE id_transaksi=?");
            $stmt->bind_param('sidsi', $nama, $jumlah, $harga, $tanggal, $id);
            if ($stmt->execute()) {
                $success = 'Transaksi alat berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui transaksi.';
            }
            $stmt->close();
        }
        $activeTab = 'alat';
    }


    if ($action === 'create_reservasi') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $dokterId = (int)($_POST['dokter_id'] ?? 0);
        $namaLayanan = trim($_POST['nama_layanan'] ?? '');
        $hargaLayanan = (float)($_POST['harga_layanan'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

        if ($userId <= 0 || $dokterId <= 0 || empty($namaLayanan)) {
            $error = 'Semua field wajib diisi.';
        } else {

            $check = $koneksi->prepare("SELECT COUNT(*) as c FROM transaksi_reservasi WHERE dokter_id=? AND tanggal=?");
            $check->bind_param('is', $dokterId, $tanggal);
            $check->execute();
            $cResult = $check->get_result()->fetch_assoc();
            $check->close();

            if ($cResult['c'] > 0) {
                $error = 'Dokter sudah memiliki pasien pada tanggal tersebut (maks 1 pasien/hari).';
            } else {
                $stmt = $koneksi->prepare("INSERT INTO transaksi_reservasi (user_id, dokter_id, nama_layanan, harga_layanan, tanggal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iisds', $userId, $dokterId, $namaLayanan, $hargaLayanan, $tanggal);
                if ($stmt->execute()) {
                    $success = 'Reservasi berhasil ditambahkan.';
                } else {
                    $error = 'Gagal menambahkan reservasi.';
                }
                $stmt->close();
            }
        }
        $activeTab = 'reservasi';
    }

    if ($action === 'delete_reservasi') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $koneksi->prepare("DELETE FROM transaksi_reservasi WHERE id_reservasi=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $success = 'Reservasi berhasil dihapus.';
        }
        $activeTab = 'reservasi';
    }
}


$trAlat = [];
$res = mysqli_query($koneksi, "SELECT * FROM transaksi_alat ORDER BY tanggal DESC, id_transaksi DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $trAlat[] = $r; }


$trReservasi = [];
$res = mysqli_query($koneksi, "SELECT tr.*, p.username, d.nama_dokter 
    FROM transaksi_reservasi tr 
    LEFT JOIN pasien p ON tr.user_id = p.id_pengguna 
    LEFT JOIN dokter d ON tr.dokter_id = d.id_dokter 
    ORDER BY tr.tanggal DESC, tr.id_reservasi DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $trReservasi[] = $r; }


$usersList = [];
$res = mysqli_query($koneksi, "SELECT id_pengguna, username FROM pasien WHERE role='pasien' ORDER BY username");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $usersList[] = $r; }

$dokterList = [];
$res = mysqli_query($koneksi, "SELECT id_dokter, nama_dokter FROM dokter WHERE tersedia=1 ORDER BY nama_dokter");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $dokterList[] = $r; }


$layananList = [];
$res = mysqli_query($koneksi, "SELECT id_layanan, nama_layanan, harga FROM layanan ORDER BY nama_layanan");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $layananList[] = $r; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Transaksi — King Clinic Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar_admin.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title">Transaksi</div>
                <div class="topbar-user">
                    <div>
                        <div class="user-name"><?= $adminName ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                </div>
            </div>

            <div class="content-area">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="page-header">
                    <div class="page-header-row">
                        <div>
                            <h2>Data Transaksi</h2>

                        </div>
                        <div class="btn-group">
                            <button class="btn btn-primary" onclick="openModal('addAlatModal')" id="btnAddAlat" style="<?= $activeTab !== 'alat' ? 'display:none' : '' ?>">+ Pembelian Alat</button>
                            <button class="btn btn-primary" onclick="openModal('addReservasiModal')" id="btnAddReservasi" style="<?= $activeTab !== 'reservasi' ? 'display:none' : '' ?>">+ Reservasi Pasien</button>
                        </div>
                    </div>
                </div>


                <div class="tabs">
                    <button class="tab-btn <?= $activeTab === 'alat' ? 'active' : '' ?>" onclick="switchTab('alat')">Pembelian Alat</button>
                    <button class="tab-btn <?= $activeTab === 'reservasi' ? 'active' : '' ?>" onclick="switchTab('reservasi')">Reservasi Pasien</button>
                </div>


                <div class="tab-content <?= $activeTab === 'alat' ? 'active' : '' ?>" id="tab-alat">
                    <div class="table-card">
                        <div class="table-header">
                            <h3>Riwayat Pembelian Alat — <?= count($trAlat) ?> transaksi</h3>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Barang</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($trAlat)): ?>
                                    <tr>
                                        <td colspan="7" class="text-muted" style="text-align:center; padding:32px;">Belum ada transaksi pembelian alat.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($trAlat as $i => $t): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td style="font-weight:600;"><?= htmlspecialchars($t['nama_barang']) ?></td>
                                        <td><?= $t['jumlah'] ?></td>
                                        <td>Rp <?= number_format($t['harga_satuan'], 0, ',', '.') ?></td>
                                        <td style="font-weight:600;">Rp <?= number_format($t['jumlah'] * $t['harga_satuan'], 0, ',', '.') ?></td>
                                        <td class="text-muted"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-edit" onclick="openEditAlat(<?= $t['id_transaksi'] ?>, '<?= htmlspecialchars($t['nama_barang'], ENT_QUOTES) ?>', <?= $t['jumlah'] ?>, <?= $t['harga_satuan'] ?>, '<?= $t['tanggal'] ?>')">Edit</button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus transaksi ini?')">
                                                    <input type="hidden" name="action" value="delete_alat">
                                                    <input type="hidden" name="id" value="<?= $t['id_transaksi'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-delete">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <div class="tab-content <?= $activeTab === 'reservasi' ? 'active' : '' ?>" id="tab-reservasi">
                    <div class="table-card">
                        <div class="table-header">
                            <h3>Riwayat Reservasi Pasien — <?= count($trReservasi) ?> transaksi</h3>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Pasien</th>
                                        <th>Layanan</th>
                                        <th>Harga</th>
                                        <th>Dokter</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($trReservasi)): ?>
                                    <tr>
                                        <td colspan="7" class="text-muted" style="text-align:center; padding:32px;">Belum ada transaksi reservasi.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($trReservasi as $i => $t): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td style="font-weight:600;"><?= htmlspecialchars($t['username'] ?? 'Unknown') ?></td>
                                        <td><?= htmlspecialchars($t['nama_layanan']) ?></td>
                                        <td>Rp <?= number_format($t['harga_layanan'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($t['nama_dokter'] ?? '-') ?></td>
                                        <td class="text-muted"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus reservasi ini?')">
                                                <input type="hidden" name="action" value="delete_reservasi">
                                                <input type="hidden" name="id" value="<?= $t['id_reservasi'] ?>">
                                                <button type="submit" class="btn btn-sm btn-delete">Hapus</button>
                                            </form>
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
    </div>


    <div class="modal-overlay" id="addAlatModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Tambah Pembelian Alat</h3>
                <button class="modal-close" onclick="closeModal('addAlatModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_alat">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="contoh: Dental Mirror" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Harga Satuan (Rp)</label>
                        <input type="number" name="harga_satuan" class="form-control" min="0" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addAlatModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>


    <div class="modal-overlay" id="editAlatModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Pembelian Alat</h3>
                <button class="modal-close" onclick="closeModal('editAlatModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_alat">
                <input type="hidden" name="id" id="ea_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" id="ea_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" id="ea_jumlah" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Harga Satuan (Rp)</label>
                        <input type="number" name="harga_satuan" id="ea_harga" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" id="ea_tanggal" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editAlatModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>


    <div class="modal-overlay" id="addReservasiModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Tambah Reservasi Pasien</h3>
                <button class="modal-close" onclick="closeModal('addReservasiModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_reservasi">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pasien</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">— Pilih Pasien —</option>
                            <?php foreach ($usersList as $u): ?>
                            <option value="<?= $u['id_pengguna'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Layanan</label>
                        <select name="nama_layanan" class="form-control" required id="selectLayanan" onchange="updateHarga()">
                            <option value="">— Pilih Layanan —</option>
                            <?php foreach ($layananList as $l): ?>
                            <option value="<?= htmlspecialchars($l['nama_layanan']) ?>" data-harga="<?= $l['harga'] ?>"><?= htmlspecialchars($l['nama_layanan']) ?> — Rp <?= number_format($l['harga'], 0, ',', '.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga Layanan (Rp)</label>
                        <input type="number" name="harga_layanan" id="inputHarga" class="form-control" min="0" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>Dokter</label>
                        <select name="dokter_id" class="form-control" required>
                            <option value="">— Pilih Dokter —</option>
                            <?php foreach ($dokterList as $d): ?>
                            <option value="<?= $d['id_dokter'] ?>"><?= htmlspecialchars($d['nama_dokter']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Reservasi</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addReservasiModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) {
        document.getElementById(id).classList.add('show');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(function(btn) { btn.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });

        if (tab === 'alat') {
            document.querySelectorAll('.tab-btn')[0].classList.add('active');
            document.getElementById('tab-alat').classList.add('active');
            document.getElementById('btnAddAlat').style.display = '';
            document.getElementById('btnAddReservasi').style.display = 'none';
        } else {
            document.querySelectorAll('.tab-btn')[1].classList.add('active');
            document.getElementById('tab-reservasi').classList.add('active');
            document.getElementById('btnAddAlat').style.display = 'none';
            document.getElementById('btnAddReservasi').style.display = '';
        }
    }

    function openEditAlat(id, nama, jumlah, harga, tanggal) {
        document.getElementById('ea_id').value = id;
        document.getElementById('ea_nama').value = nama;
        document.getElementById('ea_jumlah').value = jumlah;
        document.getElementById('ea_harga').value = harga;
        document.getElementById('ea_tanggal').value = tanggal;
        openModal('editAlatModal');
    }

    function updateHarga() {
        var sel = document.getElementById('selectLayanan');
        var opt = sel.options[sel.selectedIndex];
        var harga = opt.getAttribute('data-harga') || '';
        document.getElementById('inputHarga').value = harga;
    }

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('show');
        });
    });
    </script>
</body>
</html>
