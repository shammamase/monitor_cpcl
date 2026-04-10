<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$username     = trim($_POST['username'] ?? '');
$password     = trim($_POST['password'] ?? '');
$id_satker    = trim($_POST['id_satker'] ?? '');
$provinsi_id  = trim($_POST['provinsi_id'] ?? '');
$is_active    = isset($_POST['is_active']) ? 1 : 0;

if ($nama_lengkap === '' || $username === '' || $password === '' || $id_satker === '' || $provinsi_id === '') {
    die('Data wajib belum lengkap.');
}

if (strlen($password) < 8) {
    die('Password minimal 8 karakter.');
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
");
$stmtUser->execute([$username]);
$existsUser = $stmtUser->fetch();

if ((int)$existsUser['total'] > 0) {
    die('Username sudah digunakan.');
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users
    (
        nama_lengkap,
        username,
        password,
        provinsi_id,
        id_satker,
        is_active,
        created_at,
        updated_at
    )
    VALUES
    (
        :nama_lengkap,
        :username,
        :password,
        :provinsi_id,
        :id_satker,
        :is_active,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    'nama_lengkap' => $nama_lengkap,
    'username'     => $username,
    'password'     => $password_hash,
    'provinsi_id'  => $provinsi_id,
    'id_satker'    => $id_satker,
    'is_active'    => $is_active,
]);

header('Location: ' . base_url('indexyz.php?page=users'));
exit;