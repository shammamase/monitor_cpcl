<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$wilayahList = [
    'WILAYAH I' => [
        1, 38, 36, 30, 18, 8, 4, 37, 17, 19,
    ],
    'WILAYAH II' => [
        6, 9, 3, 10, 5, 11, 2, 12, 14, 13, 15, 16,
    ],
    'WILAYAH III' => [
        35, 31, 7, 33, 32, 34, 22, 23, 20, 21, 24, 25, 28, 26, 27, 29,
    ],
];

$allProvinsiIds = [];
foreach ($wilayahList as $provinsiIds) {
    foreach ($provinsiIds as $provinsiId) {
        $allProvinsiIds[] = $provinsiId;
    }
}

$placeholders = implode(',', array_fill(0, count($allProvinsiIds), '?'));

$sql = "
    SELECT
        p.id,
        p.name AS nama_provinsi,
        COUNT(sv.id_status_verif) AS total_sudah_submit
    FROM provinsis p
    LEFT JOIN status_verifikasi sv
        ON sv.provinsi_id = p.id
       AND sv.is_active = 1
       AND sv.status_verifikasi = 1
    WHERE p.id IN ($placeholders)
    GROUP BY p.id, p.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($allProvinsiIds);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$provinsiMap = [];
foreach ($rows as $row) {
    $provinsiMap[(int)$row['id']] = [
        'id' => (int)$row['id'],
        'nama_provinsi' => $row['nama_provinsi'],
        'total_sudah_submit' => (int)$row['total_sudah_submit'],
    ];
}

$wilayahData = [];
$totalSubmit = 0;
$totalProvinsi = 0;

foreach ($wilayahList as $namaWilayah => $provinsiIds) {
    $wilayahData[$namaWilayah] = [
        'total' => 0,
        'provinsi' => [],
    ];

    foreach ($provinsiIds as $provinsiId) {
        if (!isset($provinsiMap[$provinsiId])) {
            continue;
        }

        $row = $provinsiMap[$provinsiId];
        $wilayahData[$namaWilayah]['provinsi'][] = $row;
        $wilayahData[$namaWilayah]['total'] += $row['total_sudah_submit'];
        $totalSubmit += $row['total_sudah_submit'];
        $totalProvinsi++;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Per Wilayah - Monitoring CPCL</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .summary-card,
        .ranking-card {
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

        .wilayah-row th {
            background: #e8f4ee;
            color: #146c43;
            font-size: .95rem;
            letter-spacing: .02em;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .province-link {
            color: #146c43;
            font-weight: 600;
            text-decoration: none;
        }

        .province-link:hover {
            color: #0f5132;
            text-decoration: underline;
        }

        .badge-submit {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 700;
            min-width: 56px;
        }

        .badge-empty {
            background: rgba(108, 117, 125, 0.12);
            color: #6c757d;
            font-weight: 700;
            min-width: 56px;
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
            <h3 class="section-title mb-1">Data Per Wilayah</h3>
            <p class="text-muted mb-0">Rekap provinsi yang sudah submit verifikasi CPCL.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Data Status Verifikasi</a>
            <a href="<?= base_url('dashboard_v2.php') ?>" class="btn btn-outline-success">Rangking Per Provinsi</a>
            <a href="<?= base_url('dashboard_sumber_bantuan.php') ?>" class="btn btn-outline-success">Rekap Sumber Bantuan</a>
            <a href="<?= base_url('dashboard_jenis_bantuan.php') ?>" class="btn btn-outline-success">Rekap Jenis Bantuan</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #198754, #157347);">
                <div class="card-body">
                    <div class="label">Total Wilayah</div>
                    <div class="value"><?= number_format(count($wilayahData)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                <div class="card-body">
                    <div class="label">Total Provinsi</div>
                    <div class="value"><?= number_format($totalProvinsi) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #20c997, #198754);">
                <div class="card-body">
                    <div class="label">Usulan CPCL Sudah Submit</div>
                    <div class="value"><?= number_format($totalSubmit) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card ranking-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="80">No</th>
                            <th>Provinsi</th>
                            <th width="220">Usulan CPCL Sudah Submit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($wilayahData as $namaWilayah => $wilayah): ?>
                            <tr class="wilayah-row">
                                <th colspan="2"><?= e($namaWilayah) ?></th>
                                <th><?= number_format($wilayah['total']) ?></th>
                            </tr>

                            <?php foreach ($wilayah['provinsi'] as $row): ?>
                                <?php
                                    $detailUrl = base_url('index.php?' . http_build_query([
                                        'provinsi_id' => $row['id'],
                                        'status_filter' => '1',
                                    ]));
                                    $isEmpty = ((int)$row['total_sudah_submit'] === 0);
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <a href="<?= $detailUrl ?>" class="province-link">
                                            <?= e($row['nama_provinsi']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?= $isEmpty ? 'badge-empty' : 'badge-submit' ?>">
                                            <?= number_format($row['total_sudah_submit']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th><?= number_format($totalSubmit) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
