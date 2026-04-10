<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$nama_komoditas = trim($_POST['nama_komoditas'] ?? '');
$kategori       = trim($_POST['kategori_komoditas'] ?? '');
$is_active      = isset($_POST['is_active']) ? 1 : 0;

if ($nama_komoditas === '' || !in_array($kategori, ['tanaman', 'ternak'], true)) {
    $_SESSION['error_message_upbs'] = 'Nama komoditas dan kategori wajib diisi.';
    header('Location: ' . base_url('page_upbs/komoditas/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM komoditas
    WHERE nama_komoditas = ?
");
$stmtCheck->execute([$nama_komoditas]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama komoditas sudah ada.';
    header('Location: ' . base_url('page_upbs/komoditas/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO komoditas
    (
        nama_komoditas,
        kategori_komoditas,
        is_active,
        created_at,
        updated_at
    )
    VALUES
    (
        :nama_komoditas,
        :kategori_komoditas,
        :is_active,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    'nama_komoditas'      => $nama_komoditas,
    'kategori_komoditas'  => $kategori,
    'is_active'           => $is_active,
]);

$_SESSION['success_message_upbs'] = 'Data komoditas berhasil ditambahkan.';
header('Location: ' . base_url('page_upbs/komoditas/index.php'));
exit;