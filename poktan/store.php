<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$nama_poktan  = trim($_POST['nama_poktan'] ?? '');
$nama_ketua_poktan = trim($_POST['nama_ketua_poktan'] ?? '');
$alamat       = trim($_POST['alamat'] ?? '');
$provinsi_id  = $_POST['provinsi_id'] ?? '';
$kabupaten_id = $_POST['kabupaten_id'] ?? '';
$kecamatan_id = $_POST['kecamatan_id'] ?? '';

if ($nama_poktan == '' || $nama_ketua_poktan == '' || $provinsi_id == '' || $kabupaten_id == '' || $kecamatan_id == '') {
    die('Data wajib belum lengkap.');
}

$sql = "INSERT INTO poktan (nama_poktan, nama_ketua_poktan, alamat, provinsi_id, kabupaten_id, kecamatan_id, created_at, updated_at)
        VALUES (:nama_poktan, :nama_ketua_poktan, :alamat, :provinsi_id, :kabupaten_id, :kecamatan_id, NOW(), NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'nama_poktan'  => $nama_poktan,
    'nama_ketua_poktan' => $nama_ketua_poktan,
    'alamat'       => $alamat,
    'provinsi_id'  => $provinsi_id,
    'kabupaten_id' => $kabupaten_id,
    'kecamatan_id' => $kecamatan_id,
]);

header('Location: ' . base_url('?page=poktan'));
exit;