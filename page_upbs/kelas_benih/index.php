<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$keyword      = trim($_GET['keyword'] ?? '');
$page_number  = max(1, (int)($_GET['halaman'] ?? 1));
$limit        = 10;
$offset       = ($page_number - 1) * $limit;

$success = $_SESSION['success_message_upbs'] ?? '';
$error   = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['success_message_upbs'], $_SESSION['error_message_upbs']);

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(kode_kelas LIKE :keyword OR nama_kelas LIKE :keyword)";
    $params['keyword'] = "%{$keyword}%";
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total FROM kelas_benih $whereSql";
$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $stmtCount->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmtCount->execute();
$totalData = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

$totalPage = max(1, (int)ceil($totalData / $limit));
if ($page_number > $totalPage) {
    $page_number = $totalPage;
    $offset = ($page_number - 1) * $limit;
}

$sql = "SELECT *
        FROM kelas_benih
        $whereSql
        ORDER BY id_kelas_benih DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildKelasBenihPageUrl($page, $keyword)
{
    $query = ['halaman' => $page];
    if ($keyword !== '') $query['keyword'] = $keyword;

    return base_url('page_upbs/kelas_benih/index.php?' . http_build_query($query));
}

$pageTitle = 'Data Kelas Benih - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'kelas_benih';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Master Kelas Benih</h4>
                <div class="text-muted">Kelola data kelas benih.</div>
            </div>
            <a href="<?= base_url('page_upbs/kelas_benih/create.php') ?>" class="btn btn-primary">+ Tambah Kelas Benih</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="GET" action="<?= base_url('page_upbs/kelas_benih/index.php') ?>" class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Pencarian</label>
                <input type="text" name="keyword" class="form-control" placeholder="Cari kode atau nama kelas..." value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-auto">
                <label class="form-label d-block invisible">Aksi</label>
                <button type="submit" class="btn btn-outline-primary">Filter</button>
            </div>

            <div class="col-md-auto">
                <label class="form-label d-block invisible">Aksi</label>
                <a href="<?= base_url('page_upbs/kelas_benih/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Kode Kelas</th>
                        <th>Nama Kelas</th>
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
                                <td><?= e($row['kode_kelas']) ?></td>
                                <td><?= !empty($row['nama_kelas']) ? e($row['nama_kelas']) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <?php if ((int)$row['is_active'] === 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['created_at']) ? e(date('d-m-Y H:i', strtotime($row['created_at']))) : '-' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('page_upbs/kelas_benih/edit.php?id=' . $row['id_kelas_benih']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('page_upbs/kelas_benih/delete.php?id=' . $row['id_kelas_benih']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildKelasBenihPageUrl($page_number - 1, $keyword) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildKelasBenihPageUrl($i, $keyword) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildKelasBenihPageUrl($page_number + 1, $keyword) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>