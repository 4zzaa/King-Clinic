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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $no_hp = trim($_POST['no_hp'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $koneksi->prepare("INSERT INTO pasien (username, password, no_hp, role) VALUES (?, ?, ?, 'pasien')");
            $stmt->bind_param('sss', $username, $hashed, $no_hp);
            if ($stmt->execute()) {
                $success = 'User berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan user. Username mungkin sudah ada.';
            }
            $stmt->close();
        }
    }



    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $koneksi->prepare("DELETE FROM pasien WHERE id_pengguna=? AND role='pasien'");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'User berhasil dihapus.';
            } else {
                $error = 'Gagal menghapus user.';
            }
            $stmt->close();
        }
    }
}

// Fetch all users (pasien only)
$users = [];
$res = mysqli_query($koneksi, "SELECT id_pengguna, username, no_hp, created_at FROM pasien WHERE role = 'pasien' ORDER BY id_pengguna DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Kelola Users — King Clinic Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="layout">
        <?php include 'includes/sidebar_admin.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title">Kelola Users</div>
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
                            <h2>Data User (Pasien)</h2>

                        </div>
                        <button class="btn btn-primary" onclick="openModal('addModal')">+ Tambah User</button>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header">
                        <h3>Daftar User — <?= count($users) ?> total</h3>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>No HP</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-muted" style="text-align:center; padding:32px;">Belum ada data user.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['no_hp'] ?: '—') ?></td>
                                    <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id_pengguna'] ?>">
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

    <!-- Modal: Tambah User -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Tambah User Baru</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
                    </div>
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
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


    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
