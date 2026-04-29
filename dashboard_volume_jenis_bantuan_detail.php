<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$id_jenis = trim($_GET['id_jenis_bantuan'] ?? '');
$satuan   = trim($_GET['satuan'] ?? '');
$status   = array_key_exists('status_verifikasi', $_GET) ? trim($_GET['status_verifikasi']) : '1';

if ($id_jenis === '' || $satuan === '') {
    die('Jenis bantuan dan unit wajib dipilih.');
}

$stmtJenis = $pdo->prepare("
    SELECT jb.id_jenis_bantuan, jb.nama_jenis_bantuan, sb.nama_sumber
    FROM jenis_bantuan jb
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    WHERE jb.id_jenis_bantuan = ?
");
$stmtJenis->execute([$id_jenis]);
$jenis = $stmtJenis->fetch(PDO::FETCH_ASSOC);

if (!$jenis) {
    die('Jenis bantuan tidak ditemukan.');
}

$where = [
    'sv.is_active = 1',
    'sv.volume IS NOT NULL',
    'sv.volume > 0',
    'sv.satuan = :satuan',
    'jb.id_jenis_bantuan = :id_jenis_bantuan',
];
$params = [
    'satuan' => $satuan,
    'id_jenis_bantuan' => $id_jenis,
];

if ($status !== '' && ($status === '0' || $status === '1')) {
    $where[] = 'sv.status_verifikasi = :status_verifikasi';
    $params['status_verifikasi'] = $status;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        pr.name AS nama_provinsi,
        kb.type AS kabupaten_type,
        kb.name AS nama_kabupaten,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    INNER JOIN status_verifikasi_jenis_bantuan svjb
        ON sv.id_status_verif = svjb.id_status_verif
    INNER JOIN jenis_bantuan jb
        ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
    LEFT JOIN provinsis pr
        ON sv.provinsi_id = pr.id
    LEFT JOIN kabupatens kb
        ON sv.kabupaten_id = kb.id
    $whereSql
    GROUP BY
        pr.name,
        kb.type,
        kb.name
    ORDER BY pr.name ASC, kb.name ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grandTotalData = 0;
$grandTotalVolume = 0;

foreach ($data as &$row) {
    $row['jumlah_data'] = (int)$row['jumlah_data'];
    $row['total_volume'] = (float)$row['total_volume'];

    $grandTotalData += $row['jumlah_data'];
    $grandTotalVolume += $row['total_volume'];
}
unset($row);

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
    <title>Detail Daerah Pengisi Volume - Monitoring CPCL</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .rekap-card,
        .summary-card {
            border: 0;
            border-radius: 12px;
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

        .section-title {
            font-weight: 700;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .province-name {
            color: #146c43;
            font-weight: 700;
        }

        .badge-volume {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 700;
            min-width: 92px;
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
            <h3 class="section-title mb-1">Daerah Pengisi Volume</h3>
            <p class="text-muted mb-0">
                <?= e($jenis['nama_jenis_bantuan']) ?>
                <?= !empty($jenis['nama_sumber']) ? ' - ' . e($jenis['nama_sumber']) : '' ?>
                | Unit: <?= e($satuan) ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('dashboard_volume_jenis_bantuan.php') ?>" class="btn btn-outline-secondary">Kembali</a>
            <a href="<?= base_url('dashboard_volume_provinsi_jenis.php?' . http_build_query([
                'id_jenis_bantuan' => $id_jenis,
                'status_verifikasi' => $status,
            ])) ?>" class="btn btn-outline-success">Lihat per Provinsi</a>
            <a href="<?= base_url('export_volume_jenis_bantuan_detail.php?' . http_build_query([
                'id_jenis_bantuan' => $id_jenis,
                'satuan' => $satuan,
                'status_verifikasi' => $status,
            ])) ?>" class="btn btn-success">Export Excel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #198754, #157347);">
                <div class="card-body">
                    <div class="label">Total Data</div>
                    <div class="value"><?= number_format($grandTotalData) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                <div class="card-body">
                    <div class="label">Total Volume <?= e($satuan) ?></div>
                    <div class="value"><?= e(format_volume($grandTotalVolume)) ?></div>
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
                            <th>Provinsi</th>
                            <th>Kabupaten/Kota</th>
                            <th class="text-end">Jumlah Data</th>
                            <th class="text-end">Total Volume</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada daerah yang mengisi volume dan unit ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="province-name"><?= !empty($row['nama_provinsi']) ? e($row['nama_provinsi']) : '-' ?></td>
                                    <td>
                                        <?= !empty($row['nama_kabupaten']) ? e(trim(($row['kabupaten_type'] ?? '') . ' ' . $row['nama_kabupaten'])) : '-' ?>
                                    </td>
                                    <td class="text-end"><?= number_format($row['jumlah_data']) ?></td>
                                    <td class="text-end">
                                        <span class="badge badge-volume"><?= e(format_volume($row['total_volume'])) ?></span>
                                    </td>
                                    <td><?= e($satuan) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
