<?php
class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function currentRole(): string
    {
        return $_SESSION['user']['role'] ?? '';
    }


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
            $passwordValid = true;
        } elseif ($password === $row['password']) {
            $passwordValid = true;
            $this->upgradePasswordHash($row['id_pengguna'], $password);
        }

        if (!$passwordValid) {
            $result['error'] = 'Username atau password salah.';
            return $result;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $row;


        $result['success'] = true;
        $result['role']    = $row['role'];
        return $result;
    }

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

    public function register(string $username, string $password, string $confirmPassword, string $noHp = ''): array
    {
        $result = ['success' => false, 'error' => ''];

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

        if (strlen($password) < 3) {
            $result['error'] = 'Password minimal 3 karakter.';
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

        if ($this->isUsernameTaken($username)) {
            $result['error'] = 'Username sudah digunakan. Pilih username lain.';
            return $result;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "INSERT INTO pasien (username, password, no_hp, role) VALUES (?, ?, ?, 'pasien')"
        );

        if (!$stmt) {
            $result['error'] = 'Terjadi kesalahan server.';
            return $result;
        }

        $stmt->bind_param('sss', $username, $hashedPassword, $noHp);

        if ($stmt->execute()) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Registrasi gagal. Silakan coba lagi.';
        }

        $stmt->close();
        return $result;
    }

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

    public function redirectIfLoggedIn(): void
    {
        if ($this->isLoggedIn()) {
            if ($this->currentRole() === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: Home.php');
            }
            exit();
        }
    }

    public function guard(string $requiredRole = ''): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }

        if ($requiredRole !== '' && $this->currentRole() !== $requiredRole) {
            if ($this->currentRole() === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: Home.php');
            }
            exit();
        }
    }

    public function logout(): void
    {
        $_SESSION = [];

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