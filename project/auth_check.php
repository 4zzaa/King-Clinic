<?php
/**
 * auth_check.php
 * Helper untuk memproteksi halaman — sekarang menggunakan class Auth.
 *
 * Cara pakai:
 *   require_once "auth_check.php"; authGuard();         // wajib login (role apa pun)
 *   require_once "auth_check.php"; authGuard('admin');  // wajib login sebagai admin
 *   require_once "auth_check.php"; authGuard('pasien'); // wajib login sebagai pasien
 */

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/Auth.php';

Auth::startSession();

/**
 * Proteksi halaman — redirect ke login jika belum login.
 * Wrapper fungsi prosedural agar file lama yang memanggil authGuard() tetap berjalan.
 */
function authGuard(string $requiredRole = ''): void
{
    $auth = new Auth();
    $auth->guard($requiredRole);
}

/**
 * Cek apakah user sudah login tanpa redirect.
 */
function isLoggedIn(): bool
{
    $auth = new Auth();
    return $auth->isLoggedIn();
}

/**
 * Ambil data user dari session.
 */
function currentUser(): ?array
{
    $auth = new Auth();
    return $auth->currentUser();
}
