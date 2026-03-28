<?php
require_once __DIR__ . '/../config/database.php';

$kabupaten_id = $_GET['kabupaten_id'] ?? '';

if (!$kabupaten_id) {
    echo '<option value="">-- Pilih Kecamatan --</option>';
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM kecamatans WHERE kabupaten_id = ? ORDER BY name ASC");
$stmt->execute([$kabupaten_id]);
$data = $stmt->fetchAll();

echo '<option value="">-- Pilih Kecamatan --</option>';
foreach ($data as $row) {
    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
}