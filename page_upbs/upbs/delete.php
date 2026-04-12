<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker = (int)($userUpbs['id_satker'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($id_satker <= 0 || $id <= 0) {
    header('Location: ' . base_url('page_upbs/upbs/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM upbs
        WHERE id_upbs = ?
          AND id_satker = ?
    ");
    $stmt->execute([$id, $id_satker]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message_upbs'] = 'Data UPBS berhasil dihapus.';
    } else {
        $_SESSION['error_message_upbs'] = 'Data UPBS tidak ditemukan atau tidak dapat dihapus.';
    }
} catch (Throwable $e) {
    $_SESSION['error_message_upbs'] = 'Data UPBS tidak dapat dihapus karena kemungkinan sudah dipakai pada data lain.';
}

header('Location: ' . base_url('page_upbs/upbs/index.php'));
exit;