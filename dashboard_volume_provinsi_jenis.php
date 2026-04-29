<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$provinsi_id = trim($_GET['provinsi_id'] ?? '');
$id_jenis    = trim($_GET['id_jenis_bantuan'] ?? '');
$id_sumber   = trim($_GET['id_sumber'] ?? '');
$status      = array_key_exists('status_verifikasi', $_GET) ? trim($_GET['status_verifikasi']) : '1';

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sumberList = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll(PDO::FETCH_ASSOC);

$jenisListSql = "
    SELECT jb.id_jenis_bantuan, jb.nama_jenis_bantuan, sb.nama_sumber
    FROM jenis_bantuan jb
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    ORDER BY sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC
";
$jenisList = $pdo->query($jenisListSql)->fetchAll(PDO::FETCH_ASSOC);

$where = [
    'sv.is_active = 1',
    'sv.volume IS NOT NULL',
    'sv.volume > 0',
];
$params = [];

if ($provinsi_id !== '') {
    $where[] = 'sv.provinsi_id = :provinsi_id';
    $params['provinsi_id'] = $provinsi_id;
}

if ($id_jenis !== '') {
    $where[] = 'jb.id_jenis_bantuan = :id_jenis_bantuan';
    $params['id_jenis_bantuan'] = $id_jenis;
}

if ($id_sumber !== '') {
    $where[] = 'jb.id_sumber = :id_sumber';
    $params['id_sumber'] = $id_sumber;
}

if ($status !== '' && ($status === '0' || $status === '1')) {
    $where[] = 'sv.status_verifikasi = :status_verifikasi';
    $params['status_verifikasi'] = $status;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        pr.id AS provinsi_id,
        pr.name AS nama_provinsi,
        kb.type AS kabupaten_type,
        kb.name AS nama_kabupaten,
        jb.id_jenis_bantuan,
        jb.nama_jenis_bantuan,
        sb.nama_sumber,
        sv.satuan,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    INNER JOIN provinsis pr ON sv.provinsi_id = pr.id
    LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
    INNER JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
    INNER JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    $whereSql
    GROUP BY
        pr.id,
        pr.name,
        kb.type,
        kb.name,
        jb.id_jenis_bantuan,
        jb.nama_jenis_bantuan,
        sb.nama_sumber,
        sv.satuan
    ORDER BY pr.name ASC, kb.name ASC, jb.nama_jenis_bantuan ASC, sv.satuan ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as &$row) {
    $row['jumlah_data'] = (int)$row['jumlah_data'];
    $row['total_volume'] = (float)$row['total_volume'];
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
    <title>Volume per Provinsi dan Jenis Bantuan - Monitoring CPCL</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .rekap-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
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

        .source-label {
            color: #6c757d;
            font-size: .88rem;
        }

        .badge-volume {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 700;
            min-width: 92px;
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
            <h3 class="section-title mb-1">Total Volume per Provinsi dan Jenis Bantuan</h3>
            <p class="text-muted mb-0">Rekap akumulasi volume dari data aktif, dikelompokkan berdasarkan provinsi, jenis bantuan, dan satuan.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Data Status Verifikasi</a>
            <a href="<?= base_url('dashboard_jenis_bantuan.php') ?>" class="btn btn-outline-success">Rekap Jenis Bantuan</a>
            <a href="<?= base_url('dashboard_perwilayah.php') ?>" class="btn btn-outline-success">Data Per Wilayah</a>
            <a href="<?= base_url('export_volume_provinsi_jenis.php?' . http_build_query([
                'provinsi_id' => $provinsi_id,
                'id_jenis_bantuan' => $id_jenis,
                'id_sumber' => $id_sumber,
                'status_verifikasi' => $status,
            ])) ?>" class="btn btn-success">Export Excel</a>
        </div>
    </div>

    <div class="card rekap-card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('dashboard_volume_provinsi_jenis.php') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi_id" class="form-select select2-filter">
                        <option value="">Semua Provinsi</option>
                        <?php foreach ($provinsiList as $provinsi): ?>
                            <option value="<?= e($provinsi['id']) ?>" <?= ($provinsi_id == $provinsi['id']) ? 'selected' : '' ?>>
                                <?= e($provinsi['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Bantuan</label>
                    <select name="id_jenis_bantuan" class="form-select select2-filter">
                        <option value="">Semua Jenis Bantuan</option>
                        <?php foreach ($jenisList as $jenis): ?>
                            <option value="<?= e($jenis['id_jenis_bantuan']) ?>" <?= ($id_jenis == $jenis['id_jenis_bantuan']) ? 'selected' : '' ?>>
                                <?= e($jenis['nama_jenis_bantuan']) ?><?= !empty($jenis['nama_sumber']) ? ' - ' . e($jenis['nama_sumber']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" class="form-select select2-filter">
                        <option value="">Semua Sumber</option>
                        <?php foreach ($sumberList as $sumber): ?>
                            <option value="<?= e($sumber['id_sumber']) ?>" <?= ($id_sumber == $sumber['id_sumber']) ? 'selected' : '' ?>>
                                <?= e($sumber['nama_sumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status_verifikasi" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Sudah Submit</option>
                        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Belum Submit</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                    <a href="<?= base_url('dashboard_volume_provinsi_jenis.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Provinsi</th>
                            <th>Kabupaten/Kota</th>
                            <th>Jenis Bantuan</th>
                            <th>Sumber Bantuan</th>
                            <th class="text-end">Jumlah Data</th>
                            <th class="text-end">Total Volume</th>
                            <th>Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada data volume sesuai filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="province-name"><?= e($row['nama_provinsi']) ?></td>
                                    <td>
                                        <?= !empty($row['nama_kabupaten']) ? e(trim(($row['kabupaten_type'] ?? '') . ' ' . $row['nama_kabupaten'])) : '-' ?>
                                    </td>
                                    <td><?= e($row['nama_jenis_bantuan']) ?></td>
                                    <td>
                                        <span class="source-label"><?= !empty($row['nama_sumber']) ? e($row['nama_sumber']) : '-' ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format($row['jumlah_data']) ?></td>
                                    <td class="text-end">
                                        <span class="badge badge-volume"><?= e(format_volume($row['total_volume'])) ?></span>
                                    </td>
                                    <td><?= !empty($row['satuan']) ? e($row['satuan']) : '-' ?></td>
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
