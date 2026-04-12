<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker    = (int)($userUpbs['id_satker'] ?? 0);
$keyword      = trim($_GET['keyword'] ?? '');
$jenis_upbs   = trim($_GET['jenis_upbs'] ?? '');
$page_number  = max(1, (int)($_GET['halaman'] ?? 1));
$limit        = 10;
$offset       = ($page_number - 1) * $limit;

$success = $_SESSION['success_message_upbs'] ?? '';
$error   = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['success_message_upbs'], $_SESSION['error_message_upbs']);

$where = ["u.id_satker = :id_satker"];
$params = ['id_satker' => $id_satker];

if ($keyword !== '') {
    $where[] = "(u.kode_upbs LIKE :keyword OR u.nama_upbs LIKE :keyword OR u.no_hp_pengelola LIKE :keyword)";
    $params['keyword'] = "%{$keyword}%";
}

if ($jenis_upbs !== '') {
    $where[] = "u.jenis_upbs = :jenis_upbs";
    $params['jenis_upbs'] = $jenis_upbs;
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

$countSql = "
    SELECT COUNT(*) AS total
    FROM upbs u
    $whereSql
";
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

$sql = "
    SELECT
        u.*,
        s.nama_satker
    FROM upbs u
    LEFT JOIN satker s ON u.id_satker = s.id_satker
    $whereSql
    ORDER BY u.id_upbs DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jenisListStmt = $pdo->prepare("
    SELECT DISTINCT jenis_upbs
    FROM upbs
    WHERE id_satker = ?
      AND jenis_upbs IS NOT NULL
      AND jenis_upbs <> ''
    ORDER BY jenis_upbs ASC
");
$jenisListStmt->execute([$id_satker]);
$jenisList = $jenisListStmt->fetchAll(PDO::FETCH_COLUMN);

function buildUpbsPageUrl($page, $keyword, $jenis_upbs)
{
    $query = ['halaman' => $page];
    if ($keyword !== '') $query['keyword'] = $keyword;
    if ($jenis_upbs !== '') $query['jenis_upbs'] = $jenis_upbs;

    return base_url('page_upbs/upbs/index.php?' . http_build_query($query));
}

$pageTitle = 'Data UPBS - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'upbs';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Master UPBS</h4>
                <div class="text-muted">Kelola data UPBS pada satker Anda.</div>
            </div>
            <a href="<?= base_url('page_upbs/upbs/create.php') ?>" class="btn btn-primary">+ Tambah UPBS</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="GET" action="<?= base_url('page_upbs/upbs/index.php') ?>" class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Pencarian</label>
                <input type="text" name="keyword" class="form-control" placeholder="Cari kode, nama, atau no HP..." value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Jenis UPBS</label>
                <select name="jenis_upbs" class="form-select">
                    <option value="">-- Semua Jenis --</option>
                    <?php foreach ($jenisList as $jenis): ?>
                        <option value="<?= e($jenis) ?>" <?= $jenis_upbs === $jenis ? 'selected' : '' ?>>
                            <?= e($jenis) ?>
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
                <a href="<?= base_url('page_upbs/upbs/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Kode UPBS</th>
                        <th>Nama UPBS</th>
                        <th>Jenis UPBS</th>
                        <th>No. HP Pengelola</th>
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
                                <td><?= e($row['kode_upbs']) ?></td>
                                <td><?= e($row['nama_upbs']) ?></td>
                                <td>
                                    <?php
                                        $labelJenis = '-';
                                        if ($row['jenis_upbs'] === 'tanaman_pangan') $labelJenis = 'Tanaman Pangan';
                                        elseif ($row['jenis_upbs'] === 'hortikultura') $labelJenis = 'Hortikultura';
                                        elseif ($row['jenis_upbs'] === 'perkebunan') $labelJenis = 'Perkebunan';
                                        elseif ($row['jenis_upbs'] === 'peternakan') $labelJenis = 'Peternakan';
                                    ?>
                                    <?= e($labelJenis) ?>
                                </td>
                                <td><?= !empty($row['no_hp_pengelola']) ? e($row['no_hp_pengelola']) : '-' ?></td>
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
                                        <a href="<?= base_url('page_upbs/upbs/edit.php?id=' . $row['id_upbs']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('page_upbs/upbs/delete.php?id=' . $row['id_upbs']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada data UPBS.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildUpbsPageUrl($page_number - 1, $keyword, $jenis_upbs) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildUpbsPageUrl($i, $keyword, $jenis_upbs) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildUpbsPageUrl($page_number + 1, $keyword, $jenis_upbs) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>