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

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama = trim($_POST['nama_dokter'] ?? '');
        $tersedia = isset($_POST['tersedia']) ? 1 : 0;

        if (empty($nama)) {
            $error = 'Nama dokter wajib diisi.';
        } else {
            $stmt = $koneksi->prepare("INSERT INTO dokter (nama_dokter, tersedia) VALUES (?, ?)");
            $stmt->bind_param('si', $nama, $tersedia);
            if ($stmt->execute()) {
                $success = 'Dokter berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan dokter.';
            }
            $stmt->close();
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_dokter'] ?? '');
        $tersedia = isset($_POST['tersedia']) ? 1 : 0;

        if ($id > 0 && !empty($nama)) {
            $stmt = $koneksi->prepare("UPDATE dokter SET nama_dokter=?, tersedia=? WHERE id_dokter=?");
            $stmt->bind_param('sii', $nama, $tersedia, $id);
            if ($stmt->execute()) {
                $success = 'Data dokter berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui data dokter.';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $koneksi->prepare("DELETE FROM dokter WHERE id_dokter=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'Dokter berhasil dihapus.';
            } else {
                $error = 'Gagal menghapus dokter. Mungkin masih ada transaksi terkait.';
            }
            $stmt->close();
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $koneksi->prepare("UPDATE dokter SET tersedia = NOT tersedia WHERE id_dokter=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_doctors.php");
            exit();
        }
    }
}

// Fetch all doctors
$doctors = [];
$hariIni = date('Y-m-d');
$res = mysqli_query($koneksi, "SELECT d.*, 
    (SELECT COUNT(*) FROM transaksi_reservasi tr WHERE tr.dokter_id = d.id_dokter AND tr.tanggal = '$hariIni') as pasien_hari_ini
    FROM dokter d ORDER BY d.id_dokter ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $doctors[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Kelola Dokter — King Clinic Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar_admin.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title">Kelola Dokter</div>
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
                            <h2>Data Dokter</h2>

                        </div>
                        <button class="btn btn-primary" onclick="openModal('addModal')">+ Tambah Dokter</button>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header">
                        <h3>Daftar Dokter — <?= count($doctors) ?> total</h3>
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
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctors)): ?>
                                <tr>
                                    <td colspan="6" class="text-muted" style="text-align:center; padding:32px;">Belum ada data dokter.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($doctors as $i => $d): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($d['nama_dokter']) ?></td>
                                    <td>08:00 — 16:00</td>
                                    <td>
                                        <?php if ($d['tersedia']): ?>
                                            <span class="badge badge-success">Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Tidak Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($d['pasien_hari_ini'] > 0): ?>
                                            <span class="badge badge-info"><?= $d['pasien_hari_ini'] ?> pasien</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= $d['id_dokter'] ?>">
                                                <button type="submit" class="btn btn-sm <?= $d['tersedia'] ? 'btn-delete' : 'btn-edit' ?>" title="Toggle status">
                                                    <?= $d['tersedia'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>
                                            <button class="btn btn-sm btn-edit" onclick="openEditDokter(<?= $d['id_dokter'] ?>, '<?= htmlspecialchars($d['nama_dokter'], ENT_QUOTES) ?>', <?= $d['tersedia'] ?>)">Edit</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus dokter ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $d['id_dokter'] ?>">
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
        </div>
    </div>

    <!-- Modal: Tambah Dokter -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Tambah Dokter Baru</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Dokter</label>
                        <input type="text" name="nama_dokter" class="form-control" placeholder="contoh: drg. Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="tersedia" checked style="width:16px; height:16px;">
                            Tersedia (aktif menerima pasien)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Dokter -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Dokter</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Dokter</label>
                        <input type="text" name="nama_dokter" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="tersedia" id="edit_tersedia" style="width:16px; height:16px;">
                            Tersedia (aktif menerima pasien)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
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
    function openEditDokter(id, nama, tersedia) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_tersedia').checked = tersedia == 1;
        openModal('editModal');
    }
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('show');
        });
    });
    </script>
</body>
</html>
