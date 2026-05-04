<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/Auth.php';

Auth::startSession();

$auth = new Auth();
$auth->logout();

// Redirect ke halaman Home
header("Location: Home.php");
exit();