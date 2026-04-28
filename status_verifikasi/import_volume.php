<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$errors = [];
$previewRows = [];
$validRows = [];
$uploadedName = '';

function normalizeImportVolume($value)
{
    $value = trim((string)$value);

    if ($value === '') {
        return null;
    }

    $value = preg_replace('/\s+/', '', $value);

    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (substr_count($value, '.') > 1) {
        $value = str_replace('.', '', $value);
    }

    return $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['file_excel']['tmp_name']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File Excel wajib diupload.';
    } else {
        $uploadedName = $_FILES['file_excel']['name'] ?? '';
        $extension = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            $errors[] = 'Format file harus .xlsx atau .xls.';
        }
    }

    if (!$errors) {
        try {
            $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            $stmtFind = $pdo->prepare("
                SELECT
                    sv.id_status_verif,
                    sv.status_verifikasi,
                    sv.volume,
                    sv.satuan,
                    pr.name AS provinsi,
                    kb.type AS kabupaten_type,
                    kb.name AS kabupaten,
                    sb.nama_sumber
                FROM status_verifikasi sv
                LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
                LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
                LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
                WHERE sv.id_status_verif = ?
                LIMIT 1
            ");

            for ($row = 5; $row <= $highestRow; $row++) {
                $idStatus = trim((string)$sheet->getCell('A' . $row)->getCalculatedValue());
                $volumeRaw = $sheet->getCell('F' . $row)->getFormattedValue();
                $satuan = trim((string)$sheet->getCell('G' . $row)->getCalculatedValue());

                if ($idStatus === '' && trim((string)$volumeRaw) === '' && $satuan === '') {
                    continue;
                }

                $rowErrors = [];
                $dbRow = null;
                $volume = normalizeImportVolume($volumeRaw);

                if ($idStatus === '' || !ctype_digit($idStatus)) {
                    $rowErrors[] = 'id_status_verif tidak valid.';
                } else {
                    $stmtFind->execute([(int)$idStatus]);
                    $dbRow = $stmtFind->fetch();

                    if (!$dbRow) {
                        $rowErrors[] = 'id_status_verif tidak ditemukan.';
                    } elseif ((int)$dbRow['status_verifikasi'] !== 1) {
                        $rowErrors[] = 'Status verifikasi bukan Sudah.';
                    } elseif ($dbRow['volume'] !== null && $dbRow['satuan'] !== null && trim((string)$dbRow['satuan']) !== '') {
                        $rowErrors[] = 'Volume dan unit sudah terisi, tidak akan dioverwrite.';
                    }
                }

                if ($volume === null || !is_numeric($volume) || (float)$volume <= 0) {
                    $rowErrors[] = 'Volume wajib angka lebih dari 0.';
                }

                if ($satuan === '') {
                    $rowErrors[] = 'Unit wajib diisi.';
                }

                $status = empty($rowErrors) ? 'valid' : 'error';
                $previewRows[] = [
                    'excel_row' => $row,
                    'id_status_verif' => $idStatus,
                    'provinsi' => $dbRow['provinsi'] ?? (string)$sheet->getCell('B' . $row)->getCalculatedValue(),
                    'kabupaten' => $dbRow ? trim(($dbRow['kabupaten_type'] ?? '') . ' ' . ($dbRow['kabupaten'] ?? '')) : (string)$sheet->getCell('C' . $row)->getCalculatedValue(),
                    'sumber_bantuan' => $dbRow['nama_sumber'] ?? (string)$sheet->getCell('D' . $row)->getCalculatedValue(),
                    'volume' => $volume,
                    'satuan' => $satuan,
                    'status' => $status,
                    'message' => implode(' ', $rowErrors),
                ];

                if ($status === 'valid') {
                    $validRows[] = [
                        'id_status_verif' => (int)$idStatus,
                        'volume' => (string)$volume,
                        'satuan' => $satuan,
                    ];
                }
            }

            if (!$previewRows) {
                $errors[] = 'Tidak ada baris data yang bisa dibaca dari file.';
            }
        } catch (Throwable $e) {
            $errors[] = 'Gagal membaca file Excel: ' . $e->getMessage();
        }
    }
}

$validCount = count($validRows);
$errorCount = count(array_filter($previewRows, fn($row) => $row['status'] === 'error'));
$payload = $validRows ? base64_encode(json_encode($validRows)) : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Volume - Monitoring CPCL</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Import Volume dan Unit</h4>
            <div class="text-muted">Update data lama berdasarkan id_status_verif dari file Excel.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('status_verifikasi/export_template_volume.php') ?>" class="btn btn-success">Download Template</a>
            <a href="<?= base_url('index.php?page=status_verifikasi') ?>" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">File Excel</label>
                    <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                    <div class="form-text">Gunakan template dari sistem. Kolom yang diedit cukup volume dan unit.</div>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Preview Import</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($previewRows): ?>
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">Preview Import</h5>
                        <div class="text-muted">
                            File: <?= e($uploadedName) ?> |
                            Valid: <strong><?= number_format($validCount) ?></strong> |
                            Error: <strong><?= number_format($errorCount) ?></strong>
                        </div>
                    </div>

                    <?php if ($validRows): ?>
                        <form method="POST" action="<?= base_url('status_verifikasi/process_import_volume.php') ?>">
                            <input type="hidden" name="payload" value="<?= e($payload) ?>">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Proses update volume dan unit untuk data valid?')">Proses Update</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Baris</th>
                                <th>id_status_verif</th>
                                <th>Provinsi</th>
                                <th>Kabupaten</th>
                                <th>Sumber Bantuan</th>
                                <th>Volume</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewRows as $row): ?>
                                <tr>
                                    <td><?= e($row['excel_row']) ?></td>
                                    <td><?= e($row['id_status_verif']) ?></td>
                                    <td><?= e($row['provinsi']) ?></td>
                                    <td><?= e($row['kabupaten']) ?></td>
                                    <td><?= e($row['sumber_bantuan']) ?></td>
                                    <td><?= $row['volume'] !== null && $row['volume'] !== '' ? e(rtrim(rtrim(number_format((float)$row['volume'], 2, ',', '.'), '0'), ',')) : '-' ?></td>
                                    <td><?= e($row['satuan']) ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'valid'): ?>
                                            <span class="badge bg-success">Valid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($row['message'] ?: 'Siap diupdate') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
