<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_jenis_bantuan   = (int)($_POST['id_jenis_bantuan'] ?? 0);
$id_sumber          = trim($_POST['id_sumber'] ?? '');
$nama_jenis_bantuan = trim($_POST['nama_jenis_bantuan'] ?? '');

if ($id_jenis_bantuan <= 0 || $id_sumber === '' || $nama_jenis_bantuan === '') {
    die('Data tidak valid.');
}

$cek = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM jenis_bantuan
    WHERE id_sumber = ? 
      AND nama_jenis_bantuan = ?
      AND id_jenis_bantuan != ?
");
$cek->execute([$id_sumber, $nama_jenis_bantuan, $id_jenis_bantuan]);
$exists = $cek->fetch();

if ((int)$exists['total'] > 0) {
    die('Jenis bantuan tersebut sudah digunakan pada sumber bantuan yang dipilih.');
}

$stmt = $pdo->prepare("
    UPDATE jenis_bantuan
    SET id_sumber = ?,
        nama_jenis_bantuan = ?,
        updated_at = NOW()
    WHERE id_jenis_bantuan = ?
");
$stmt->execute([$id_sumber, $nama_jenis_bantuan, $id_jenis_bantuan]);

header('Location: ' . base_url('?page=jenis_bantuan'));
exit;