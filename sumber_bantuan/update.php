<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_sumber   = (int)($_POST['id_sumber'] ?? 0);
$nama_sumber = trim($_POST['nama_sumber'] ?? '');

if ($id_sumber <= 0 || $nama_sumber === '') {
    die('Data tidak valid.');
}

$cek = $pdo->prepare("
    SELECT COUNT(*) AS total 
    FROM sumber_bantuan 
    WHERE nama_sumber = ? AND id_sumber != ?
");
$cek->execute([$nama_sumber, $id_sumber]);
$exists = $cek->fetch();

if ((int)$exists['total'] > 0) {
    die('Nama sumber bantuan sudah digunakan.');
}

$stmt = $pdo->prepare("
    UPDATE sumber_bantuan
    SET nama_sumber = ?, updated_at = NOW()
    WHERE id_sumber = ?
");
$stmt->execute([$nama_sumber, $id_sumber]);

header('Location: ' . base_url('?page=sumber_bantuan'));
exit;