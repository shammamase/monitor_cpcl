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

$provinsiId = trim($_GET['provinsi_id'] ?? '');
$kabupatenId = trim($_GET['kabupaten_id'] ?? '');
$idSumber = trim($_GET['id_sumber'] ?? '');
$idJenis = trim($_GET['id_jenis_bantuan'] ?? '');
$activeOnlyParam = trim($_GET['active_only'] ?? '1');

$hasRelationTable = tableExists($pdo, 'status_verifikasi_jenis_bantuan');
$hasIsActive = columnExists($pdo, 'status_verifikasi', 'is_active');
$activeOnly = $hasIsActive && $activeOnlyParam === '1';
$directJenisColumn = null;
$errorMessage = '';
$data = [];
$info = null;

foreach (['id_jenis_bantuan', 'id_kenis_bantuan'] as $candidateColumn) {
    if (columnExists($pdo, 'status_verifikasi', $candidateColumn)) {
        $directJenisColumn = $candidateColumn;
        break;
    }
}

if ($provinsiId === '' || $kabupatenId === '' || $idSumber === '' || $idJenis === '') {
    $errorMessage = 'Parameter data duplikat tidak lengkap.';
} else {
    try {
        $where = [
            'sv.provinsi_id = :provinsi_id',
            'sv.kabupaten_id = :kabupaten_id',
            'sv.id_sumber = :id_sumber',
        ];
        $params = [
            'provinsi_id' => $provinsiId,
            'kabupaten_id' => $kabupatenId,
            'id_sumber' => $idSumber,
            'id_jenis_bantuan' => $idJenis,
        ];

        if ($activeOnly) {
            $where[] = 'sv.is_active = 1';
        }

        if ($directJenisColumn !== null) {
            $where[] = "sv.`{$directJenisColumn}` = :id_jenis_bantuan";
        } elseif ($hasRelationTable) {
            $where[] = "EXISTS (
                SELECT 1
                FROM status_verifikasi_jenis_bantuan svjb_filter
                WHERE svjb_filter.id_status_verif = sv.id_status_verif
                  AND svjb_filter.id_jenis_bantuan = :id_jenis_bantuan
            )";
        } else {
            $errorMessage = 'Kolom id_jenis_bantuan/id_kenis_bantuan atau tabel relasi status_verifikasi_jenis_bantuan tidak ditemukan.';
        }

        if ($errorMessage === '') {
            $whereSql = 'WHERE ' . implode(' AND ', $where);

            $sql = "
                SELECT
                    sv.id_status_verif,
                    sv.status_verifikasi,
                    sv.tanggal_submit,
                    sv.volume,
                    sv.satuan,
                    sv.copied_from_id,
                    sv.root_id,
                    sv.keterangan_kendala,
                    sv.keterangan_umum,
                    sv.created_at,
                    pr.name AS provinsi,
                    kb.type AS kabupaten_type,
                    kb.name AS kabupaten,
                    sb.nama_sumber,
                    GROUP_CONCAT(DISTINCT jb.nama_jenis_bantuan ORDER BY jb.nama_jenis_bantuan ASC SEPARATOR '||') AS jenis_bantuan_list
                FROM status_verifikasi sv
                LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
                LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
                LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
                LEFT JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
                LEFT JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
                {$whereSql}
                GROUP BY
                    sv.id_status_verif,
                    sv.status_verifikasi,
                    sv.tanggal_submit,
                    sv.volume,
                    sv.satuan,
                    sv.copied_from_id,
                    sv.root_id,
                    sv.keterangan_kendala,
                    sv.keterangan_umum,
                    sv.created_at,
                    pr.name,
                    kb.type,
                    kb.name,
                    sb.nama_sumber
                ORDER BY sv.id_status_verif DESC
            ";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetchAll();

            $stmtInfo = $pdo->prepare("
                SELECT
                    pr.name AS provinsi,
                    kb.type AS kabupaten_type,
                    kb.name AS kabupaten,
                    sb.nama_sumber,
                    jb.nama_jenis_bantuan
                FROM provinsis pr
                LEFT JOIN kabupatens kb ON kb.id = :kabupaten_id
                LEFT JOIN sumber_bantuan sb ON sb.id_sumber = :id_sumber
                LEFT JOIN jenis_bantuan jb ON jb.id_jenis_bantuan = :id_jenis_bantuan
                WHERE pr.id = :provinsi_id
                LIMIT 1
            ");
            $stmtInfo->execute($params);
            $info = $stmtInfo->fetch();
        }
    } catch (Throwable $e) {
        $errorMessage = 'Gagal mengambil detail data duplikat: ' . $e->getMessage();
    }
}

$backUrl = base_url('status_verifikasi/cek_duplikat.php?' . http_build_query([
    'active_only' => $activeOnly ? '1' : '0',
]));
$currentUrl = $_SERVER['REQUEST_URI'] ?? base_url('status_verifikasi/cek_duplikat.php');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Duplikat Status Verifikasi</title>
    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <style>
        .jenis-bantuan-list {
            margin: 0;
            padding-left: 18px;
        }

        .jenis-bantuan-list li {
            margin-bottom: 2px;
        }

        .table td,
        .table th {
            vertical-align: top;
        }

        .waktu-input {
            min-width: 140px;
            white-space: nowrap;
        }

        .status-box {
            min-width: 110px;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 13px;
            }

            .btn {
                white-space: nowrap;
            }
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
            <h3 class="mb-1">Detail Data Duplikat</h3>
            <div class="text-muted">Data dengan kombinasi provinsi, kabupaten, sumber bantuan, dan jenis bantuan yang sama.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">Kembali</a>
            <a href="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>" class="btn btn-outline-primary">Refresh</a>
        </div>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted">Provinsi</div>
                    <div class="fw-semibold"><?= e($info['provinsi'] ?? '-') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Kabupaten</div>
                    <div class="fw-semibold"><?= e(trim(($info['kabupaten_type'] ?? '') . ' ' . ($info['kabupaten'] ?? '')) ?: '-') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Sumber Bantuan</div>
                    <div class="fw-semibold"><?= e($info['nama_sumber'] ?? '-') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Jenis Bantuan</div>
                    <div class="fw-semibold"><?= e($info['nama_jenis_bantuan'] ?? '-') ?></div>
                </div>
            </div>
            <div class="mt-3 text-muted">
                Total data: <strong><?= number_format(count($data), 0, ',', '.') ?></strong>
                <?php if ($hasIsActive): ?>
                    <span class="ms-2">Mode: <?= $activeOnly ? 'hanya data aktif' : 'semua data' ?></span>
                <?php endif; ?>
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
                            <th>ID</th>
                            <th>Copied From ID</th>
                            <th>Root ID</th>
                            <th>Provinsi</th>
                            <th>Kabupaten</th>
                            <th>Sumber Bantuan</th>
                            <th>Status Verifikasi</th>
                            <th>Jenis Bantuan</th>
                            <th>Volume</th>
                            <th>Unit</th>
                            <th>Keterangan</th>
                            <th>Waktu Input</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data): ?>
                            <?php foreach ($data as $i => $row): ?>
                                <?php
                                    $jenisBantuan = [];
                                    if (!empty($row['jenis_bantuan_list'])) {
                                        $jenisBantuan = explode('||', $row['jenis_bantuan_list']);
                                    }
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($row['id_status_verif']) ?></td>
                                    <td><?= !empty($row['copied_from_id']) ? e($row['copied_from_id']) : '<span class="text-muted">-</span>' ?></td>
                                    <td><?= !empty($row['root_id']) ? e($row['root_id']) : '<span class="text-muted">-</span>' ?></td>
                                    <td><?= e($row['provinsi']) ?></td>
                                    <td><?= e($row['kabupaten_type']) ?> <?= e($row['kabupaten']) ?></td>
                                    <td><?= e($row['nama_sumber']) ?></td>
                                    <td class="status-box">
                                        <?php if ((int)$row['status_verifikasi'] === 1): ?>
                                            <span class="badge bg-success">Sudah Submit Es.1</span>
                                            <?php if (!empty($row['tanggal_submit'])): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        Tgl Submit:<br><?= e(date('d-m-Y', strtotime($row['tanggal_submit']))) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($jenisBantuan)): ?>
                                            <ul class="jenis-bantuan-list">
                                                <?php foreach ($jenisBantuan as $jb): ?>
                                                    <li><?= e($jb) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['volume'] !== null && $row['volume'] !== '' ? e(rtrim(rtrim(number_format((float)$row['volume'], 2, ',', '.'), '0'), ',')) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td><?= !empty($row['satuan']) ? e($row['satuan']) : '<span class="text-muted">-</span>' ?></td>
                                    <td><?= !empty($row['keterangan_kendala']) ? nl2br(e($row['keterangan_kendala'])) : '<span class="text-muted">-</span>' ?></td>
                                    <td class="waktu-input">
                                        <?php if (!empty($row['created_at'])): ?>
                                            <?= e(date('d-m-Y H:i', strtotime($row['created_at']))) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <a href="<?= base_url('status_verifikasi/edit_duplikat.php?' . http_build_query([
                                                'id' => $row['id_status_verif'],
                                                'redirect' => $currentUrl,
                                            ])) ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="<?= base_url('status_verifikasi/delete.php?' . http_build_query([
                                                'id' => $row['id_status_verif'],
                                                'redirect' => $currentUrl,
                                            ])) ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="14" class="text-center text-muted">Data tidak ditemukan.</td>
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
