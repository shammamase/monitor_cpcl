<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id    = (int)($userLogin['provinsi_id'] ?? 0);
$kabupaten_id   = trim($_GET['kabupaten_id'] ?? '');
$id_sumber      = trim($_GET['id_sumber'] ?? '');
$status_filter  = trim($_GET['status_filter'] ?? '');
$id_jenis       = trim($_GET['id_jenis_bantuan'] ?? '');
$tanggal_dari   = trim($_GET['tanggal_dari'] ?? '');
$tanggal_sampai = trim($_GET['tanggal_sampai'] ?? '');
$page_number    = max(1, (int)($_GET['halaman'] ?? 1));
$limit          = 10;
$offset         = ($page_number - 1) * $limit;

if ($provinsi_id <= 0) {
    die('Provinsi user tidak valid.');
}

$stmtKab = $pdo->prepare("
    SELECT id, type, name
    FROM kabupatens
    WHERE provinsi_id = ?
    ORDER BY name ASC
");
$stmtKab->execute([$provinsi_id]);
$kabupatenList = $stmtKab->fetchAll();

$sumberList = $pdo->query("
    SELECT id_sumber, nama_sumber
    FROM sumber_bantuan
    ORDER BY nama_sumber ASC
")->fetchAll();

$jenisList = $pdo->query("
    SELECT jb.id_jenis_bantuan, jb.nama_jenis_bantuan, sb.nama_sumber
    FROM jenis_bantuan jb
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    ORDER BY jb.nama_jenis_bantuan ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| WHERE dinamis
|--------------------------------------------------------------------------
*/
$where = [];
$params = [];

$where[] = "sv.provinsi_id = :provinsi_id";
$params['provinsi_id'] = $provinsi_id;

$where[] = "sv.is_active = 1";

if ($kabupaten_id !== '') {
    $where[] = "sv.kabupaten_id = :kabupaten_id";
    $params['kabupaten_id'] = $kabupaten_id;
}

if ($id_sumber !== '') {
    $where[] = "sv.id_sumber = :id_sumber";
    $params['id_sumber'] = $id_sumber;
}

if ($status_filter !== '' && ($status_filter === '0' || $status_filter === '1')) {
    $where[] = "sv.status_verifikasi = :status_filter";
    $params['status_filter'] = $status_filter;
}

if ($id_jenis !== '') {
    $where[] = "EXISTS (
        SELECT 1
        FROM status_verifikasi_jenis_bantuan svjb2
        WHERE svjb2.id_status_verif = sv.id_status_verif
          AND svjb2.id_jenis_bantuan = :id_jenis_bantuan
    )";
    $params['id_jenis_bantuan'] = $id_jenis;
}

if ($tanggal_dari !== '') {
    $where[] = "DATE(sv.created_at) >= :tanggal_dari";
    $params['tanggal_dari'] = $tanggal_dari;
}

if ($tanggal_sampai !== '') {
    $where[] = "DATE(sv.created_at) <= :tanggal_sampai";
    $params['tanggal_sampai'] = $tanggal_sampai;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Total data
|--------------------------------------------------------------------------
*/
$countSql = "SELECT COUNT(DISTINCT sv.id_status_verif) AS total
             FROM status_verifikasi sv
             LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
             LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
             LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
             $whereSql";

$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $stmtCount->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmtCount->execute();
$totalData = (int)$stmtCount->fetch()['total'];

$totalPage = max(1, (int)ceil($totalData / $limit));

if ($page_number > $totalPage) {
    $page_number = $totalPage;
    $offset = ($page_number - 1) * $limit;
}

/*
|--------------------------------------------------------------------------
| Data utama
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
            sv.id_status_verif,
            sv.provinsi_id,
            sv.kabupaten_id,
            sv.id_sumber,
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
        $whereSql
        GROUP BY
            sv.id_status_verif,
            sv.provinsi_id,
            sv.kabupaten_id,
            sv.id_sumber,
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
            kb.name,
            sb.nama_sumber
        ORDER BY sv.id_status_verif DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Cari root yang sudah final (status = 1)
|--------------------------------------------------------------------------
*/
$rootIds = [];

foreach ($data as $row) {
    if (!empty($row['root_id'])) {
        $rootIds[] = (int)$row['root_id'];
    }
}

$rootIds = array_values(array_unique($rootIds));
$finalizedRoots = [];

if (!empty($rootIds)) {
    $placeholders = implode(',', array_fill(0, count($rootIds), '?'));

    $stmtFinalized = $pdo->prepare("
        SELECT DISTINCT root_id
        FROM status_verifikasi
        WHERE root_id IN ($placeholders)
          AND status_verifikasi = 1
    ");
    $stmtFinalized->execute($rootIds);
    $rowsFinalized = $stmtFinalized->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rowsFinalized as $rid) {
        $finalizedRoots[(int)$rid] = true;
    }
}

/*
|--------------------------------------------------------------------------
| Helper pagination URL
|--------------------------------------------------------------------------
*/
function buildPageUrl($page, $kabupaten_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai)
{
    $query = [
        'halaman' => $page,
    ];

    if ($kabupaten_id !== '') $query['kabupaten_id'] = $kabupaten_id;
    if ($id_sumber !== '') $query['id_sumber'] = $id_sumber;
    if ($status_filter !== '') $query['status_filter'] = $status_filter;
    if ($id_jenis !== '') $query['id_jenis_bantuan'] = $id_jenis;
    if ($tanggal_dari !== '') $query['tanggal_dari'] = $tanggal_dari;
    if ($tanggal_sampai !== '') $query['tanggal_sampai'] = $tanggal_sampai;

    return base_url('page_cpcl/dashboard_provinsi.php?' . http_build_query($query));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Provinsi - Monitoring CPCL BRMP</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        .sv-meta small {
            display: block;
            line-height: 1.4;
        }

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
            white-space: nowrap;
            min-width: 140px;
        }

        .status-box {
            min-width: 110px;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
            padding: 4px 8px;
            border: 1px solid #ced4da !important;
            border-radius: .375rem !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
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
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Dashboard Provinsi CPCL</span>
        </a>

        <div class="d-flex align-items-center text-white gap-2">
            <span><?= e($userLogin['nama_lengkap']) ?> - <?= e($userLogin['nama_provinsi']) ?></span>
            <a href="<?= base_url('page_cpcl/ubah_password.php') ?>" class="btn btn-sm btn-outline-light">Ubah Password</a>
            <a href="<?= base_url('page_cpcl/logout.php') ?>" class="btn btn-sm btn-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-1">Dashboard Monitoring CPCL Provinsi</h5>
            <p class="mb-0 text-muted">
                Login sebagai: <strong><?= e($userLogin['nama_lengkap']) ?></strong> |
                Provinsi: <strong><?= e($userLogin['nama_provinsi']) ?></strong>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                <h4 class="mb-0">Data Status Verifikasi</h4>
                <a href="<?= base_url('page_cpcl/create_status_verifikasi.php') ?>" class="btn btn-primary">+ Tambah Status Verifikasi</a>
            </div>

            <form method="GET" action="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>" class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Provinsi</label>
                    <input type="text" class="form-control" value="<?= e($userLogin['nama_provinsi']) ?>" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Kabupaten</label>
                    <select name="kabupaten_id" id="filter_kabupaten_id" class="form-select select2-filter">
                        <option value="">-- Semua Kabupaten --</option>
                        <?php foreach ($kabupatenList as $kab): ?>
                            <option value="<?= $kab['id'] ?>" <?= ($kabupaten_id == $kab['id']) ? 'selected' : '' ?>>
                                <?= e($kab['type']) ?> <?= e($kab['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sumber Bantuan</label>
                    <select name="id_sumber" class="form-select select2-filter">
                        <option value="">-- Semua Sumber Bantuan --</option>
                        <?php foreach ($sumberList as $sumber): ?>
                            <option value="<?= $sumber['id_sumber'] ?>" <?= ($id_sumber == $sumber['id_sumber']) ? 'selected' : '' ?>>
                                <?= e($sumber['nama_sumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status_filter" class="form-select select2-filter">
                        <option value="">-- Semua Status --</option>
                        <option value="1" <?= ($status_filter === '1') ? 'selected' : '' ?>>Sudah</option>
                        <option value="0" <?= ($status_filter === '0') ? 'selected' : '' ?>>Belum</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jenis Bantuan</label>
                    <select name="id_jenis_bantuan" class="form-select select2-filter">
                        <option value="">-- Semua Jenis Bantuan --</option>
                        <?php foreach ($jenisList as $jenis): ?>
                            <option value="<?= $jenis['id_jenis_bantuan'] ?>" <?= ($id_jenis == $jenis['id_jenis_bantuan']) ? 'selected' : '' ?>>
                                <?= e($jenis['nama_jenis_bantuan']) ?><?= !empty($jenis['nama_sumber']) ? ' - ' . e($jenis['nama_sumber']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-1">Rentang Waktu Input</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" name="tanggal_dari" class="form-control" value="<?= e($tanggal_dari) ?>">
                            <small class="text-muted">Dari</small>
                        </div>
                        <div class="col-6">
                            <input type="date" name="tanggal_sampai" class="form-control" value="<?= e($tanggal_sampai) ?>">
                            <small class="text-muted">Sampai</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-auto">
                    <label class="form-label fw-semibold d-block invisible">Aksi</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                        <a href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>" class="btn btn-outline-secondary">Reset</a>
                        <a href="<?= base_url('page_cpcl/export_excel_provinsi.php?' . http_build_query([
                            'kabupaten_id' => $kabupaten_id,
                            'id_sumber' => $id_sumber,
                            'status_filter' => $status_filter,
                            'id_jenis_bantuan' => $id_jenis,
                            'tanggal_dari' => $tanggal_dari,
                            'tanggal_sampai' => $tanggal_sampai,
                        ])) ?>" class="btn btn-success">
                            Export Excel
                        </a>
                    </div>
                </div>
            </form>

            <div class="mb-3 text-muted">
                Total data: <strong><?= number_format($totalData) ?></strong>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th>Provinsi</th>
                            <th>Kabupaten</th>
                            <th>Sumber Bantuan</th>
                            <th>Status Verifikasi</th>
                            <th>Jenis Bantuan</th>
                            <th>Volume</th>
                            <th>Unit</th>
                            <th>Keterangan</th>
                            <th>Waktu Input</th>
                            <th width="180">Aksi</th>
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

                                    $rootId = (int)($row['root_id'] ?? 0);
                                    $hideUpdate = $rootId > 0 && isset($finalizedRoots[$rootId]);
                                ?>
                                <tr>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td><?= e($row['provinsi']) ?></td>
                                    <td> <?= e($row['kabupaten_type']) ?> <?= e($row['kabupaten']) ?></td>
                                    <td><?= e($row['nama_sumber']) ?></td>
                                    <td class="status-box">
                                        <?php if ((int)$row['status_verifikasi'] === 1): ?>
                                            <span class="badge bg-success">Sudah Submit Es.1</span>
                                            <?php if (!empty($row['tanggal_submit'])): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        Tgl Submit:<br/><?= e(date('d-m-Y', strtotime($row['tanggal_submit']))) ?>
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
                                        <?= $row['volume'] !== null && $row['volume'] !== '' ? e(rtrim(rtrim(number_format((float)$row['volume'], 2, '.', ''), '0'), '.')) : '<span class="text-muted">-</span>' ?>
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
                                            <?php if (!$hideUpdate): ?>
                                                <a href="<?= base_url('page_cpcl/edit_status_verifikasi.php?id=' . $row['id_status_verif']) ?>" class="btn btn-sm btn-warning">Update</a>
                                            <?php endif; ?>

                                            <a href="<?= base_url('page_cpcl/delete_status_verifikasi.php?id=' . $row['id_status_verif']) ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted">Belum ada data.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPage > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination flex-wrap">
                        <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildPageUrl($page_number - 1, $kabupaten_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">
                                Sebelumnya
                            </a>
                        </li>

                        <?php
                        $start = max(1, $page_number - 2);
                        $end   = min($totalPage, $page_number + 2);
                        ?>

                        <?php if ($start > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPageUrl(1, $kabupaten_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildPageUrl($i, $kabupaten_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end < $totalPage): ?>
                            <?php if ($end < $totalPage - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPageUrl($totalPage, $kabupaten_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>"><?= $totalPage ?></a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildPageUrl($page_number + 1, $kabupaten_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">
                                Berikutnya
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-filter').select2({
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
    });
});
</script>

</body>
</html>
