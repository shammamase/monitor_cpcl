<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id            = (int)($_POST['id_varietas'] ?? 0);
$id_komoditas  = trim($_POST['id_komoditas'] ?? '');
$nama_varietas = trim($_POST['nama_varietas'] ?? '');
$jenis         = trim($_POST['jenis_varietas'] ?? '');
$is_active     = isset($_POST['is_active']) ? 1 : 0;

if ($id <= 0 || $id_komoditas === '' || $nama_varietas === '' || !in_array($jenis, ['varietas', 'galur'], true)) {
    $_SESSION['error_message_upbs'] = 'Data varietas/galur tidak valid.';
    header('Location: ' . base_url('page_upbs/varietas/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM varietas
    WHERE id_komoditas = ?
      AND nama_varietas = ?
      AND id_varietas != ?
");
$stmtCheck->execute([$id_komoditas, $nama_varietas, $id]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama varietas/galur untuk komoditas tersebut sudah digunakan.';
    header('Location: ' . base_url('page_upbs/varietas/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    UPDATE varietas
    SET
        id_komoditas = :id_komoditas,
        nama_varietas = :nama_varietas,
        jenis_varietas = :jenis_varietas,
        is_active = :is_active,
        updated_at = NOW()
    WHERE id_varietas = :id_varietas
");

$stmt->execute([
    'id_komoditas'   => $id_komoditas,
    'nama_varietas'  => $nama_varietas,
    'jenis_varietas' => $jenis,
    'is_active'      => $is_active,
    'id_varietas'    => $id,
]);

$_SESSION['success_message_upbs'] = 'Data varietas/galur berhasil diperbarui.';
header('Location: ' . base_url('page_upbs/varietas/index.php'));
exit;