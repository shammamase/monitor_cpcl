<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . base_url('page_upbs/komoditas/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM komoditas WHERE id_komoditas = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message_upbs'] = 'Data komoditas berhasil dihapus.';
} catch (Throwable $e) {
    $_SESSION['error_message_upbs'] = 'Data komoditas tidak dapat dihapus karena kemungkinan sudah dipakai pada data lain.';
}

header('Location: ' . base_url('page_upbs/komoditas/index.php'));
exit;