<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo->beginTransaction();

    // Ambil data status_verifikasi yang masih null dan masih punya id_poktan
    $stmt = $pdo->query("
        SELECT 
            sv.id_status_verif,
            sv.id_poktan,
            sv.provinsi_id,
            sv.kabupaten_id,
            p.provinsi_id AS poktan_provinsi_id,
            p.kabupaten_id AS poktan_kabupaten_id
        FROM status_verifikasi sv
        INNER JOIN poktan p ON sv.id_poktan = p.id_poktan
        WHERE (sv.provinsi_id IS NULL OR sv.kabupaten_id IS NULL)
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "Tidak ada data yang perlu diperbarui.\n";
        $pdo->commit();
        exit;
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE status_verifikasi
        SET 
            provinsi_id = :provinsi_id,
            kabupaten_id = :kabupaten_id,
            updated_at = NOW()
        WHERE id_status_verif = :id_status_verif
    ");

    $updated = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $idStatusVerif = (int)$row['id_status_verif'];
        $provinsiId = $row['poktan_provinsi_id'] ?? null;
        $kabupatenId = $row['poktan_kabupaten_id'] ?? null;

        if (empty($provinsiId) || empty($kabupatenId)) {
            $skipped++;
            echo "Lewati ID {$idStatusVerif}: provinsi/kabupaten pada poktan tidak lengkap.\n";
            continue;
        }

        $stmtUpdate->execute([
            'provinsi_id' => $provinsiId,
            'kabupaten_id' => $kabupatenId,
            'id_status_verif' => $idStatusVerif,
        ]);

        $updated++;
    }

    $pdo->commit();

    echo "Selesai.\n";
    echo "Berhasil diperbarui: {$updated}\n";
    echo "Dilewati: {$skipped}\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "Gagal: " . $e->getMessage() . "\n";
}