<?php
require_once __DIR__ . '/../config/database.php';

$provinsi_id = $_GET['provinsi_id'] ?? '';

if (!$provinsi_id) {
    echo '<option value="">-- Pilih Kabupaten --</option>';
    exit;
}

$stmt = $pdo->prepare("SELECT id, type, name FROM kabupatens WHERE provinsi_id = ? ORDER BY name ASC");
$stmt->execute([$provinsi_id]);
$data = $stmt->fetchAll();

echo '<option value="">-- Pilih Kabupaten --</option>';
foreach ($data as $row) {
    echo '<option value="' . $row['id'] . '">'. htmlspecialchars($row['type']) .' '. htmlspecialchars($row['name']) . '</option>';
}