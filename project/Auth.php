<?php
/**
 * Class Auth
 * Menangani semua operasi autentikasi: login, register, cek sesi, logout.
 */
class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Session Management ──────────────────────────────────

    /**
     * Memulai session jika belum aktif.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Cek apakah user sudah login.
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Ambil data user dari session.
     */
    public function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Ambil role user saat ini.
     */
    public function currentRole(): string
    {
        return $_SESSION['user']['role'] ?? '';
    }


    // ─── Login ───────────────────────────────────────────────

    /**
     * Proses login user.
     *
     * @param string $username
     * @param string $password
     * @return array ['success' => bool, 'error' => string, 'role' => string]
     */
    public function login(string $username, string $password): array
    {
        $result = ['success' => false, 'error' => '', 'role' => ''];

        if (empty($username) || empty($password)) {
            $result['error'] = 'Username dan password tidak boleh kosong.';
            return $result;
        }

        $stmt = $this->db->prepare(
            "SELECT id_pengguna, username, password, role FROM pasien WHERE username = ? LIMIT 1"
        );

        if (!$stmt) {
            $result['error'] = 'Terjadi kesalahan server. Coba lagi.';
            return $result;
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $queryResult = $stmt->get_result();
        $row = $queryResult->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $result['error'] = 'Username atau password salah.';
            return $result;
        }

        $passwordValid = false;

        if (password_verify($password, $row['password'])) {
            // Password sudah dalam format hash bcrypt — OK
            $passwordValid = true;
        } elseif ($password === $row['password']) {
            // Password masih plain text (data lama/admin awal)
            // Auto-upgrade ke bcrypt supaya aman ke depannya
            $passwordValid = true;
            $this->upgradePasswordHash($row['id_pengguna'], $password);
        }

        if (!$passwordValid) {
            $result['error'] = 'Username atau password salah.';
            return $result;
        }

        // Regenerate session ID untuk mencegah session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = $row;


        $result['success'] = true;
        $result['role']    = $row['role'];
        return $result;
    }

    /**
     * Upgrade plain-text password ke bcrypt hash.
     */
    private function upgradePasswordHash(int $userId, string $plainPassword): void
    {
        $newHash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare("UPDATE pasien SET password = ? WHERE id_pengguna = ?");
        if ($stmt) {
            $stmt->bind_param('si', $newHash, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ─── Register ────────────────────────────────────────────

    /**
     * Proses registrasi user baru.
     *
     * @param string $username
     * @param string $password
     * @param string $confirmPassword
     * @return array ['success' => bool, 'error' => string]
     */
    public function register(string $username, string $password, string $confirmPassword): array
    {
        $result = ['success' => false, 'error' => ''];

        // Validasi username
        if (empty($username)) {
            $result['error'] = 'Username tidak boleh kosong.';
            return $result;
        }
        if (strlen($username) < 3 || strlen($username) > 50) {
            $result['error'] = 'Username harus antara 3–50 karakter.';
            return $result;
        }
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
            $result['error'] = 'Username hanya boleh huruf, angka, underscore, dan strip.';
            return $result;
        }

        // Validasi password
        if (strlen($password) < 8) {
            $result['error'] = 'Password minimal 8 karakter.';
            return $result;
        }
        if (strlen($password) > 128) {
            $result['error'] = 'Password terlalu panjang.';
            return $result;
        }
        if ($password !== $confirmPassword) {
            $result['error'] = 'Konfirmasi password tidak cocok.';
            return $result;
        }

        // Cek duplikat username
        if ($this->isUsernameTaken($username)) {
            $result['error'] = 'Username sudah digunakan. Pilih username lain.';
            return $result;
        }

        // Hash password dan simpan
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "INSERT INTO pasien (username, password, role) VALUES (?, ?, 'pasien')"
        );

        if (!$stmt) {
            $result['error'] = 'Terjadi kesalahan server.';
            return $result;
        }

        $stmt->bind_param('ss', $username, $hashedPassword);

        if ($stmt->execute()) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Registrasi gagal. Silakan coba lagi.';
        }

        $stmt->close();
        return $result;
    }

    /**
     * Cek apakah username sudah dipakai.
     */
    private function isUsernameTaken(string $username): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id_pengguna FROM pasien WHERE username = ? LIMIT 1"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        $taken = $stmt->num_rows > 0;
        $stmt->close();

        return $taken;
    }

    // ─── Auth Guard ──────────────────────────────────────────

    /**
     * Redirect ke halaman yang sesuai jika sudah login.
     */
    public function redirectIfLoggedIn(): void
    {
        if ($this->isLoggedIn()) {
            if ($this->currentRole() === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: Home.php');
            }
            exit();
        }
    }

    /**
     * Proteksi halaman — redirect ke login jika belum login.
     * Opsional: bisa cek role tertentu.
     */
    public function guard(string $requiredRole = ''): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }

        if ($requiredRole !== '' && $this->currentRole() !== $requiredRole) {
            if ($this->currentRole() === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: Home.php');
            }
            exit();
        }
    }

    // ─── Logout ──────────────────────────────────────────────

    /**
     * Logout: hapus session, cookie, dan destroy.
     */
    public function logout(): void
    {
        $_SESSION = [];

        // Hapus cookie session jika ada
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
