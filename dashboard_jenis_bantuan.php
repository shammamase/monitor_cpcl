<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$sql = "
    SELECT
        jb.id_jenis_bantuan,
        jb.nama_jenis_bantuan,
        sb.nama_sumber,
        COUNT(DISTINCT
            CASE
                WHEN sv.status_verifikasi = 1 THEN sv.id_status_verif
                WHEN sv.status_verifikasi = 0
                    AND NOT EXISTS (
                        SELECT 1
                        FROM status_verifikasi sv2
                        WHERE sv2.root_id = sv.root_id
                          AND sv2.status_verifikasi = 1
                    )
                THEN sv.id_status_verif
                ELSE NULL
            END
        ) AS total_input,
        COUNT(DISTINCT CASE WHEN sv.status_verifikasi = 1 THEN sv.id_status_verif END) AS total_sudah,
        COUNT(DISTINCT
            CASE
                WHEN sv.status_verifikasi = 0
                    AND NOT EXISTS (
                        SELECT 1
                        FROM status_verifikasi sv2
                        WHERE sv2.root_id = sv.root_id
                          AND sv2.status_verifikasi = 1
                    )
                THEN sv.id_status_verif
                ELSE NULL
            END
        ) AS total_belum
    FROM jenis_bantuan jb
    LEFT JOIN sumber_bantuan sb
        ON jb.id_sumber = sb.id_sumber
    LEFT JOIN status_verifikasi_jenis_bantuan svjb
        ON jb.id_jenis_bantuan = svjb.id_jenis_bantuan
    LEFT JOIN status_verifikasi sv
        ON svjb.id_status_verif = sv.id_status_verif
       AND sv.is_active = 1
    GROUP BY jb.id_jenis_bantuan, jb.nama_jenis_bantuan, sb.nama_sumber
    ORDER BY total_sudah DESC, total_belum DESC, sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalJenis = count($data);
$totalInput = 0;
$totalSudah = 0;
$totalBelum = 0;

foreach ($data as &$row) {
    $row['total_input'] = (int)$row['total_input'];
    $row['total_sudah'] = (int)$row['total_sudah'];
    $row['total_belum'] = (int)$row['total_belum'];

    $totalInput += $row['total_input'];
    $totalSudah += $row['total_sudah'];
    $totalBelum += $row['total_belum'];

    $row['persen_sudah'] = $row['total_input'] > 0
        ? round(($row['total_sudah'] / $row['total_input']) * 100, 1)
        : 0;
}
unset($row);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Jenis Bantuan - Monitoring CPCL</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .summary-card,
        .rekap-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .summary-card {
            color: #fff;
        }

        .summary-card .value {
            font-size: 1.9rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .summary-card .label {
            opacity: .95;
        }

        .section-title {
            font-weight: 700;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .jenis-link {
            color: #146c43;
            font-weight: 600;
            text-decoration: none;
        }

        .jenis-link:hover {
            color: #0f5132;
            text-decoration: underline;
        }

        .badge-soft-primary {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
            font-weight: 700;
            min-width: 56px;
        }

        .badge-soft-success {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 700;
            min-width: 56px;
        }

        .badge-soft-secondary {
            background: rgba(108, 117, 125, 0.12);
            color: #6c757d;
            font-weight: 700;
            min-width: 56px;
        }

        .source-label {
            color: #6c757d;
            font-size: .88rem;
        }

        .progress {
            height: 12px;
            border-radius: 999px;
            background: #e9ecef;
            min-width: 120px;
        }

        .progress-text {
            font-size: .85rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL BRMP</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="section-title mb-1">Rekap Berdasarkan Jenis Bantuan</h3>
            <p class="text-muted mb-0">Daftar jumlah usulan CPCL per jenis bantuan, termasuk yang sudah submit ke Es.1 dan belum.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Data Status Verifikasi</a>
            <a href="<?= base_url('dashboard_sumber_bantuan.php') ?>" class="btn btn-outline-success">Rekap Sumber Bantuan</a>
            <a href="<?= base_url('dashboard_perwilayah.php') ?>" class="btn btn-outline-success">Data Per Wilayah</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #198754, #157347);">
                <div class="card-body">
                    <div class="label">Total Jenis Bantuan</div>
                    <div class="value"><?= number_format($totalJenis) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                <div class="card-body">
                    <div class="label">Total Data</div>
                    <div class="value"><?= number_format($totalInput) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #20c997, #198754);">
                <div class="card-body">
                    <div class="label">Sudah Submit ke Es.1</div>
                    <div class="value"><?= number_format($totalSudah) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #6c757d, #495057);">
                <div class="card-body">
                    <div class="label">Belum</div>
                    <div class="value"><?= number_format($totalBelum) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card rekap-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="70">No</th>
                            <th>Jenis Bantuan</th>
                            <th width="220">Sumber Bantuan</th>
                            <th width="130">Total Data</th>
                            <th width="160">Sudah Submit</th>
                            <th width="120">Belum</th>
                            <th width="260">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data): ?>
                            <?php foreach ($data as $i => $row): ?>
                                <?php
                                    $totalUrl = base_url('index.php?' . http_build_query([
                                        'id_jenis_bantuan' => $row['id_jenis_bantuan'],
                                    ]));
                                    $sudahUrl = base_url('index.php?' . http_build_query([
                                        'id_jenis_bantuan' => $row['id_jenis_bantuan'],
                                        'status_filter' => '1',
                                    ]));
                                    $belumUrl = base_url('index.php?' . http_build_query([
                                        'id_jenis_bantuan' => $row['id_jenis_bantuan'],
                                        'status_filter' => '0',
                                    ]));
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <a href="<?= $totalUrl ?>" class="jenis-link">
                                            <?= e($row['nama_jenis_bantuan']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="source-label"><?= e($row['nama_sumber'] ?: '-') ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= $totalUrl ?>" class="badge badge-soft-primary text-decoration-none">
                                            <?= number_format($row['total_input']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= $sudahUrl ?>" class="badge badge-soft-success text-decoration-none">
                                            <?= number_format($row['total_sudah']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= $belumUrl ?>" class="badge badge-soft-secondary text-decoration-none">
                                            <?= number_format($row['total_belum']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex justify-content-between">
                                                <span class="progress-text"><?= number_format($row['persen_sudah'], 1) ?>%</span>
                                                <span class="progress-text"><?= number_format($row['total_sudah']) ?>/<?= number_format($row['total_input']) ?></span>
                                            </div>
                                            <div class="progress">
                                                <div
                                                    class="progress-bar bg-success"
                                                    role="progressbar"
                                                    style="width: <?= $row['persen_sudah'] ?>%;"
                                                    aria-valuenow="<?= $row['persen_sudah'] ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data jenis bantuan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3">Total</th>
                            <th><?= number_format($totalInput) ?></th>
                            <th><?= number_format($totalSudah) ?></th>
                            <th><?= number_format($totalBelum) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
