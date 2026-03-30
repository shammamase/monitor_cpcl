<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$nama_sumber = trim($_POST['nama_sumber'] ?? '');

if ($nama_sumber === '') {
    die('Nama sumber bantuan wajib diisi.');
}

$cek = $pdo->prepare("SELECT COUNT(*) AS total FROM sumber_bantuan WHERE nama_sumber = ?");
$cek->execute([$nama_sumber]);
$exists = $cek->fetch();

if ((int)$exists['total'] > 0) {
    die('Nama sumber bantuan sudah ada.');
}

$stmt = $pdo->prepare("
    INSERT INTO sumber_bantuan (nama_sumber, created_at, updated_at)
    VALUES (?, NOW(), NOW())
");
$stmt->execute([$nama_sumber]);

header('Location: ' . base_url('?page=sumber_bantuan'));
exit;