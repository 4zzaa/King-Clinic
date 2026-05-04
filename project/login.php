<?php
require_once "koneksi.php";
require_once "Auth.php";

Auth::startSession();

$auth  = new Auth();
$error = '';

// Jika sudah login, redirect ke halaman yang sesuai
$auth->redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $auth->login($username, $password);

    if ($result['success']) {
        if ($result['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: Home.php");
        }
        exit();
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login — Poli Gigi</title>
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
        --primary-light: #eff6ff;
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
        max-width: 420px;
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
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
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
    </style>
</head>

<body>
    <div class="wrapper">

        <!-- Card -->
        <div class="card">
            <div class="card-title">Masuk ke Akun</div>

            <?php if ($error): ?>
            <div class="alert" role="alert">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                        clip-rule="evenodd" />
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>


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
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            maxlength="50" autocomplete="username" required>
                    </div>
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
                            placeholder="Masukkan password"
                            maxlength="128" autocomplete="current-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Tampilkan password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" id="eye-icon">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                    </svg>
                    Login
                </button>
            </form>

            <div class="card-footer">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>
        </div>
    </div>

    <script>
    function togglePw() {
        const inp = document.getElementById('password');
        const isHidden = inp.type === 'password';
        inp.type = isHidden ? 'text' : 'password';
        document.getElementById('eye-icon').style.opacity = isHidden ? '0.5' : '1';
    }

    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Memproses...';
    });
    </script>
    <style>
    @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</body>

</html>