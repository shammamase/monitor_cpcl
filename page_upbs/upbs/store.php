<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker         = (int)($userUpbs['id_satker'] ?? 0);
$kode_upbs         = trim($_POST['kode_upbs'] ?? '');
$nama_upbs         = trim($_POST['nama_upbs'] ?? '');
$jenis_upbs        = trim($_POST['jenis_upbs'] ?? '');
$no_hp_pengelola   = trim($_POST['no_hp_pengelola'] ?? '');
$is_active         = isset($_POST['is_active']) ? 1 : 0;

if ($id_satker <= 0) {
    $_SESSION['error_message_upbs'] = 'Satker user tidak valid.';
    header('Location: ' . base_url('page_upbs/upbs/index.php'));
    exit;
}

if ($kode_upbs === '' || $nama_upbs === '') {
    $_SESSION['error_message_upbs'] = 'Kode UPBS dan nama UPBS wajib diisi.';
    header('Location: ' . base_url('page_upbs/upbs/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM upbs
    WHERE id_satker = ?
      AND (nama_upbs = ?)
");
$stmtCheck->execute([$id_satker, $nama_upbs]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Nama UPBS sudah ada pada satker Anda.';
    header('Location: ' . base_url('page_upbs/upbs/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO upbs
    (
        id_satker,
        kode_upbs,
        nama_upbs,
        jenis_upbs,
        no_hp_pengelola,
        is_active,
        created_at,
        updated_at
    )
    VALUES
    (
        :id_satker,
        :kode_upbs,
        :nama_upbs,
        :jenis_upbs,
        :no_hp_pengelola,
        :is_active,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    'id_satker'        => $id_satker,
    'kode_upbs'        => $kode_upbs,
    'nama_upbs'        => $nama_upbs,
    'jenis_upbs'       => ($jenis_upbs !== '' ? $jenis_upbs : null),
    'no_hp_pengelola'  => ($no_hp_pengelola !== '' ? $no_hp_pengelola : null),
    'is_active'        => $is_active,
]);

$_SESSION['success_message_upbs'] = 'Data UPBS berhasil ditambahkan.';
header('Location: ' . base_url('page_upbs/upbs/index.php'));
exit;