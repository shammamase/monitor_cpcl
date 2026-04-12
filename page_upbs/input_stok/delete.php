<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker = (int)($userUpbs['id_satker'] ?? 0);
$id_laporan_stok = (int)($_GET['id'] ?? 0);

if ($id_satker <= 0 || $id_laporan_stok <= 0) {
    $_SESSION['error_message_upbs'] = 'Data laporan stok tidak valid.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Pastikan laporan milik satker user
|--------------------------------------------------------------------------
*/
$stmtCheck = $pdo->prepare("
    SELECT
        l.id_laporan_stok
    FROM laporan_stok_upbs l
    INNER JOIN upbs u ON l.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    WHERE l.id_laporan_stok = ?
      AND s.id_satker = ?
    LIMIT 1
");
$stmtCheck->execute([$id_laporan_stok, $id_satker]);
$data = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error_message_upbs'] = 'Laporan stok tidak ditemukan atau bukan milik satker Anda.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtDeleteDetail = $pdo->prepare("
        DELETE FROM laporan_stok_upbs_detail
        WHERE id_laporan_stok = ?
    ");
    $stmtDeleteDetail->execute([$id_laporan_stok]);

    $stmtDeleteHeader = $pdo->prepare("
        DELETE FROM laporan_stok_upbs
        WHERE id_laporan_stok = ?
    ");
    $stmtDeleteHeader->execute([$id_laporan_stok]);

    $pdo->commit();

    $_SESSION['success_message_upbs'] = 'Laporan stok berhasil dihapus.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_message_upbs'] = 'Gagal menghapus laporan stok: ' . $e->getMessage();
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}