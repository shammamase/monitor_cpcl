<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$keyword      = trim($_GET['keyword_user'] ?? '');
$provinsi_id  = trim($_GET['provinsi_id'] ?? '');
$id_satker    = trim($_GET['id_satker'] ?? '');
$page_number  = max(1, (int)($_GET['halaman'] ?? 1));
$limit        = 10;
$offset       = ($page_number - 1) * $limit;

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();

$satkerList = [];
if ($provinsi_id !== '') {
    $stmtSatker = $pdo->prepare("
        SELECT id_satker, nama_satker
        FROM satker
        WHERE provinsi_id = ?
        ORDER BY nama_satker ASC
    ");
    $stmtSatker->execute([$provinsi_id]);
    $satkerList = $stmtSatker->fetchAll();
}

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(u.nama_lengkap LIKE :keyword OR u.username LIKE :keyword OR s.nama_satker LIKE :keyword)";
    $params['keyword'] = "%{$keyword}%";
}

if ($provinsi_id !== '') {
    $where[] = "u.provinsi_id = :provinsi_id";
    $params['provinsi_id'] = $provinsi_id;
}

if ($id_satker !== '') {
    $where[] = "u.id_satker = :id_satker";
    $params['id_satker'] = $id_satker;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total
             FROM users u
             LEFT JOIN provinsis p ON u.provinsi_id = p.id
             LEFT JOIN satker s ON u.id_satker = s.id_satker
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
            u.id_user,
            u.nama_lengkap,
            u.username,
            u.is_active,
            u.last_login,
            u.created_at,
            u.updated_at,
            p.name AS nama_provinsi,
            s.nama_satker
        FROM users u
        LEFT JOIN provinsis p ON u.provinsi_id = p.id
        LEFT JOIN satker s ON u.id_satker = s.id_satker
        $whereSql
        ORDER BY u.id_user DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll();

function buildUserPageUrl($page, $keyword, $provinsi_id, $id_satker)
{
    $query = [
        'page' => 'users',
        'halaman' => $page,
    ];

    if ($keyword !== '') $query['keyword_user'] = $keyword;
    if ($provinsi_id !== '') $query['provinsi_id'] = $provinsi_id;
    if ($id_satker !== '') $query['id_satker'] = $id_satker;

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
            <h4 class="mb-0">Master User</h4>
            <a href="<?= base_url('users/create.php') ?>" class="btn btn-primary">+ Tambah User</a>
        </div>

        <form method="GET" action="<?= base_url() ?>" class="row g-2 mb-3">
            <input type="hidden" name="page" value="users">

            <div class="col-md-3">
                <label class="form-label fw-semibold">Pencarian</label>
                <input type="text" name="keyword_user" class="form-control" placeholder="Nama / username / satker..." value="<?= e($keyword) ?>">
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
                <label class="form-label fw-semibold">Satker</label>
                <select name="id_satker" id="filter_satker_id" class="form-select select2-filter">
                    <option value="">-- Semua Satker --</option>
                    <?php foreach ($satkerList as $s): ?>
                        <option value="<?= $s['id_satker'] ?>" <?= ($id_satker == $s['id_satker']) ? 'selected' : '' ?>>
                            <?= e($s['nama_satker']) ?>
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
                <a href="<?= base_url('?page=users') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Provinsi</th>
                        <th>Satker</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Waktu Input</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= e($row['nama_lengkap']) ?></td>
                                <td><?= e($row['username']) ?></td>
                                <td><?= e($row['nama_provinsi']) ?></td>
                                <td><?= e($row['nama_satker']) ?></td>
                                <td>
                                    <?php if ((int)$row['is_active'] === 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['last_login']) ? e(date('d-m-Y H:i', strtotime($row['last_login']))) : '<span class="text-muted">-</span>' ?></td>
                                <td><?= !empty($row['created_at']) ? e(date('d-m-Y H:i', strtotime($row['created_at']))) : '-' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('users/edit.php?id=' . $row['id_user']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('users/delete.php?id=' . $row['id_user']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildUserPageUrl($page_number - 1, $keyword, $provinsi_id, $id_satker) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildUserPageUrl($i, $keyword, $provinsi_id, $id_satker) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildUserPageUrl($page_number + 1, $keyword, $provinsi_id, $id_satker) ?>">Berikutnya</a>
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
        $('#filter_satker_id').html('<option value="">-- Semua Satker --</option>');

        if (provinsiId) {
            $.ajax({
                url: '<?= base_url("ajax/get_satker.php") ?>',
                type: 'GET',
                data: { provinsi_id: provinsiId },
                success: function(response) {
                    $('#filter_satker_id').html(response);
                }
            });
        }
    });
});
</script>