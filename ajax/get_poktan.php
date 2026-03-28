<?php
require_once __DIR__ . '/../config/database.php';

$kecamatan_id = $_GET['kecamatan_id'] ?? '';

if (!$kecamatan_id) {
    echo '<option value="">-- Pilih Poktan --</option>';
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_poktan, nama_poktan 
    FROM poktan 
    WHERE kecamatan_id = ?
    ORDER BY nama_poktan ASC
");
$stmt->execute([$kecamatan_id]);
$data = $stmt->fetchAll();

echo '<option value="">-- Pilih Poktan --</option>';
foreach ($data as $row) {
    echo '<option value="' . $row['id_poktan'] . '">' . htmlspecialchars($row['nama_poktan']) . '</option>';
}