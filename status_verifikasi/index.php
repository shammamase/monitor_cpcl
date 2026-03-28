<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$keyword        = trim($_GET['keyword_sv'] ?? '');
$provinsi_id    = trim($_GET['provinsi_id'] ?? '');
$id_sumber      = trim($_GET['id_sumber'] ?? '');
$status_filter  = trim($_GET['status_filter'] ?? '');
$id_jenis       = trim($_GET['id_jenis_bantuan'] ?? '');
$tanggal_dari   = trim($_GET['tanggal_dari'] ?? '');
$tanggal_sampai = trim($_GET['tanggal_sampai'] ?? '');
$page_number    = max(1, (int)($_GET['halaman'] ?? 1));
$limit          = 10;
$offset         = ($page_number - 1) * $limit;

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();
$sumberList   = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll();

$jenisListSql = "SELECT jb.id_jenis_bantuan, jb.nama_jenis_bantuan, sb.nama_sumber
                 FROM jenis_bantuan jb
                 LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
                 ORDER BY jb.id_jenis_bantuan ASC";
$jenisList = $pdo->query($jenisListSql)->fetchAll();

/*
|--------------------------------------------------------------------------
| WHERE dinamis
|--------------------------------------------------------------------------
*/
$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(p.nama_poktan LIKE :keyword OR sb.nama_sumber LIKE :keyword)";
    $params['keyword'] = "%{$keyword}%";
}

if ($provinsi_id !== '') {
    $where[] = "p.provinsi_id = :provinsi_id";
    $params['provinsi_id'] = $provinsi_id;
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
             LEFT JOIN poktan p ON sv.id_poktan = p.id_poktan
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
            sv.status_verifikasi,
            sv.tanggal_submit,
            sv.keterangan_kendala,
            sv.keterangan_umum,
            sv.created_at,
            p.nama_poktan,
            pr.name AS provinsi,
            kb.name AS kabupaten,
            kc.name AS kecamatan,
            sb.nama_sumber,
            GROUP_CONCAT(DISTINCT jb.nama_jenis_bantuan ORDER BY jb.nama_jenis_bantuan ASC SEPARATOR '||') AS jenis_bantuan_list
        FROM status_verifikasi sv
        LEFT JOIN poktan p ON sv.id_poktan = p.id_poktan
        LEFT JOIN provinsis pr ON p.provinsi_id = pr.id
        LEFT JOIN kabupatens kb ON p.kabupaten_id = kb.id
        LEFT JOIN kecamatans kc ON p.kecamatan_id = kc.id
        LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
        LEFT JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
        LEFT JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
        $whereSql
        GROUP BY
            sv.id_status_verif,
            sv.status_verifikasi,
            sv.tanggal_submit,
            sv.keterangan_kendala,
            sv.keterangan_umum,
            sv.created_at,
            p.nama_poktan,
            pr.name,
            kb.name,
            kc.name,
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
| Helper pagination URL
|--------------------------------------------------------------------------
*/
function buildPageUrl($page, $keyword, $provinsi_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai)
{
    $query = [
        'page' => 'status_verifikasi',
        'halaman' => $page,
    ];

    if ($keyword !== '') $query['keyword_sv'] = $keyword;
    if ($provinsi_id !== '') $query['provinsi_id'] = $provinsi_id;
    if ($id_sumber !== '') $query['id_sumber'] = $id_sumber;
    if ($status_filter !== '') $query['status_filter'] = $status_filter;
    if ($id_jenis !== '') $query['id_jenis_bantuan'] = $id_jenis;
    if ($tanggal_dari !== '') $query['tanggal_dari'] = $tanggal_dari;
    if ($tanggal_sampai !== '') $query['tanggal_sampai'] = $tanggal_sampai;

    return base_url('?' . http_build_query($query));
}
?>

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

    @media (max-width: 768px) {
        .table-responsive {
            font-size: 13px;
        }

        .btn {
            white-space: nowrap;
        }
    }
</style>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Data Status Verifikasi</h4>
            <a href="<?= base_url('status_verifikasi/create.php') ?>" class="btn btn-primary">+ Tambah Status Verifikasi</a>
        </div>

        <form method="GET" action="<?= base_url() ?>" class="row g-2 mb-3">
            <input type="hidden" name="page" value="status_verifikasi">

            <div class="col-md-3">
                <input type="text"
                       name="keyword_sv"
                       class="form-control"
                       placeholder="Cari poktan / sumber bantuan..."
                       value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-3">
                <select name="provinsi_id" class="form-select select2-filter">
                    <option value="">-- Semua Provinsi --</option>
                    <?php foreach ($provinsiList as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= ($provinsi_id == $prov['id']) ? 'selected' : '' ?>>
                            <?= e($prov['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
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
                <select name="status_filter" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="1" <?= ($status_filter === '1') ? 'selected' : '' ?>>Sudah</option>
                    <option value="0" <?= ($status_filter === '0') ? 'selected' : '' ?>>Belum</option>
                </select>
            </div>

            <div class="col-md-4">
                <select name="id_jenis_bantuan" class="form-select select2-filter">
                    <option value="">-- Semua Jenis Bantuan --</option>
                    <?php foreach ($jenisList as $jenis): ?>
                        <option value="<?= $jenis['id_jenis_bantuan'] ?>" <?= ($id_jenis == $jenis['id_jenis_bantuan']) ? 'selected' : '' ?>>
                            <?= e($jenis['nama_jenis_bantuan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="tanggal_dari" class="form-control" value="<?= e($tanggal_dari) ?>" placeholder="Dari tanggal">
            </div>

            <div class="col-md-2">
                <input type="date" name="tanggal_sampai" class="form-control" value="<?= e($tanggal_sampai) ?>" placeholder="Sampai tanggal">
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
            </div>

            <div class="col-md-auto">
                <a href="<?= base_url('?page=status_verifikasi') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>

            <div class="col-md-auto">
                <a href="<?= base_url('status_verifikasi/export_excel.php?' . http_build_query([
                    'keyword_sv' => $keyword,
                    'provinsi_id' => $provinsi_id,
                    'id_sumber' => $id_sumber,
                    'status_filter' => $status_filter,
                    'id_jenis_bantuan' => $id_jenis,
                    'tanggal_dari' => $tanggal_dari,
                    'tanggal_sampai' => $tanggal_sampai,
                ])) ?>" class="btn btn-success">
                    Export Excel
                </a>
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
                        <th>Poktan</th>
                        <th>Wilayah</th>
                        <th>Sumber Bantuan</th>
                        <th>Status</th>
                        <th>Jenis Bantuan</th>
                        <th>Keterangan Kendala</th>
                        <th>Keterangan Umum</th>
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
                            ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= e($row['nama_poktan']) ?></td>
                                <td class="sv-meta">
                                    <strong><?= e($row['provinsi']) ?></strong>
                                    <small><?= e($row['kabupaten']) ?></small>
                                    <small><?= e($row['kecamatan']) ?></small>
                                </td>
                                <td><?= e($row['nama_sumber']) ?></td>
                                <td class="status-box">
                                    <?php if ((int)$row['status_verifikasi'] === 1): ?>
                                        <span class="badge bg-success">Sudah</span>
                                        <?php if (!empty($row['tanggal_submit'])): ?>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    <?= e(date('d-m-Y', strtotime($row['tanggal_submit']))) ?>
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
                                <td><?= !empty($row['keterangan_kendala']) ? nl2br(e($row['keterangan_kendala'])) : '<span class="text-muted">-</span>' ?></td>
                                <td><?= !empty($row['keterangan_umum']) ? nl2br(e($row['keterangan_umum'])) : '<span class="text-muted">-</span>' ?></td>
                                <td class="waktu-input">
                                    <?php if (!empty($row['created_at'])): ?>
                                        <?= e(date('d-m-Y H:i', strtotime($row['created_at']))) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('status_verifikasi/edit.php?id=' . $row['id_status_verif']) ?>" class="btn btn-sm btn-warning">Update</a>
                                        <a href="<?= base_url('status_verifikasi/delete.php?id=' . $row['id_status_verif']) ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildPageUrl($page_number - 1, $keyword, $provinsi_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">
                            Sebelumnya
                        </a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php if ($start > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPageUrl(1, $keyword, $provinsi_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">1</a>
                        </li>
                        <?php if ($start > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildPageUrl($i, $keyword, $provinsi_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end < $totalPage): ?>
                        <?php if ($end < $totalPage - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPageUrl($totalPage, $keyword, $provinsi_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>"><?= $totalPage ?></a>
                        </li>
                    <?php endif; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildPageUrl($page_number + 1, $keyword, $provinsi_id, $id_sumber, $status_filter, $id_jenis, $tanggal_dari, $tanggal_sampai) ?>">
                            Berikutnya
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>