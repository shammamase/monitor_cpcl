<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header('Location: ' . base_url('login.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.*, p.name AS nama_provinsi
    FROM users u
    LEFT JOIN provinsis p ON u.provinsi_id = p.id
    WHERE u.username = ?
      AND u.is_active = 1
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['login_error'] = 'Akun tidak ditemukan atau tidak aktif.';
    header('Location: ' . base_url('page_cpcl/login.php'));
    exit;
}

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = 'Password salah.';
    header('Location: ' . base_url('page_cpcl/login.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Simpan session login
|--------------------------------------------------------------------------
*/
$_SESSION['user_login'] = [
    'id_user'       => $user['id_user'],
    'nama_lengkap'  => $user['nama_lengkap'],
    'username'      => $user['username'],
    'provinsi_id'   => $user['provinsi_id'],
    'nama_provinsi' => $user['nama_provinsi'] ?? '',
];

/*
|--------------------------------------------------------------------------
| Update last_login jika kolom tersedia
|--------------------------------------------------------------------------
*/
try {
    $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
    $stmtUpdate->execute([$user['id_user']]);
} catch (Throwable $e) {
    // abaikan kalau kolom last_login belum ada
}

header('Location: ' . base_url('page_cpcl/dashboard_provinsi.php'));
exit;