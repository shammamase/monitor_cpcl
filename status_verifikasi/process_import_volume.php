<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$payload = $_POST['payload'] ?? '';
$rows = [];
$messages = [];
$success = 0;
$failed = 0;

if ($payload === '') {
    $messages[] = [
        'type' => 'danger',
        'text' => 'Payload import kosong. Silakan preview ulang file Excel.',
    ];
} else {
    $decoded = json_decode(base64_decode($payload, true), true);

    if (!is_array($decoded)) {
        $messages[] = [
            'type' => 'danger',
            'text' => 'Payload import tidak valid. Silakan preview ulang file Excel.',
        ];
    } else {
        $rows = $decoded;
    }
}

if ($rows) {
    $stmtCheck = $pdo->prepare("
        SELECT id_status_verif, status_verifikasi, volume, satuan
        FROM status_verifikasi
        WHERE id_status_verif = ?
        LIMIT 1
    ");

    $stmtUpdate = $pdo->prepare("
        UPDATE status_verifikasi
        SET
            volume = CASE WHEN volume IS NULL THEN :volume ELSE volume END,
            satuan = CASE WHEN satuan IS NULL OR satuan = '' THEN :satuan ELSE satuan END,
            updated_at = NOW()
        WHERE id_status_verif = :id_status_verif
          AND status_verifikasi = 1
          AND (volume IS NULL OR satuan IS NULL OR satuan = '')
    ");

    foreach ($rows as $row) {
        $idStatus = (int)($row['id_status_verif'] ?? 0);
        $volume = trim((string)($row['volume'] ?? ''));
        $satuan = trim((string)($row['satuan'] ?? ''));

        if ($idStatus <= 0 || $volume === '' || !is_numeric($volume) || (float)$volume <= 0 || $satuan === '') {
            $failed++;
            continue;
        }

        $stmtCheck->execute([$idStatus]);
        $existing = $stmtCheck->fetch();

        if (!$existing || (int)$existing['status_verifikasi'] !== 1) {
            $failed++;
            continue;
        }

        if ($existing['volume'] !== null && $existing['satuan'] !== null && trim((string)$existing['satuan']) !== '') {
            $failed++;
            continue;
        }

        $stmtUpdate->execute([
            'volume' => $volume,
            'satuan' => $satuan,
            'id_status_verif' => $idStatus,
        ]);

        if ($stmtUpdate->rowCount() > 0) {
            $success++;
        } else {
            $failed++;
        }
    }

    $messages[] = [
        'type' => $failed > 0 ? 'warning' : 'success',
        'text' => 'Import selesai. Berhasil update: ' . number_format($success) . '. Gagal/terlewati: ' . number_format($failed) . '.',
    ];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Import Volume - Monitoring CPCL</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Hasil Import Volume dan Unit</h4>

            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?= e($message['type']) ?>"><?= e($message['text']) ?></div>
            <?php endforeach; ?>

            <div class="d-flex gap-2">
                <a href="<?= base_url('status_verifikasi/import_volume.php') ?>" class="btn btn-primary">Import Lagi</a>
                <a href="<?= base_url('index.php?page=status_verifikasi') ?>" class="btn btn-secondary">Kembali ke Daftar</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
