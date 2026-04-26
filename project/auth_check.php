<?php
/**
 * auth_check.php
 * Helper sederhana untuk memproteksi halaman.
 * 
 * Cara pakai:
 *   require_once "auth_check.php"; authGuard();         // wajib login (role apa pun)
 *   require_once "auth_check.php"; authGuard('admin');  // wajib login sebagai admin
 *   require_once "auth_check.php"; authGuard('pasien'); // wajib login sebagai pasien
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function authGuard(string $requiredRole = '') {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }
    if ($requiredRole !== '' && $_SESSION['user']['role'] !== $requiredRole) {
        // Sudah login tapi role salah — redirect ke halaman sesuai role
        if ($_SESSION['user']['role'] === 'admin') {
            header("Location: index.php");
        } else {
            header("Location: Home.php");
        }
        exit();
    }
}

/**
 * Cek apakah user sudah login tanpa redirect.
 * Return: true jika sudah login, false jika belum.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

/**
 * Ambil data user dari session.
 * Return: array user atau null.
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}
