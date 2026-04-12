<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM benih_sumber WHERE id_benih_sumber = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message_upbs'] = 'Data benih sumber berhasil dihapus.';
} catch (Throwable $e) {
    $_SESSION['error_message_upbs'] = 'Data benih sumber tidak dapat dihapus karena kemungkinan sudah dipakai pada data lain.';
}

header('Location: ' . base_url('page_upbs/benih_sumber/index.php'));
exit;