<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id          = (int)($_POST['id_satuan'] ?? 0);
$nama_satuan = trim($_POST['nama_satuan'] ?? '');
$simbol      = trim($_POST['simbol'] ?? '');

if ($id <= 0 || $nama_satuan === '' || $simbol === '') {
    $_SESSION['error_message_upbs'] = 'Data satuan tidak valid.';
    header('Location: ' . base_url('page_upbs/satuan/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM satuan
    WHERE (nama_satuan = ? OR simbol = ?)
      AND id_satuan != ?
");
$stmtCheck->execute([$nama_satuan, $simbol, $id]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama satuan atau simbol sudah digunakan.';
    header('Location: ' . base_url('page_upbs/satuan/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    UPDATE satuan
    SET
        nama_satuan = :nama_satuan,
        simbol = :simbol
    WHERE id_satuan = :id_satuan
");

$stmt->execute([
    'nama_satuan' => $nama_satuan,
    'simbol'      => $simbol,
    'id_satuan'   => $id,
]);

$_SESSION['success_message_upbs'] = 'Data satuan berhasil diperbarui.';
header('Location: ' . base_url('page_upbs/satuan/index.php'));
exit;