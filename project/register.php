<?php
session_start();
include "koneksi.php";

// Jika sudah login, redirect ke home
if (isset($_SESSION['user'])) {
    header("Location: Home.php");
    exit();
}

// Generate CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error   = '';
$success = '';
$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. CSRF Verification ─────────────────────────────────
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {
        // ── 2. Ambil & Sanitasi Input ─────────────────────────
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $usernameValue = htmlspecialchars($username);

        // ── 3. Validasi ───────────────────────────────────────
        if (empty($username)) {
            $error = 'Username tidak boleh kosong.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username harus antara 3–50 karakter.';
        } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
            $error = 'Username hanya boleh huruf, angka, underscore, dan strip.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif (strlen($password) > 128) {
            $error = 'Password terlalu panjang.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            // ── 4. Cek duplikat username (prepared statement) ──
            $stmt = mysqli_prepare($koneksi, "SELECT id_pengguna FROM pasien WHERE username = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error = 'Username sudah digunakan. Pilih username lain.';
                    mysqli_stmt_close($stmt);
                } else {
                    mysqli_stmt_close($stmt);

                    // ── 5. Hash Password ───────────────────────
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                    // ── 6. Simpan ke database ──────────────────
                    $insert = mysqli_prepare($koneksi, "INSERT INTO pasien (username, password, role) VALUES (?, ?, 'pasien')");
                    if ($insert) {
                        mysqli_stmt_bind_param($insert, "ss", $username, $hashedPassword);
                        if (mysqli_stmt_execute($insert)) {
                            // Regenerate CSRF setelah sukses
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            $success = 'Akun berhasil dibuat! Silakan login.';
                            $usernameValue = '';
                        } else {
                            $error = 'Registrasi gagal. Silakan coba lagi.';
                        }
                        mysqli_stmt_close($insert);
                    } else {
                        $error = 'Terjadi kesalahan server.';
                    }
                }
            } else {
                $error = 'Terjadi kesalahan server.';
            }
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Daftar — Poli Gigi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    :root {
        --primary: #3b82f6;
        --primary-hover: #2563eb;
        --danger: #ef4444;
        --success: #22c55e;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --bg: #f8fafc;
        --card-bg: #ffffff;
        --radius: 14px;
        --shadow-lg: 0 20px 40px -8px rgba(59, 130, 246, .15), 0 8px 16px -4px rgba(0, 0, 0, .08);
        --font: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font);
        background: var(--bg);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background-image:
            radial-gradient(ellipse 80% 60% at 20% -10%, rgba(59, 130, 246, .08) 0%, transparent 60%),
            radial-gradient(ellipse 60% 50% at 80% 110%, rgba(37, 99, 235, .06) 0%, transparent 60%);
    }

    .wrapper {
        width: 100%;
        max-width: 440px;
        animation: fadeUp .45s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }


    .card {
        background: var(--card-bg);
        border-radius: var(--radius);
        padding: 36px 32px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border);
    }

    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 24px;
        letter-spacing: -.01em;
    }

    .alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: .875rem;
        font-weight: 500;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .alert-error {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .alert-success {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .alert svg { flex-shrink: 0; margin-top: 1px; }

    .form-group { margin-bottom: 18px; }

    label {
        display: block;
        font-size: .8125rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 7px;
        letter-spacing: .01em;
    }

    .input-wrap { position: relative; }

    .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
    }

    .input-icon svg { width: 17px; height: 17px; }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 11px 14px 11px 42px;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        font-family: var(--font);
        font-size: .9375rem;
        color: var(--text-main);
        background: #fafbfc;
        outline: none;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }

    input:focus {
        border-color: var(--primary);
        background: #fff;
        box-shadow: 0 0 0 3.5px rgba(59, 130, 246, .15);
    }

    .toggle-pw {
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        padding: 4px;
        display: flex;
        align-items: center;
        transition: color .2s;
    }

    .toggle-pw:hover { color: var(--primary); }
    .toggle-pw svg { width: 18px; height: 18px; }


    .btn-primary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 13px;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-family: var(--font);
        font-size: .9375rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s, transform .15s, box-shadow .2s;
        margin-top: 8px;
        letter-spacing: -.01em;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        box-shadow: 0 6px 16px rgba(59, 130, 246, .35);
    }

    .btn-primary:active { transform: scale(.98); }
    .btn-primary:disabled { opacity: .65; cursor: not-allowed; }

    .card-footer {
        text-align: center;
        margin-top: 22px;
        font-size: .875rem;
        color: var(--text-muted);
    }

    .card-footer a {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        transition: color .2s;
    }

    .card-footer a:hover {
        color: var(--primary-hover);
        text-decoration: underline;
    }

    .hint {
        font-size: .75rem;
        color: var(--text-muted);
        margin-top: 5px;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>

<body>
    <div class="wrapper">

        <!-- Card -->
        <div class="card">
            <div class="card-title">Daftar Akun</div>

            <?php if ($error): ?>
            <div class="alert alert-error" role="alert">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                        clip-rule="evenodd" />
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                <?= htmlspecialchars($success) ?> <a href="login.php">Login sekarang →</a>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate autocomplete="off">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M20 21a8 8 0 10-16 0" />
                            </svg>
                        </span>
                        <input type="text" id="username" name="username"
                            placeholder="Masukkan username"
                            value="<?= $usernameValue ?>"
                            maxlength="50" autocomplete="off" spellcheck="false" required>
                    </div>
                    <p class="hint">Huruf, angka, underscore, strip. Min. 3 karakter.</p>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                        </span>
                        <input type="password" id="password" name="password"
                            placeholder="Min. 8 karakter"
                            maxlength="128" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('password', this)"
                            aria-label="Tampilkan password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>

                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4" />
                                <rect width="18" height="11" x="3" y="11" rx="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Ulangi password"
                            maxlength="128" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)"
                            aria-label="Tampilkan konfirmasi password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <line x1="19" y1="8" x2="19" y2="14" />
                        <line x1="22" y1="11" x2="16" y2="11" />
                    </svg>
                    Buat Akun
                </button>
            </form>

            <div class="card-footer">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </div>

    <script>
    function togglePw(id, btn) {
        const inp = document.getElementById(id);
        const isHidden = inp.type === 'password';
        inp.type = isHidden ? 'text' : 'password';
        btn.querySelector('.eye-icon').style.opacity = isHidden ? '0.5' : '1';
    }



    // Prevent double submit
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Memproses...';
    });
    </script>
</body>

</html>