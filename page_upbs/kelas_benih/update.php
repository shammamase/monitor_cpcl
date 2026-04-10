<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id         = (int)($_POST['id_kelas_benih'] ?? 0);
$kode_kelas = trim($_POST['kode_kelas'] ?? '');
$nama_kelas = trim($_POST['nama_kelas'] ?? '');
$is_active  = isset($_POST['is_active']) ? 1 : 0;

if ($id <= 0 || $kode_kelas === '') {
    $_SESSION['error_message_upbs'] = 'Data kelas benih tidak valid.';
    header('Location: ' . base_url('page_upbs/kelas_benih/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM kelas_benih
    WHERE kode_kelas = ?
      AND id_kelas_benih != ?
");
$stmtCheck->execute([$kode_kelas, $id]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Kode kelas sudah digunakan.';
    header('Location: ' . base_url('page_upbs/kelas_benih/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    UPDATE kelas_benih
    SET
        kode_kelas = :kode_kelas,
        nama_kelas = :nama_kelas,
        is_active = :is_active,
        updated_at = NOW()
    WHERE id_kelas_benih = :id_kelas_benih
");

$stmt->execute([
    'kode_kelas'      => $kode_kelas,
    'nama_kelas'      => ($nama_kelas !== '' ? $nama_kelas : null),
    'is_active'       => $is_active,
    'id_kelas_benih'  => $id,
]);

$_SESSION['success_message_upbs'] = 'Data kelas benih berhasil diperbarui.';
header('Location: ' . base_url('page_upbs/kelas_benih/index.php'));
exit;