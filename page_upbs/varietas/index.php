<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$keyword      = trim($_GET['keyword'] ?? '');
$jenis        = trim($_GET['jenis'] ?? '');
$id_komoditas = trim($_GET['id_komoditas'] ?? '');
$page_number  = max(1, (int)($_GET['halaman'] ?? 1));
$limit        = 10;
$offset       = ($page_number - 1) * $limit;

$success = $_SESSION['success_message_upbs'] ?? '';
$error   = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['success_message_upbs'], $_SESSION['error_message_upbs']);

$komoditasList = $pdo->query("
    SELECT id_komoditas, nama_komoditas
    FROM komoditas
    WHERE is_active = 1
    ORDER BY nama_komoditas ASC
")->fetchAll(PDO::FETCH_ASSOC);

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "v.nama_varietas LIKE :keyword";
    $params['keyword'] = "%{$keyword}%";
}

if ($jenis !== '' && in_array($jenis, ['varietas', 'galur'], true)) {
    $where[] = "v.jenis_varietas = :jenis";
    $params['jenis'] = $jenis;
}

if ($id_komoditas !== '') {
    $where[] = "v.id_komoditas = :id_komoditas";
    $params['id_komoditas'] = $id_komoditas;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total
             FROM varietas v
             LEFT JOIN komoditas k ON v.id_komoditas = k.id_komoditas
             $whereSql";
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

$sql = "SELECT
            v.*,
            k.nama_komoditas
        FROM varietas v
        LEFT JOIN komoditas k ON v.id_komoditas = k.id_komoditas
        $whereSql
        ORDER BY v.id_varietas DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildVarietasPageUrl($page, $keyword, $jenis, $id_komoditas)
{
    $query = ['halaman' => $page];
    if ($keyword !== '') $query['keyword'] = $keyword;
    if ($jenis !== '') $query['jenis'] = $jenis;
    if ($id_komoditas !== '') $query['id_komoditas'] = $id_komoditas;

    return base_url('page_upbs/varietas/index.php?' . http_build_query($query));
}

$pageTitle = 'Data Varietas / Galur - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'varietas';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Master Varietas / Galur</h4>
                <div class="text-muted">Kelola data varietas dan galur berdasarkan komoditas.</div>
            </div>
            <a href="<?= base_url('page_upbs/varietas/create.php') ?>" class="btn btn-primary">+ Tambah Varietas / Galur</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="GET" action="<?= base_url('page_upbs/varietas/index.php') ?>" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Pencarian</label>
                <input type="text" name="keyword" class="form-control" placeholder="Cari nama varietas / galur..." value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Jenis</label>
                <select name="jenis" class="form-select">
                    <option value="">-- Semua Jenis --</option>
                    <option value="varietas" <?= $jenis === 'varietas' ? 'selected' : '' ?>>Varietas</option>
                    <option value="galur" <?= $jenis === 'galur' ? 'selected' : '' ?>>Galur</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Komoditas</label>
                <select name="id_komoditas" class="form-select">
                    <option value="">-- Semua Komoditas --</option>
                    <?php foreach ($komoditasList as $k): ?>
                        <option value="<?= $k['id_komoditas'] ?>" <?= ($id_komoditas == $k['id_komoditas']) ? 'selected' : '' ?>>
                            <?= e($k['nama_komoditas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-auto">
                <label class="form-label d-block invisible">Aksi</label>
                <button type="submit" class="btn btn-outline-primary">Filter</button>
            </div>

            <div class="col-md-auto">
                <label class="form-label d-block invisible">Aksi</label>
                <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Nama Varietas / Galur</th>
                        <th>Jenis</th>
                        <th>Komoditas</th>
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
                                <td><?= e($row['nama_varietas']) ?></td>
                                <td>
                                    <?php if ($row['jenis_varietas'] === 'varietas'): ?>
                                        <span class="badge bg-success">Varietas</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Galur</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($row['nama_komoditas']) ?></td>
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
                                        <a href="<?= base_url('page_upbs/varietas/edit.php?id=' . $row['id_varietas']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('page_upbs/varietas/delete.php?id=' . $row['id_varietas']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildVarietasPageUrl($page_number - 1, $keyword, $jenis, $id_komoditas) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildVarietasPageUrl($i, $keyword, $jenis, $id_komoditas) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildVarietasPageUrl($page_number + 1, $keyword, $jenis, $id_komoditas) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>