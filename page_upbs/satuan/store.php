<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$nama_satuan = trim($_POST['nama_satuan'] ?? '');
$simbol      = trim($_POST['simbol'] ?? '');

if ($nama_satuan === '' || $simbol === '') {
    $_SESSION['error_message_upbs'] = 'Nama satuan dan simbol wajib diisi.';
    header('Location: ' . base_url('page_upbs/satuan/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM satuan
    WHERE nama_satuan = ?
       OR simbol = ?
");
$stmtCheck->execute([$nama_satuan, $simbol]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama satuan atau simbol sudah ada.';
    header('Location: ' . base_url('page_upbs/satuan/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO satuan
    (
        nama_satuan,
        simbol
    )
    VALUES
    (
        :nama_satuan,
        :simbol
    )
");

$stmt->execute([
    'nama_satuan' => $nama_satuan,
    'simbol'      => $simbol,
]);

$_SESSION['success_message_upbs'] = 'Data satuan berhasil ditambahkan.';
header('Location: ' . base_url('page_upbs/satuan/index.php'));
exit;