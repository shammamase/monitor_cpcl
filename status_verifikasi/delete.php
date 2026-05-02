<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = $_GET['id'] ?? 0;
$redirect = $_GET['redirect'] ?? base_url('?page=status_verifikasi');
$basePath = base_url();

if (!is_string($redirect) || strpos($redirect, $basePath) !== 0) {
    $redirect = base_url('?page=status_verifikasi');
}

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM status_verifikasi WHERE id_status_verif = ?");
    $stmt->execute([$id]);
}

header('Location: ' . $redirect);
exit;
