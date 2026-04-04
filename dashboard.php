<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

/*
|--------------------------------------------------------------------------
| Ambil ringkasan per provinsi
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        p.id,
        p.name AS nama_provinsi,
        COUNT(sv.id_status_verif) AS total_input,
        SUM(CASE WHEN sv.status_verifikasi = 1 THEN 1 ELSE 0 END) AS total_sudah,
        SUM(CASE WHEN sv.status_verifikasi = 0 THEN 1 ELSE 0 END) AS total_belum
    FROM provinsis p
    LEFT JOIN status_verifikasi sv
        ON sv.provinsi_id = p.id
       AND sv.is_active = 1
    GROUP BY p.id, p.name
    ORDER BY p.name ASC
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalProvinsi = count($data);
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
    <title>Dashboard Ringkasan Provinsi - Monitoring CPCL</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .summary-card,
        .province-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .summary-card {
            color: #fff;
        }

        .summary-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .summary-card .label {
            opacity: .95;
        }

        .province-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }

        .province-card {
            transition: transform .18s ease, box-shadow .18s ease;
            background: #fff;
        }

        .province-link:hover .province-card {
            transform: translateY(-4px);
            box-shadow: 0 14px 32px rgba(0,0,0,0.10);
        }

        .province-card-empty {
            border: 1px solid rgba(220, 53, 69, 0.18);
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.10), rgba(220, 53, 69, 0.04));
        }

        .province-card-empty .province-name {
            color: #b02a37;
        }

        .province-name {
            font-size: 1.02rem;
            font-weight: 700;
            min-height: 48px;
            margin-bottom: 12px;
        }

        .mini-stat {
            border-radius: 14px;
            padding: 12px;
            background: #f8f9fa;
            text-align: center;
            height: 100%;
        }

        .mini-stat .mini-value {
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .mini-stat .mini-label {
            font-size: .84rem;
            color: #6c757d;
            margin-top: 4px;
        }

        .section-title {
            font-weight: 700;
        }

        .progress {
            height: 10px;
            border-radius: 999px;
            background: #e9ecef;
        }

        .badge-soft-success {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 600;
        }

        .badge-soft-secondary {
            background: rgba(108, 117, 125, 0.12);
            color: #6c757d;
            font-weight: 600;
        }

        .badge-soft-primary {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
            font-weight: 600;
        }

        .badge-soft-danger {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            font-weight: 600;
        }

        .progress-label {
            font-size: .85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2 btn btn-outline-warning" href="<?= base_url('index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="mb-4">
        <h3 class="section-title mb-1">Ringkasan Monitoring Provinsi</h3>
        <p class="text-muted mb-0">Klik card provinsi untuk melihat data monitoring publik per provinsi.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #198754, #157347);">
                <div class="card-body">
                    <div class="label">Total Provinsi</div>
                    <div class="value"><?= number_format($totalProvinsi) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                <div class="card-body">
                    <div class="label">Total Data Diinput</div>
                    <div class="value"><?= number_format($totalInput) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #20c997, #198754);">
                <div class="card-body">
                    <div class="label">Sudah Submit ke Es.1</div>
                    <div class="value"><?= number_format($totalSudah) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #6c757d, #495057);">
                <div class="card-body">
                    <div class="label">Belum</div>
                    <div class="value"><?= number_format($totalBelum) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($data as $row): ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <a href="<?= base_url('index.php?' . http_build_query([
                    'provinsi_id' => $row['id'], 'kabupaten_id' => null, 'id_sumber' => null, 'status_filter' => null,
                    'jenis_bantuan' => null, 'tanggal_dari' => null, 'tanggal_sampai' => null
                ])) ?>" class="province-link">
                    <div class="card province-card h-100 <?= ((int)$row['total_input'] === 0) ? 'province-card-empty' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="province-name">
                                    <?= e($row['nama_provinsi']) ?>
                                </div>

                                <?php if ((int)$row['total_input'] === 0): ?>
                                    <span class="badge badge-soft-danger">Belum Input</span>
                                <?php else: ?>
                                    <span class="badge badge-soft-primary">
                                        <?= number_format($row['persen_sudah'], 1) ?>%
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="progress-label">Progress Verifikasi</span>
                                    <span class="progress-label"><?= number_format($row['total_sudah']) ?>/<?= number_format($row['total_input']) ?></span>
                                </div>
                                <div class="progress">
                                    <div 
                                        class="progress-bar <?= ((int)$row['total_input'] === 0) ? 'bg-danger' : 'bg-success' ?>"
                                        role="progressbar"
                                        style="width: <?= ((int)$row['total_input'] === 0) ? '100' : $row['persen_sudah'] ?>%;"
                                        aria-valuenow="<?= ((int)$row['total_input'] === 0) ? 100 : $row['persen_sudah'] ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-12">
                                    <div class="mini-stat">
                                        <div class="mini-value">
                                            <?php if ((int)$row['total_input'] === 0): ?>
                                                <span class="badge badge-soft-danger"><?= number_format($row['total_input']) ?></span>
                                            <?php else: ?>
                                                <?= number_format($row['total_input']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mini-label">Sudah Diinput</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="mini-stat">
                                        <div class="mini-value">
                                            <span class="badge badge-soft-success"><?= number_format($row['total_sudah']) ?></span>
                                        </div>
                                        <div class="mini-label">Sudah</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="mini-stat">
                                        <div class="mini-value">
                                            <span class="badge badge-soft-secondary"><?= number_format($row['total_belum']) ?></span>
                                        </div>
                                        <div class="mini-label">Belum</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>