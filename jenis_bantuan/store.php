<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_sumber          = trim($_POST['id_sumber'] ?? '');
$nama_jenis_bantuan = trim($_POST['nama_jenis_bantuan'] ?? '');

if ($id_sumber === '' || $nama_jenis_bantuan === '') {
    die('Sumber bantuan dan nama jenis bantuan wajib diisi.');
}

$cek = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM jenis_bantuan
    WHERE id_sumber = ? AND nama_jenis_bantuan = ?
");
$cek->execute([$id_sumber, $nama_jenis_bantuan]);
$exists = $cek->fetch();

if ((int)$exists['total'] > 0) {
    die('Jenis bantuan tersebut sudah ada pada sumber bantuan yang dipilih.');
}

$stmt = $pdo->prepare("
    INSERT INTO jenis_bantuan (id_sumber, nama_jenis_bantuan, created_at, updated_at)
    VALUES (?, ?, NOW(), NOW())
");
$stmt->execute([$id_sumber, $nama_jenis_bantuan]);

header('Location: ' . base_url('?page=jenis_bantuan'));
exit;