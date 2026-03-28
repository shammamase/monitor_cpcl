<?php
require_once __DIR__ . '/../config/database.php';

$id_sumber = $_GET['id_sumber'] ?? '';

if (!$id_sumber) {
    echo '';
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_jenis_bantuan, nama_jenis_bantuan
    FROM jenis_bantuan
    WHERE id_sumber = ?
    ORDER BY nama_jenis_bantuan ASC
");
$stmt->execute([$id_sumber]);
$data = $stmt->fetchAll();

foreach ($data as $row) {
    echo '<option value="' . $row['id_jenis_bantuan'] . '">' . htmlspecialchars($row['nama_jenis_bantuan']) . '</option>';
}