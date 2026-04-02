<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM status_verifikasi WHERE id_status_verif = ?");
    $stmt->execute([$id]);
}

header('Location: ' . base_url('?page=status_verifikasi'));
exit;