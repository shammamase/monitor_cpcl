<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM satker WHERE id_satker = ?");
    $stmt->execute([$id]);
}

header('Location: ' . base_url('indexyz.php?page=satker'));
exit;