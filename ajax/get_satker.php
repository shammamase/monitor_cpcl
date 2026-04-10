<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id = $_GET['provinsi_id'] ?? '';

echo '<option value="">-- Semua Satker --</option>';

if ($provinsi_id !== '') {
    $stmt = $pdo->prepare("
        SELECT id_satker, nama_satker
        FROM satker
        WHERE provinsi_id = ?
        ORDER BY nama_satker ASC
    ");
    $stmt->execute([$provinsi_id]);

    foreach ($stmt->fetchAll() as $row) {
        echo '<option value="' . e($row['id_satker']) . '">' . e($row['nama_satker']) . '</option>';
    }
}