<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_satker    = (int)($_POST['id_satker'] ?? 0);
$kode_satker  = trim($_POST['kode_satker'] ?? '');
$nama_satker  = trim($_POST['nama_satker'] ?? '');
$jenis_satker = trim($_POST['jenis_satker'] ?? '');
$provinsi_id  = trim($_POST['provinsi_id'] ?? '');
$kabupaten_id = trim($_POST['kabupaten_id'] ?? '');
$alamat       = trim($_POST['alamat'] ?? '');
$is_active    = isset($_POST['is_active']) ? 1 : 0;

if ($id_satker <= 0 || $nama_satker === '' || $provinsi_id === '' || $kabupaten_id === '') {
    die('Data tidak valid.');
}

$stmtCekKab = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM kabupatens
    WHERE id = ?
      AND provinsi_id = ?
");
$stmtCekKab->execute([$kabupaten_id, $provinsi_id]);
$cekKab = $stmtCekKab->fetch();

if ((int)$cekKab['total'] === 0) {
    die('Kabupaten tidak sesuai dengan provinsi yang dipilih.');
}

if ($kode_satker !== '') {
    $stmtCekKode = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM satker
        WHERE kode_satker = ?
          AND id_satker != ?
    ");
    $stmtCekKode->execute([$kode_satker, $id_satker]);
    $existsKode = $stmtCekKode->fetch();
    /*
    if ((int)$existsKode['total'] > 0) {
        die('Kode satker sudah digunakan.');
    }
    */
}

$stmt = $pdo->prepare("
    UPDATE satker
    SET
        kode_satker = :kode_satker,
        nama_satker = :nama_satker,
        jenis_satker = :jenis_satker,
        provinsi_id = :provinsi_id,
        kabupaten_id = :kabupaten_id,
        alamat = :alamat,
        is_active = :is_active,
        updated_at = NOW()
    WHERE id_satker = :id_satker
");

$stmt->execute([
    'kode_satker'  => ($kode_satker !== '' ? $kode_satker : null),
    'nama_satker'  => $nama_satker,
    'jenis_satker' => ($jenis_satker !== '' ? $jenis_satker : null),
    'provinsi_id'  => $provinsi_id,
    'kabupaten_id' => $kabupaten_id,
    'alamat'       => ($alamat !== '' ? $alamat : null),
    'is_active'    => $is_active,
    'id_satker'    => $id_satker,
]);

header('Location: ' . base_url('indexyz.php?page=satker'));
exit;