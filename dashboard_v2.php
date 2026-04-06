<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

/*
|--------------------------------------------------------------------------
| Ambil ringkasan per provinsi
|--------------------------------------------------------------------------
*/
function sortUrl($column, $currentSortBy, $currentSortDir)
{
    $nextDir = 'desc';

    if ($currentSortBy === $column) {
        $nextDir = $currentSortDir === 'desc' ? 'asc' : 'desc';
    }

    return base_url('dashboard_v2.php?' . http_build_query([
        'sort_by' => $column,
        'sort_dir' => $nextDir,
    ]));
}

function sortIcon($column, $currentSortBy, $currentSortDir)
{
    if ($currentSortBy !== $column) {
        return '↕';
    }

    return $currentSortDir === 'desc' ? '↓' : '↑';
}

$sort_by = $_GET['sort_by'] ?? 'total_input';
$sort_dir = $_GET['sort_dir'] ?? 'desc';

$allowedSort = ['total_input', 'total_sudah', 'total_belum'];
if (!in_array($sort_by, $allowedSort, true)) {
    $sort_by = 'total_input';
}

$orderBy = "ORDER BY {$sort_by} " . strtoupper($sort_dir) . ", p.name ASC";

$sql = "
    SELECT
        p.id,
        p.name AS nama_provinsi,
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
    FROM provinsis p
    LEFT JOIN status_verifikasi sv
        ON sv.provinsi_id = p.id
       AND sv.is_active = 1
    GROUP BY p.id, p.name
    {$orderBy}
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
    <title>Dashboard Ringkasan Provinsi - Tabel</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .summary-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
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

        .dashboard-table-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .table th a {
            font-weight: 600;
        }

        .table th a:hover {
            color: #198754 !important;
        }

        .row-clickable {
            cursor: pointer;
            transition: background-color .15s ease;
        }

        .row-clickable:hover {
            background-color: #f8f9fa;
        }

        .row-empty {
            background-color: rgba(220, 53, 69, 0.08) !important;
        }

        .row-empty:hover {
            background-color: rgba(220, 53, 69, 0.14) !important;
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

        .badge-soft-danger {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            font-weight: 600;
        }

        .badge-soft-primary {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
            font-weight: 600;
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

        .province-link {
            text-decoration: none;
            font-weight: 600;
        }

        .section-title {
            font-weight: 700;
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
        <a class="navbar-brand d-flex align-items-center gap-2 btn btn-outline-warning" href="<?= base_url('index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="mb-4">
        <h3 class="section-title mb-1">Ringkasan Monitoring Provinsi</h3>
        <p class="text-muted mb-0">Klik nama provinsi atau baris tabel untuk melihat data monitoring publik per provinsi.</p>
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

    <div class="card dashboard-table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th>Provinsi</th>
                            <th width="150">
                                <a href="<?= sortUrl('total_input', $sort_by, $sort_dir) ?>" class="text-decoration-none text-dark">
                                    Sudah Diinput <?= sortIcon('total_input', $sort_by, $sort_dir) ?>
                                </a>
                            </th>
                            <th width="150">
                                <a href="<?= sortUrl('total_sudah', $sort_by, $sort_dir) ?>" class="text-decoration-none text-dark">
                                    Sudah <?= sortIcon('total_sudah', $sort_by, $sort_dir) ?>
                                </a>
                            </th>
                            <th width="150">
                                <a href="<?= sortUrl('total_belum', $sort_by, $sort_dir) ?>" class="text-decoration-none text-dark">
                                    Belum <?= sortIcon('total_belum', $sort_by, $sort_dir) ?>
                                </a>
                            </th>
                            <th width="240">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data): ?>
                            <?php foreach ($data as $i => $row): ?>
                                <?php
                                    $url = base_url('index.php?' . http_build_query([
                                        'provinsi_id' => $row['id']
                                    ]));
                                    $isEmpty = ((int)$row['total_input'] === 0);
                                ?>
                                <tr class="row-clickable <?= $isEmpty ? 'row-empty' : '' ?>" onclick="window.location='<?= e($url) ?>'">
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <a href="<?= $url ?>" class="province-link" onclick="event.stopPropagation();">
                                            <?= e($row['nama_provinsi']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($isEmpty): ?>
                                            <span class="badge badge-soft-danger"><?= number_format($row['total_input']) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-primary"><?= number_format($row['total_input']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-success"><?= number_format($row['total_sudah']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-secondary"><?= number_format($row['total_belum']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex justify-content-between">
                                                <span class="progress-text"><?= number_format($row['persen_sudah'], 1) ?>%</span>
                                                <span class="progress-text"><?= number_format($row['total_sudah']) ?>/<?= number_format($row['total_input']) ?></span>
                                            </div>
                                            <div class="progress">
                                                <div
                                                    class="progress-bar <?= $isEmpty ? 'bg-danger' : 'bg-success' ?>"
                                                    role="progressbar"
                                                    style="width: <?= $isEmpty ? '100' : $row['persen_sudah'] ?>%;"
                                                    aria-valuenow="<?= $isEmpty ? 100 : $row['persen_sudah'] ?>"
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
                                <td colspan="6" class="text-center text-muted">Belum ada data.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th><?= number_format($totalInput) ?></th>
                            <th><?= number_format($totalSudah) ?></th>
                            <th><?= number_format($totalBelum) ?></th>
                            <th>38 Provinsi</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>