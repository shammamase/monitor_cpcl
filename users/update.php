<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_user      = (int)($_POST['id_user'] ?? 0);
$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$username     = trim($_POST['username'] ?? '');
$password     = trim($_POST['password'] ?? '');
$id_satker    = trim($_POST['id_satker'] ?? '');
$provinsi_id  = trim($_POST['provinsi_id'] ?? '');
$is_active    = isset($_POST['is_active']) ? 1 : 0;

if ($id_user <= 0 || $nama_lengkap === '' || $username === '' || $id_satker === '' || $provinsi_id === '') {
    die('Data tidak valid.');
}

$stmtSatker = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM satker
    WHERE id_satker = ?
      AND provinsi_id = ?
");
$stmtSatker->execute([$id_satker, $provinsi_id]);
$cekSatker = $stmtSatker->fetch();

if ((int)$cekSatker['total'] === 0) {
    die('Satker tidak sesuai dengan provinsi.');
}

$stmtUser = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM users
    WHERE username = ?
      AND id_user != ?
");
$stmtUser->execute([$username, $id_user]);
$existsUser = $stmtUser->fetch();

if ((int)$existsUser['total'] > 0) {
    die('Username sudah digunakan.');
}

if ($password !== '' && strlen($password) < 8) {
    die('Password baru minimal 8 karakter.');
}

if ($password !== '') {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users
        SET
            nama_lengkap = :nama_lengkap,
            username = :username,
            password = :password,
            provinsi_id = :provinsi_id,
            id_satker = :id_satker,
            is_active = :is_active,
            updated_at = NOW()
        WHERE id_user = :id_user
    ");

    $stmt->execute([
        'nama_lengkap' => $nama_lengkap,
        'username'     => $username,
        'password'     => $password_hash,
        'provinsi_id'  => $provinsi_id,
        'id_satker'    => $id_satker,
        'is_active'    => $is_active,
        'id_user'      => $id_user,
    ]);
} else {
    $stmt = $pdo->prepare("
        UPDATE users
        SET
            nama_lengkap = :nama_lengkap,
            username = :username,
            provinsi_id = :provinsi_id,
            id_satker = :id_satker,
            is_active = :is_active,
            updated_at = NOW()
        WHERE id_user = :id_user
    ");

    $stmt->execute([
        'nama_lengkap' => $nama_lengkap,
        'username'     => $username,
        'provinsi_id'  => $provinsi_id,
        'id_satker'    => $id_satker,
        'is_active'    => $is_active,
        'id_user'      => $id_user,
    ]);
}

header('Location: ' . base_url('indexyz.php?page=users'));
exit;