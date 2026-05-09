<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$id_sumber = trim($_GET['id_sumber'] ?? '');
$status    = array_key_exists('status_verifikasi', $_GET) ? trim($_GET['status_verifikasi']) : '1';

$sumberList = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll(PDO::FETCH_ASSOC);

$where = [
    'sv.is_active = 1',
    'sv.volume IS NOT NULL',
    'sv.volume > 0',
    'sv.satuan IS NOT NULL',
    "sv.satuan <> ''",
];
$params = [];

if ($id_sumber !== '') {
    $where[] = 'sv.id_sumber = :id_sumber';
    $params['id_sumber'] = $id_sumber;
}

if ($status !== '' && ($status === '0' || $status === '1')) {
    $where[] = 'sv.status_verifikasi = :status_verifikasi';
    $params['status_verifikasi'] = $status;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        sb.id_sumber,
        sb.nama_sumber,
        jb.id_jenis_bantuan,
        COALESCE(jb.nama_jenis_bantuan, '-') AS nama_jenis_bantuan,
        sv.satuan,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    LEFT JOIN sumber_bantuan sb
        ON sv.id_sumber = sb.id_sumber
    LEFT JOIN status_verifikasi_jenis_bantuan svjb
        ON sv.id_status_verif = svjb.id_status_verif
    LEFT JOIN jenis_bantuan jb
        ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
    $whereSql
    GROUP BY
        sb.id_sumber,
        sb.nama_sumber,
        jb.id_jenis_bantuan,
        jb.nama_jenis_bantuan,
        sv.satuan
    ORDER BY sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC, sv.satuan ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalVolumeSql = "
    SELECT
        sv.satuan,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    $whereSql
    GROUP BY sv.satuan
    ORDER BY sv.satuan ASC
";

$stmtTotalVolume = $pdo->prepare($totalVolumeSql);
foreach ($params as $key => $value) {
    $stmtTotalVolume->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmtTotalVolume->execute();
$totalVolumeRows = $stmtTotalVolume->fetchAll(PDO::FETCH_ASSOC);

$totalSumber = [];
$totalData = 0;
$totalUnit = [];
$totalVolumeByUnit = [];
$displayGroups = [];

foreach ($data as &$row) {
    $row['jumlah_data'] = (int)$row['jumlah_data'];
    $row['total_volume'] = (float)$row['total_volume'];

    $totalData += $row['jumlah_data'];

    if (!empty($row['id_sumber'])) {
        $totalSumber[$row['id_sumber']] = true;
    }

    if (!empty($row['satuan'])) {
        $totalUnit[$row['satuan']] = true;
    }

    $groupKey = (string)($row['id_sumber'] ?? '-');

    if (!isset($displayGroups[$groupKey])) {
        $displayGroups[$groupKey] = [
            'id_sumber' => $row['id_sumber'],
            'nama_sumber' => $row['nama_sumber'],
            'rows' => [],
        ];
    }

    $displayGroups[$groupKey]['rows'][] = [
        'id_jenis_bantuan' => $row['id_jenis_bantuan'],
        'nama_jenis_bantuan' => $row['nama_jenis_bantuan'],
        'satuan' => $row['satuan'],
        'total_volume' => $row['total_volume'],
    ];
}
unset($row);

$displayGroups = array_values($displayGroups);

foreach ($totalVolumeRows as $totalRow) {
    if (!empty($totalRow['satuan'])) {
        $totalVolumeByUnit[$totalRow['satuan']] = (float)$totalRow['total_volume'];
    }
}

function format_volume($value)
{
    return rtrim(rtrim(number_format((float)$value, 2, ',', '.'), '0'), ',');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volume per Sumber Bantuan - Monitoring CPCL</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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

        .sumber-name {
            color: #146c43;
            font-weight: 700;
        }

        .badge-volume {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 700;
            min-width: 92px;
        }

        .total-row th,
        .total-row td {
            background: #f8f9fa;
            font-weight: 700;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: .75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
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
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL BRMP</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="section-title mb-1">Rekap Volume Berdasarkan Sumber Bantuan</h3>
            <p class="text-muted mb-0">Total volume bantuan dirinci per sumber bantuan, jenis bantuan, dan unit satuan.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Data Status Verifikasi</a>
            <a href="<?= base_url('dashboard_sumber_bantuan.php') ?>" class="btn btn-outline-success">Rekap Sumber Bantuan</a>
            <a href="<?= base_url('dashboard_volume_jenis_bantuan.php') ?>" class="btn btn-outline-success">Volume per Jenis</a>
            <a href="<?= base_url('export_volume_sumber_bantuan.php?' . http_build_query([
                'id_sumber' => $id_sumber,
                'status_verifikasi' => $status,
            ])) ?>" class="btn btn-success">Export Excel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #198754, #157347);">
                <div class="card-body">
                    <div class="label">Sumber Bantuan Tampil</div>
                    <div class="value"><?= number_format(count($totalSumber)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                <div class="card-body">
                    <div class="label">Total Data Volume</div>
                    <div class="value"><?= number_format($totalData) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #6c757d, #495057);">
                <div class="card-body">
                    <div class="label">Jumlah Unit Satuan</div>
                    <div class="value"><?= number_format(count($totalUnit)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card rekap-card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('dashboard_volume_sumber_bantuan.php') ?>" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" class="form-select select2-filter">
                        <option value="">Semua Sumber Bantuan</option>
                        <?php foreach ($sumberList as $sumber): ?>
                            <option value="<?= e($sumber['id_sumber']) ?>" <?= ($id_sumber == $sumber['id_sumber']) ? 'selected' : '' ?>>
                                <?= e($sumber['nama_sumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status_verifikasi" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Sudah Submit</option>
                        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Belum Submit</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                    <a href="<?= base_url('dashboard_volume_sumber_bantuan.php') ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rekap-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="70">No</th>
                            <th>Sumber Bantuan</th>
                            <th>Jenis Bantuan</th>
                            <th class="text-end">Total Volume</th>
                            <th>Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($displayGroups)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data volume sesuai filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($displayGroups as $index => $group): ?>
                                <?php $rowspan = count($group['rows']); ?>
                                <?php foreach ($group['rows'] as $rowIndex => $row): ?>
                                    <tr>
                                        <?php if ($rowIndex === 0): ?>
                                            <td rowspan="<?= $rowspan ?>"><?= $index + 1 ?></td>
                                            <td rowspan="<?= $rowspan ?>">
                                                <a href="<?= base_url('index.php?' . http_build_query([
                                                    'id_sumber' => $group['id_sumber'],
                                                    'status_filter' => $status,
                                                ])) ?>" class="sumber-name text-decoration-none">
                                                    <?= !empty($group['nama_sumber']) ? e($group['nama_sumber']) : '-' ?>
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if (!empty($row['id_jenis_bantuan'])): ?>
                                                <a href="<?= base_url('index.php?' . http_build_query([
                                                    'id_jenis_bantuan' => $row['id_jenis_bantuan'],
                                                    'satuan' => $row['satuan'],
                                                    'status_filter' => '1',
                                                ])) ?>" class="text-decoration-none fw-semibold">
                                                    <?= e($row['nama_jenis_bantuan']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-volume"><?= e(format_volume($row['total_volume'])) ?></span>
                                        </td>
                                        <td><?= e($row['satuan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php foreach ($totalVolumeByUnit as $satuan => $totalVolume): ?>
                                <tr class="total-row">
                                    <th colspan="3" class="text-end">Total Keseluruhan</th>
                                    <th class="text-end"><?= e(format_volume($totalVolume)) ?></th>
                                    <th><?= e($satuan) ?></th>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function() {
        $('.select2-filter').select2({
            width: '100%',
            allowClear: false
        });
    });
</script>
</body>
</html>
