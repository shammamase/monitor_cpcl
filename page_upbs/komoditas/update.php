<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id             = (int)($_POST['id_komoditas'] ?? 0);
$nama_komoditas = trim($_POST['nama_komoditas'] ?? '');
$kategori       = trim($_POST['kategori_komoditas'] ?? '');
$is_active      = isset($_POST['is_active']) ? 1 : 0;

if ($id <= 0 || $nama_komoditas === '' || !in_array($kategori, ['tanaman', 'ternak'], true)) {
    $_SESSION['error_message_upbs'] = 'Data komoditas tidak valid.';
    header('Location: ' . base_url('page_upbs/komoditas/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM komoditas
    WHERE nama_komoditas = ?
      AND id_komoditas != ?
");
$stmtCheck->execute([$nama_komoditas, $id]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama komoditas sudah digunakan.';
    header('Location: ' . base_url('page_upbs/komoditas/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    UPDATE komoditas
    SET
        nama_komoditas = :nama_komoditas,
        kategori_komoditas = :kategori_komoditas,
        is_active = :is_active,
        updated_at = NOW()
    WHERE id_komoditas = :id_komoditas
");

$stmt->execute([
    'nama_komoditas'      => $nama_komoditas,
    'kategori_komoditas'  => $kategori,
    'is_active'           => $is_active,
    'id_komoditas'        => $id,
]);

$_SESSION['success_message_upbs'] = 'Data komoditas berhasil diperbarui.';
header('Location: ' . base_url('page_upbs/komoditas/index.php'));
exit;