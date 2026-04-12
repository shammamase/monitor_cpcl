<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker = (int)($userUpbs['id_satker'] ?? 0);

$tanggal_dari   = trim($_GET['tanggal_dari'] ?? '');
$tanggal_sampai = trim($_GET['tanggal_sampai'] ?? '');
$id_upbs        = trim($_GET['id_upbs'] ?? '');
$page_number    = max(1, (int)($_GET['halaman'] ?? 1));
$limit          = 10;
$offset         = ($page_number - 1) * $limit;

$upbsList = [];
if ($id_satker > 0) {
    $stmtUpbs = $pdo->prepare("
        SELECT id_upbs, nama_upbs
        FROM upbs
        WHERE id_satker = ?
          AND is_active = 1
        ORDER BY nama_upbs ASC
    ");
    $stmtUpbs->execute([$id_satker]);
    $upbsList = $stmtUpbs->fetchAll(PDO::FETCH_ASSOC);
}

$where = [];
$params = [];

$where[] = "u.id_satker = :id_satker";
$params['id_satker'] = $id_satker;

if ($tanggal_dari !== '') {
    $where[] = "l.tanggal_laporan >= :tanggal_dari";
    $params['tanggal_dari'] = $tanggal_dari;
}

if ($tanggal_sampai !== '') {
    $where[] = "l.tanggal_laporan <= :tanggal_sampai";
    $params['tanggal_sampai'] = $tanggal_sampai;
}

if ($id_upbs !== '') {
    $where[] = "l.id_upbs = :id_upbs";
    $params['id_upbs'] = $id_upbs;
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

$success = $_SESSION['success_message_upbs'] ?? '';
$error   = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['success_message_upbs'], $_SESSION['error_message_upbs']);

$countSql = "
    SELECT COUNT(*) AS total
    FROM laporan_stok_upbs l
    INNER JOIN upbs u ON l.id_upbs = u.id_upbs
    $whereSql
";
$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $stmtCount->bindValue(':' . $key, $value);
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
        l.id_laporan_stok,
        l.tanggal_laporan,
        l.periode_text,
        l.nama_petugas_input,
        l.keterangan,
        u.nama_upbs,
        COUNT(d.id_detail_stok) AS jumlah_item
    FROM laporan_stok_upbs l
    INNER JOIN upbs u ON l.id_upbs = u.id_upbs
    LEFT JOIN laporan_stok_upbs_detail d ON l.id_laporan_stok = d.id_laporan_stok
    $whereSql
    GROUP BY
        l.id_laporan_stok,
        l.tanggal_laporan,
        l.periode_text,
        l.nama_petugas_input,
        l.keterangan,
        u.nama_upbs
    ORDER BY l.id_laporan_stok DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildInputStokPageUrl($page, $tanggal_dari, $tanggal_sampai, $id_upbs)
{
    $query = ['halaman' => $page];
    if ($tanggal_dari !== '') $query['tanggal_dari'] = $tanggal_dari;
    if ($tanggal_sampai !== '') $query['tanggal_sampai'] = $tanggal_sampai;
    if ($id_upbs !== '') $query['id_upbs'] = $id_upbs;

    return base_url('page_upbs/input_stok/index.php?' . http_build_query($query));
}

$pageTitle = 'Input Stok - UPBS';
$activeMenu = 'input_stok';
$activeSubmenu = '';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Input Stok</h4>
                <div class="text-muted">Daftar laporan stok UPBS untuk satker Anda.</div>
            </div>
            <a href="<?= base_url('page_upbs/input_stok/create.php') ?>" class="btn btn-primary">+ Input Stok Baru</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="GET" action="<?= base_url('page_upbs/input_stok/index.php') ?>" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Dari</label>
                <input type="date" name="tanggal_dari" class="form-control" value="<?= e($tanggal_dari) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control" value="<?= e($tanggal_sampai) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">UPBS</label>
                <select name="id_upbs" class="form-select">
                    <option value="">-- Semua UPBS --</option>
                    <?php foreach ($upbsList as $u): ?>
                        <option value="<?= $u['id_upbs'] ?>" <?= ($id_upbs == $u['id_upbs']) ? 'selected' : '' ?>>
                            <?= e($u['nama_upbs']) ?>
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
                <a href="<?= base_url('page_upbs/input_stok/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="mb-3 text-muted">
            Total laporan: <strong><?= number_format($totalData) ?></strong>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="60">No</th>
                        <th>Tanggal Laporan</th>
                        <th>UPBS</th>
                        <th>Periode</th>
                        <th>Petugas</th>
                        <th>Jumlah Item</th>
                        <th>Keterangan</th>
                        <th width="170">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= !empty($row['tanggal_laporan']) ? e(date('d-m-Y', strtotime($row['tanggal_laporan']))) : '-' ?></td>
                                <td><?= e($row['nama_upbs']) ?></td>
                                <td><?= !empty($row['periode_text']) ? e($row['periode_text']) : '<span class="text-muted">-</span>' ?></td>
                                <td><?= !empty($row['nama_petugas_input']) ? e($row['nama_petugas_input']) : '<span class="text-muted">-</span>' ?></td>
                                <td><span class="badge bg-success"><?= number_format((int)$row['jumlah_item']) ?></span></td>
                                <td><?= !empty($row['keterangan']) ? e($row['keterangan']) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('page_upbs/input_stok/detail.php?id=' . $row['id_laporan_stok']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                        <a href="<?= base_url('page_upbs/input_stok/edit.php?id=' . $row['id_laporan_stok']) ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                        <a href="<?= base_url('page_upbs/input_stok/salin.php?id=' . $row['id_laporan_stok']) ?>" class="btn btn-sm btn-outline-success">Update</a>
                                        <a href="<?= base_url('page_upbs/input_stok/delete.php?id=' . $row['id_laporan_stok']) ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin ingin menghapus laporan stok ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada laporan stok.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildInputStokPageUrl($page_number - 1, $tanggal_dari, $tanggal_sampai, $id_upbs) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildInputStokPageUrl($i, $tanggal_dari, $tanggal_sampai, $id_upbs) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildInputStokPageUrl($page_number + 1, $tanggal_dari, $tanggal_sampai, $id_upbs) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>