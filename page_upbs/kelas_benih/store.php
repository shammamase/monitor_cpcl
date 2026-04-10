<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$kode_kelas = trim($_POST['kode_kelas'] ?? '');
$nama_kelas = trim($_POST['nama_kelas'] ?? '');
$is_active  = isset($_POST['is_active']) ? 1 : 0;

if ($kode_kelas === '') {
    $_SESSION['error_message_upbs'] = 'Kode kelas wajib diisi.';
    header('Location: ' . base_url('page_upbs/kelas_benih/index.php'));
    exit;
}

$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM kelas_benih
    WHERE kode_kelas = ?
");
$stmtCheck->execute([$kode_kelas]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Kode kelas sudah ada.';
    header('Location: ' . base_url('page_upbs/kelas_benih/index.php'));
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO kelas_benih
    (
        kode_kelas,
        nama_kelas,
        is_active,
        created_at,
        updated_at
    )
    VALUES
    (
        :kode_kelas,
        :nama_kelas,
        :is_active,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    'kode_kelas' => $kode_kelas,
    'nama_kelas' => ($nama_kelas !== '' ? $nama_kelas : null),
    'is_active'  => $is_active,
]);

$_SESSION['success_message_upbs'] = 'Data kelas benih berhasil ditambahkan.';
header('Location: ' . base_url('page_upbs/kelas_benih/index.php'));
exit;