<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$eselonList = [
    'Dirjen Hortikultura',
    'Dirjen PSP',
    'Dirjen Tanaman Pangan',
    'Dirjen LIP',
    'Dirjen Perkebunan',
    'Dirjen PKH',
];

$rekap = [];

foreach ($eselonList as $eselon) {
    $rekap[$eselon] = [
        'nama_eselon' => $eselon,
        'total_sumber' => 0,
        'total_input' => 0,
        'total_sudah' => 0,
        'total_belum' => 0,
        'persen_sudah' => 0,
    ];
}

$sql = "
    SELECT
        sb.id_sumber,
        sb.nama_sumber,
        SUM(
            CASE
                WHEN sv.status_verifikasi = 1 THEN 1
                WHEN sv.status_verifikasi = 0
                    AND NOT EXISTS (
                        SELECT 1
                        FROM status_verifikasi sv2
                        WHERE sv2.root_id = sv.root_id
                          AND sv2.status_verifikasi = 1
                    )
                THEN 1
                ELSE 0
            END
        ) AS total_input,
        SUM(CASE WHEN sv.status_verifikasi = 1 THEN 1 ELSE 0 END) AS total_sudah,
        SUM(
            CASE
                WHEN sv.status_verifikasi = 0
                    AND NOT EXISTS (
                        SELECT 1
                        FROM status_verifikasi sv2
                        WHERE sv2.root_id = sv.root_id
                          AND sv2.status_verifikasi = 1
                    )
                THEN 1
                ELSE 0
            END
        ) AS total_belum
    FROM sumber_bantuan sb
    LEFT JOIN status_verifikasi sv
        ON sv.id_sumber = sb.id_sumber
       AND sv.is_active = 1
    GROUP BY sb.id_sumber, sb.nama_sumber
    ORDER BY sb.nama_sumber ASC
";

$stmt = $pdo->query($sql);
$sumberRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sumberRows as $row) {
    foreach ($eselonList as $eselon) {
        $suffix = ' - ' . $eselon;

        if (substr($row['nama_sumber'], -strlen($suffix)) === $suffix) {
            $rekap[$eselon]['total_sumber']++;
            $rekap[$eselon]['total_input'] += (int)$row['total_input'];
            $rekap[$eselon]['total_sudah'] += (int)$row['total_sudah'];
            $rekap[$eselon]['total_belum'] += (int)$row['total_belum'];
            break;
        }
    }
}

$totalEselon = count($rekap);
$totalSumber = 0;
$totalInput = 0;
$totalSudah = 0;
$totalBelum = 0;

foreach ($rekap as &$row) {
    $totalSumber += $row['total_sumber'];
    $totalInput += $row['total_input'];
    $totalSudah += $row['total_sudah'];
    $totalBelum += $row['total_belum'];

    $row['persen_sudah'] = $row['total_input'] > 0
        ? round(($row['total_sudah'] / $row['total_input']) * 100, 1)
        : 0;
}
unset($row);

uasort($rekap, function ($a, $b) {
    if ($a['total_sudah'] !== $b['total_sudah']) {
        return $b['total_sudah'] <=> $a['total_sudah'];
    }

    if ($a['total_belum'] !== $b['total_belum']) {
        return $b['total_belum'] <=> $a['total_belum'];
    }

    return strcmp($a['nama_eselon'], $b['nama_eselon']);
});
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Sumber Bantuan Eselon 1 - Monitoring CPCL</title>

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

        .eselon-link {
            color: #146c43;
            font-weight: 600;
            text-decoration: none;
        }

        .eselon-link:hover {
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
            <h3 class="section-title mb-1">Rekap Sumber Bantuan Berdasarkan Eselon 1</h3>
            <p class="text-muted mb-0">Ringkasan jumlah sumber bantuan dan status submit CPCL per Eselon 1.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Data Status Verifikasi</a>
            <a href="<?= base_url('dashboard_sumber_bantuan.php') ?>" class="btn btn-outline-success">Rekap Sumber Bantuan</a>
            <a href="<?= base_url('dashboard_jenis_bantuan.php') ?>" class="btn btn-outline-success">Rekap Jenis Bantuan</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #198754, #157347);">
                <div class="card-body">
                    <div class="label">Total Eselon 1</div>
                    <div class="value"><?= number_format($totalEselon) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                <div class="card-body">
                    <div class="label">Total Sumber</div>
                    <div class="value"><?= number_format($totalSumber) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #20c997, #198754);">
                <div class="card-body">
                    <div class="label">Sudah Submit</div>
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
                            <th>Eselon 1</th>
                            <th width="130">Jumlah Sumber</th>
                            <th width="130">Total Data</th>
                            <th width="140">Sudah Submit</th>
                            <th width="120">Belum</th>
                            <th width="260">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rekap): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($rekap as $row): ?>
                                <?php
                                    $totalUrl = base_url('index.php?' . http_build_query([
                                        'eselon_1' => $row['nama_eselon'],
                                    ]));
                                    $sudahUrl = base_url('index.php?' . http_build_query([
                                        'eselon_1' => $row['nama_eselon'],
                                        'status_filter' => '1',
                                    ]));
                                    $belumUrl = base_url('index.php?' . http_build_query([
                                        'eselon_1' => $row['nama_eselon'],
                                        'status_filter' => '0',
                                    ]));
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <a href="<?= $totalUrl ?>" class="eselon-link">
                                            <?= e($row['nama_eselon']) ?>
                                        </a>
                                    </td>
                                    <td><?= number_format($row['total_sumber']) ?></td>
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
                                <td colspan="7" class="text-center text-muted">Belum ada data Eselon 1.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th><?= number_format($totalSumber) ?></th>
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
