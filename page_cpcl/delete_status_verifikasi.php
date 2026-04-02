<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id_user = (int)($userLogin['provinsi_id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($provinsi_id_user <= 0) {
    die('Provinsi user tidak valid.');
}

if ($id <= 0) {
    header('Location: ' . base_url('page_cpcl/dashboard_provinsi.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_status_verif
    FROM status_verifikasi
    WHERE id_status_verif = ?
      AND provinsi_id = ?
    LIMIT 1
");
$stmt->execute([$id, $provinsi_id_user]);
$data = $stmt->fetch();

if (!$data) {
    die('Data tidak ditemukan atau bukan milik provinsi Anda.');
}

try {
    $pdo->beginTransaction();

    $stmtRelasi = $pdo->prepare("
        DELETE FROM status_verifikasi_jenis_bantuan
        WHERE id_status_verif = ?
    ");
    $stmtRelasi->execute([$id]);

    $stmtDelete = $pdo->prepare("
        DELETE FROM status_verifikasi
        WHERE id_status_verif = ?
          AND provinsi_id = ?
    ");
    $stmtDelete->execute([$id, $provinsi_id_user]);

    $pdo->commit();

    header('Location: ' . base_url('page_cpcl/dashboard_provinsi.php'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Gagal menghapus data: ' . $e->getMessage());
}