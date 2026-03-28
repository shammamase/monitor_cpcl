<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$provinsi_id = $_GET['provinsi_id'] ?? '';

if (!$provinsi_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM kabupatens WHERE provinsi_id = ? ORDER BY name ASC");
$stmt->execute([$provinsi_id]);

echo json_encode($stmt->fetchAll());