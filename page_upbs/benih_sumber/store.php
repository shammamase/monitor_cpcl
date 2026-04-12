<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$nama_benih_sumber = trim($_POST['nama_benih_sumber'] ?? '');
$kategori          = trim($_POST['kategori_komoditas'] ?? '');
$is_active         = isset($_POST['is_active']) ? 1 : 0;

if ($nama_benih_sumber === '') {
    $_SESSION['error_message_upbs'] = 'Nama benih sumber wajib diisi.';
    header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
    exit;
}

if ($kategori !== '' && !in_array($kategori, ['tanaman', 'ternak'], true)) {
    $_SESSION['error_message_upbs'] = 'Kategori komoditas tidak valid.';
    header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM benih_sumber
    WHERE nama_benih_sumber = ?
");
$stmtCheck->execute([$nama_benih_sumber]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama benih sumber sudah ada.';
    header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO benih_sumber
    (
        nama_benih_sumber,
        kategori_komoditas,
        is_active,
        created_at,
        updated_at
    )
    VALUES
    (
        :nama_benih_sumber,
        :kategori_komoditas,
        :is_active,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    'nama_benih_sumber'   => $nama_benih_sumber,
    'kategori_komoditas'  => ($kategori !== '' ? $kategori : null),
    'is_active'           => $is_active,
]);

$_SESSION['success_message_upbs'] = 'Data benih sumber berhasil ditambahkan.';
header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
exit;