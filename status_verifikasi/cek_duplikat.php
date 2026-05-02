<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ");
    $stmt->execute([$table]);

    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ");
    $stmt->execute([$table, $column]);

    return (int)$stmt->fetchColumn() > 0;
}

$hasRelationTable = tableExists($pdo, 'status_verifikasi_jenis_bantuan');
$hasIsActive = columnExists($pdo, 'status_verifikasi', 'is_active');
$directJenisColumn = null;

foreach (['id_jenis_bantuan', 'id_kenis_bantuan'] as $candidateColumn) {
    if (columnExists($pdo, 'status_verifikasi', $candidateColumn)) {
        $directJenisColumn = $candidateColumn;
        break;
    }
}

$activeOnly = $hasIsActive && ($_GET['active_only'] ?? '1') === '1';
$whereSql = $activeOnly ? 'WHERE sv.is_active = 1' : '';
$mode = $directJenisColumn !== null ? 'direct' : 'relation';
$errorMessage = '';
$duplicateGroups = [];
$totalDuplicateRows = 0;

try {
    if ($mode === 'direct') {
        $jenisExpr = "sv.`{$directJenisColumn}`";
        $sql = "
            SELECT
                sv.provinsi_id,
                sv.kabupaten_id,
                sv.id_sumber,
                {$jenisExpr} AS id_jenis_bantuan,
                pr.name AS provinsi,
                kb.type AS kabupaten_type,
                kb.name AS kabupaten,
                sb.nama_sumber,
                jb.nama_jenis_bantuan,
                COUNT(*) AS jumlah_duplikat,
                GROUP_CONCAT(sv.id_status_verif ORDER BY sv.id_status_verif ASC SEPARATOR ', ') AS id_status_list,
                MIN(sv.created_at) AS pertama_input,
                MAX(sv.created_at) AS terakhir_input
            FROM status_verifikasi sv
            LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
            LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
            LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
            LEFT JOIN jenis_bantuan jb ON {$jenisExpr} = jb.id_jenis_bantuan
            {$whereSql}
            GROUP BY
                sv.provinsi_id,
                sv.kabupaten_id,
                sv.id_sumber,
                {$jenisExpr},
                pr.name,
                kb.type,
                kb.name,
                sb.nama_sumber,
                jb.nama_jenis_bantuan
            HAVING COUNT(*) > 1
            ORDER BY jumlah_duplikat DESC, pr.name ASC, kb.name ASC, sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC
        ";
    } elseif ($hasRelationTable) {
        $sql = "
            SELECT
                sv.provinsi_id,
                sv.kabupaten_id,
                sv.id_sumber,
                svjb.id_jenis_bantuan,
                pr.name AS provinsi,
                kb.type AS kabupaten_type,
                kb.name AS kabupaten,
                sb.nama_sumber,
                jb.nama_jenis_bantuan,
                COUNT(DISTINCT sv.id_status_verif) AS jumlah_duplikat,
                GROUP_CONCAT(DISTINCT sv.id_status_verif ORDER BY sv.id_status_verif ASC SEPARATOR ', ') AS id_status_list,
                MIN(sv.created_at) AS pertama_input,
                MAX(sv.created_at) AS terakhir_input
            FROM status_verifikasi sv
            INNER JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
            LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
            LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
            LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
            LEFT JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
            {$whereSql}
            GROUP BY
                sv.provinsi_id,
                sv.kabupaten_id,
                sv.id_sumber,
                svjb.id_jenis_bantuan,
                pr.name,
                kb.type,
                kb.name,
                sb.nama_sumber,
                jb.nama_jenis_bantuan
            HAVING COUNT(DISTINCT sv.id_status_verif) > 1
            ORDER BY jumlah_duplikat DESC, pr.name ASC, kb.name ASC, sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC
        ";
    } else {
        $sql = '';
        $errorMessage = 'Kolom id_jenis_bantuan/id_kenis_bantuan atau tabel relasi status_verifikasi_jenis_bantuan tidak ditemukan.';
    }

    if ($sql !== '') {
        $duplicateGroups = $pdo->query($sql)->fetchAll();
        foreach ($duplicateGroups as $group) {
            $totalDuplicateRows += (int)$group['jumlah_duplikat'];
        }
    }
} catch (Throwable $e) {
    $errorMessage = 'Gagal mengambil data duplikat: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Duplikat Status Verifikasi</title>
    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <style>
        .summary-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .duplicate-key {
            min-width: 260px;
        }

        .id-list {
            max-width: 260px;
            white-space: normal;
            word-break: break-word;
        }

        .table td,
        .table th {
            vertical-align: top;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url() ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL BRMP</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Cek Data Duplikat</h3>
            <div class="text-muted">
                Berdasarkan kombinasi provinsi, kabupaten, sumber bantuan, dan jenis bantuan.
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
            <a href="<?= base_url('status_verifikasi/cek_duplikat.php') ?>" class="btn btn-outline-primary">Refresh</a>
        </div>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-2">Grup duplikat</div>
                    <div class="summary-value"><?= number_format(count($duplicateGroups), 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-2">Total baris dalam grup duplikat</div>
                    <div class="summary-value"><?= number_format($totalDuplicateRows, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-2">Sumber jenis bantuan</div>
                    <div class="fw-semibold">
                        <?= $mode === 'direct' ? e('Kolom ' . $directJenisColumn) : 'Tabel relasi' ?>
                    </div>
                    <?php if ($hasIsActive): ?>
                        <form method="GET" class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="active_only" value="1" id="active_only" <?= $activeOnly ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label" for="active_only">Hanya data aktif</label>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th class="duplicate-key">Kombinasi Duplikat</th>
                            <th>Jumlah</th>
                            <th>ID Status Verif</th>
                            <th>Pertama Input</th>
                            <th>Terakhir Input</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($duplicateGroups): ?>
                            <?php foreach ($duplicateGroups as $i => $row): ?>
                                <?php
                                    $detailUrl = base_url('status_verifikasi/cek_duplikat_detail.php?' . http_build_query([
                                        'provinsi_id' => $row['provinsi_id'],
                                        'kabupaten_id' => $row['kabupaten_id'],
                                        'id_sumber' => $row['id_sumber'],
                                        'id_jenis_bantuan' => $row['id_jenis_bantuan'],
                                        'active_only' => $activeOnly ? '1' : '0',
                                    ]));
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <div><strong>Provinsi:</strong> <?= e($row['provinsi'] ?: '-') ?></div>
                                        <div><strong>Kabupaten:</strong> <?= e(trim(($row['kabupaten_type'] ?? '') . ' ' . ($row['kabupaten'] ?? '')) ?: '-') ?></div>
                                        <div><strong>Sumber:</strong> <?= e($row['nama_sumber'] ?: '-') ?></div>
                                        <div><strong>Jenis:</strong> <?= e($row['nama_jenis_bantuan'] ?: '-') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?= number_format((int)$row['jumlah_duplikat'], 0, ',', '.') ?> data</span>
                                    </td>
                                    <td class="id-list"><?= e($row['id_status_list']) ?></td>
                                    <td><?= !empty($row['pertama_input']) ? e(date('d-m-Y H:i', strtotime($row['pertama_input']))) : '<span class="text-muted">-</span>' ?></td>
                                    <td><?= !empty($row['terakhir_input']) ? e(date('d-m-Y H:i', strtotime($row['terakhir_input']))) : '<span class="text-muted">-</span>' ?></td>
                                    <td>
                                        <a href="<?= $detailUrl ?>" class="btn btn-sm btn-primary">Lihat Data</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ditemukan data duplikat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
