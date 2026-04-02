<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_user = (int)($userLogin['id_user'] ?? 0);

$password_lama              = trim($_POST['password_lama'] ?? '');
$password_baru              = trim($_POST['password_baru'] ?? '');
$konfirmasi_password_baru   = trim($_POST['konfirmasi_password_baru'] ?? '');

if ($id_user <= 0) {
    $_SESSION['error_message'] = 'Session user tidak valid.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

if ($password_lama === '' || $password_baru === '' || $konfirmasi_password_baru === '') {
    $_SESSION['error_message'] = 'Semua field wajib diisi.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

if ($password_baru !== $konfirmasi_password_baru) {
    $_SESSION['error_message'] = 'Konfirmasi password baru tidak sama.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

if (strlen($password_baru) < 8) {
    $_SESSION['error_message'] = 'Password baru minimal 8 karakter.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_user, password, is_active
    FROM users
    WHERE id_user = ?
    LIMIT 1
");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

if (!$user || (int)$user['is_active'] !== 1) {
    $_SESSION['error_message'] = 'Akun tidak ditemukan atau tidak aktif.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

if (!password_verify($password_lama, $user['password'])) {
    $_SESSION['error_message'] = 'Password lama tidak sesuai.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

if (password_verify($password_baru, $user['password'])) {
    $_SESSION['error_message'] = 'Password baru tidak boleh sama dengan password lama.';
    header('Location: ' . base_url('page_cpcl/ubah_password.php'));
    exit;
}

$password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);

$stmtUpdate = $pdo->prepare("
    UPDATE users
    SET password = ?, updated_at = NOW()
    WHERE id_user = ?
");
$stmtUpdate->execute([$password_hash_baru, $id_user]);

$_SESSION['success_message'] = 'Password berhasil diubah.';
header('Location: ' . base_url('page_cpcl/ubah_password.php'));
exit;