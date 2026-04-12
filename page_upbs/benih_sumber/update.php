<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id                = (int)($_POST['id_benih_sumber'] ?? 0);
$nama_benih_sumber = trim($_POST['nama_benih_sumber'] ?? '');
$kategori          = trim($_POST['kategori_komoditas'] ?? '');
$is_active         = isset($_POST['is_active']) ? 1 : 0;

if ($id <= 0 || $nama_benih_sumber === '') {
    $_SESSION['error_message_upbs'] = 'Data benih sumber tidak valid.';
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
      AND id_benih_sumber != ?
");
$stmtCheck->execute([$nama_benih_sumber, $id]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama benih sumber sudah digunakan.';
    header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    UPDATE benih_sumber
    SET
        nama_benih_sumber = :nama_benih_sumber,
        kategori_komoditas = :kategori_komoditas,
        is_active = :is_active,
        updated_at = NOW()
    WHERE id_benih_sumber = :id_benih_sumber
");

$stmt->execute([
    'nama_benih_sumber'   => $nama_benih_sumber,
    'kategori_komoditas'  => ($kategori !== '' ? $kategori : null),
    'is_active'           => $is_active,
    'id_benih_sumber'     => $id,
]);

$_SESSION['success_message_upbs'] = 'Data benih sumber berhasil diperbarui.';
header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
exit;