<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$keyword     = trim($_GET['keyword_sumber'] ?? '');
$page_number = max(1, (int)($_GET['halaman'] ?? 1));
$limit       = 10;
$offset      = ($page_number - 1) * $limit;

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "nama_sumber LIKE :keyword";
    $params['keyword'] = "%{$keyword}%";
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total FROM sumber_bantuan $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalData = (int)$stmtCount->fetch()['total'];

$totalPage = max(1, (int)ceil($totalData / $limit));

if ($page_number > $totalPage) {
    $page_number = $totalPage;
    $offset = ($page_number - 1) * $limit;
}

$sql = "SELECT id_sumber, nama_sumber, created_at, updated_at
        FROM sumber_bantuan
        $whereSql
        ORDER BY id_sumber DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll();

function buildSumberPageUrl($page, $keyword)
{
    $query = [
        'page' => 'sumber_bantuan',
        'halaman' => $page,
    ];

    if ($keyword !== '') {
        $query['keyword_sumber'] = $keyword;
    }

    return base_url('?' . http_build_query($query));
}
?>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Master Sumber Bantuan</h4>
            <a href="<?= base_url('sumber_bantuan/create.php') ?>" class="btn btn-primary">+ Tambah Sumber Bantuan</a>
        </div>

        <form method="GET" action="<?= base_url() ?>" class="row g-2 mb-3">
            <input type="hidden" name="page" value="sumber_bantuan">

            <div class="col-md-4">
                <input type="text" name="keyword_sumber" class="form-control" placeholder="Cari nama sumber bantuan..." value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary">Cari</button>
            </div>

            <div class="col-md-auto">
                <a href="<?= base_url('?page=sumber_bantuan') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Nama Sumber Bantuan</th>
                        <th>Waktu Input</th>
                        <th>Waktu Update</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= e($row['nama_sumber']) ?></td>
                                <td><?= !empty($row['created_at']) ? e(date('d-m-Y H:i', strtotime($row['created_at']))) : '-' ?></td>
                                <td><?= !empty($row['updated_at']) ? e(date('d-m-Y H:i', strtotime($row['updated_at']))) : '-' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('sumber_bantuan/edit.php?id=' . $row['id_sumber']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('sumber_bantuan/delete.php?id=' . $row['id_sumber']) ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildSumberPageUrl($page_number - 1, $keyword) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildSumberPageUrl($i, $keyword) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildSumberPageUrl($page_number + 1, $keyword) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>