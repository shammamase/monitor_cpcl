<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error_upbs'] = 'Username dan password wajib diisi.';
    header('Location: ' . base_url('page_upbs/login.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        u.id_user,
        u.nama_lengkap,
        u.username,
        u.password,
        u.provinsi_id,
        u.id_satker,
        u.is_active,
        p.name AS nama_provinsi,
        s.nama_satker,
        s.jenis_satker
    FROM users u
    LEFT JOIN provinsis p ON u.provinsi_id = p.id
    LEFT JOIN satker s ON u.id_satker = s.id_satker
    WHERE u.username = ?
      AND u.is_active = 1
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['login_error_upbs'] = 'Akun tidak ditemukan atau tidak aktif.';
    header('Location: ' . base_url('page_upbs/login.php'));
    exit;
}

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error_upbs'] = 'Password salah.';
    header('Location: ' . base_url('page_upbs/login.php'));
    exit;
}

$_SESSION['user_upbs'] = [
    'id_user'       => $user['id_user'],
    'nama_lengkap'  => $user['nama_lengkap'],
    'username'      => $user['username'],
    'provinsi_id'   => $user['provinsi_id'],
    'id_satker'     => $user['id_satker'],
    'nama_provinsi' => $user['nama_provinsi'] ?? '',
    'nama_satker'   => $user['nama_satker'] ?? '',
    'jenis_satker'  => $user['jenis_satker'] ?? '',
];

try {
    $stmtUpdate = $pdo->prepare("
        UPDATE users
        SET last_login = NOW(),
            updated_at = NOW()
        WHERE id_user = ?
    ");
    $stmtUpdate->execute([$user['id_user']]);
} catch (Throwable $e) {
    // abaikan jika kolom last_login belum ada
}

header('Location: ' . base_url('page_upbs/dashboard.php'));
exit;