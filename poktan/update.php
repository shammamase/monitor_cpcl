<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_poktan    = $_POST['id_poktan'] ?? 0;
$nama_poktan  = trim($_POST['nama_poktan'] ?? '');
$nama_ketua_poktan = trim($_POST['nama_ketua_poktan'] ?? '');
$alamat       = trim($_POST['alamat'] ?? '');
$provinsi_id  = $_POST['provinsi_id'] ?? '';
$kabupaten_id = $_POST['kabupaten_id'] ?? '';
$kecamatan_id = $_POST['kecamatan_id'] ?? '';

if ($id_poktan == 0 || $nama_poktan == '' || $nama_ketua_poktan == '' || $provinsi_id == '' || $kabupaten_id == '' || $kecamatan_id == '') {
    die('Data wajib belum lengkap.');
}

$sql = "UPDATE poktan 
        SET nama_poktan = :nama_poktan,
            nama_ketua_poktan = :nama_ketua_poktan,
            alamat = :alamat,
            provinsi_id = :provinsi_id,
            kabupaten_id = :kabupaten_id,
            kecamatan_id = :kecamatan_id,
            updated_at = NOW()
        WHERE id_poktan = :id_poktan";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'nama_poktan'  => $nama_poktan,
    'nama_ketua_poktan' => $nama_ketua_poktan,
    'alamat'       => $alamat,
    'provinsi_id'  => $provinsi_id,
    'kabupaten_id' => $kabupaten_id,
    'kecamatan_id' => $kecamatan_id,
    'id_poktan'    => $id_poktan,
]);

header('Location: ' . base_url('?page=poktan'));
exit;