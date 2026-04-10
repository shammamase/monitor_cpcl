<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$keyword       = trim($_GET['keyword_satker'] ?? '');
$provinsi_id   = trim($_GET['provinsi_id'] ?? '');
$kabupaten_id  = trim($_GET['kabupaten_id'] ?? '');
$page_number   = max(1, (int)($_GET['halaman'] ?? 1));
$limit         = 10;
$offset        = ($page_number - 1) * $limit;

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();

$kabupatenList = [];
if ($provinsi_id !== '') {
    $stmtKab = $pdo->prepare("
        SELECT id, name
        FROM kabupatens
        WHERE provinsi_id = ?
        ORDER BY name ASC
    ");
    $stmtKab->execute([$provinsi_id]);
    $kabupatenList = $stmtKab->fetchAll();
}

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(s.kode_satker LIKE :keyword OR s.nama_satker LIKE :keyword OR s.jenis_satker LIKE :keyword)";
    $params['keyword'] = "%{$keyword}%";
}

if ($provinsi_id !== '') {
    $where[] = "s.provinsi_id = :provinsi_id";
    $params['provinsi_id'] = $provinsi_id;
}

if ($kabupaten_id !== '') {
    $where[] = "s.kabupaten_id = :kabupaten_id";
    $params['kabupaten_id'] = $kabupaten_id;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total
             FROM satker s
             LEFT JOIN provinsis p ON s.provinsi_id = p.id
             LEFT JOIN kabupatens k ON s.kabupaten_id = k.id
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

$sql = "SELECT
            s.id_satker,
            s.kode_satker,
            s.nama_satker,
            s.jenis_satker,
            s.alamat,
            s.is_active,
            s.created_at,
            s.updated_at,
            p.name AS nama_provinsi,
            k.name AS nama_kabupaten
        FROM satker s
        LEFT JOIN provinsis p ON s.provinsi_id = p.id
        LEFT JOIN kabupatens k ON s.kabupaten_id = k.id
        $whereSql
        ORDER BY s.id_satker DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll();

function buildSatkerPageUrl($page, $keyword, $provinsi_id, $kabupaten_id)
{
    $query = [
        'page' => 'satker',
        'halaman' => $page,
    ];

    if ($keyword !== '') $query['keyword_satker'] = $keyword;
    if ($provinsi_id !== '') $query['provinsi_id'] = $provinsi_id;
    if ($kabupaten_id !== '') $query['kabupaten_id'] = $kabupaten_id;

    return base_url('?' . http_build_query($query));
}
?>

<style>
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
</style>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Master Satker</h4>
            <a href="<?= base_url('satker/create.php') ?>" class="btn btn-primary">+ Tambah Satker</a>
        </div>

        <form method="GET" action="<?= base_url() ?>" class="row g-2 mb-3">
            <input type="hidden" name="page" value="satker">

            <div class="col-md-3">
                <label class="form-label fw-semibold">Pencarian</label>
                <input type="text" name="keyword_satker" class="form-control" placeholder="Kode / nama / jenis satker..." value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Provinsi</label>
                <select name="provinsi_id" id="filter_provinsi_id" class="form-select select2-filter">
                    <option value="">-- Semua Provinsi --</option>
                    <?php foreach ($provinsiList as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= ($provinsi_id == $prov['id']) ? 'selected' : '' ?>>
                            <?= e($prov['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Kabupaten</label>
                <select name="kabupaten_id" id="filter_kabupaten_id" class="form-select select2-filter">
                    <option value="">-- Semua Kabupaten --</option>
                    <?php foreach ($kabupatenList as $kab): ?>
                        <option value="<?= $kab['id'] ?>" <?= ($kabupaten_id == $kab['id']) ? 'selected' : '' ?>>
                            <?= e($kab['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-auto">
                <label class="form-label fw-semibold d-block invisible">Aksi</label>
                <button type="submit" class="btn btn-outline-primary">Filter</button>
            </div>

            <div class="col-md-auto">
                <label class="form-label fw-semibold d-block invisible">Aksi</label>
                <a href="<?= base_url('?page=satker') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Kode Satker</th>
                        <th>Nama Satker</th>
                        <th>Jenis Satker</th>
                        <th>Provinsi</th>
                        <th>Kabupaten</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th>Waktu Input</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= e($row['kode_satker']) ?></td>
                                <td><?= e($row['nama_satker']) ?></td>
                                <td><?= e($row['jenis_satker']) ?></td>
                                <td><?= e($row['nama_provinsi']) ?></td>
                                <td><?= e($row['nama_kabupaten']) ?></td>
                                <td><?= !empty($row['alamat']) ? nl2br(e($row['alamat'])) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <?php if ((int)$row['is_active'] === 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !empty($row['created_at']) ? e(date('d-m-Y H:i', strtotime($row['created_at']))) : '-' ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('satker/edit.php?id=' . $row['id_satker']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('satker/delete.php?id=' . $row['id_satker']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildSatkerPageUrl($page_number - 1, $keyword, $provinsi_id, $kabupaten_id) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildSatkerPageUrl($i, $keyword, $provinsi_id, $kabupaten_id) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildSatkerPageUrl($page_number + 1, $keyword, $provinsi_id, $kabupaten_id) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-filter').select2({
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
    });

    $('#filter_provinsi_id').on('change', function() {
        let provinsiId = $(this).val();
        $('#filter_kabupaten_id').html('<option value="">-- Semua Kabupaten --</option>');

        if (provinsiId) {
            $.ajax({
                url: '<?= base_url("ajax/get_kabupaten.php") ?>',
                type: 'GET',
                data: { provinsi_id: provinsiId },
                success: function(response) {
                    $('#filter_kabupaten_id').html(response);
                }
            });
        }
    });
});
</script>