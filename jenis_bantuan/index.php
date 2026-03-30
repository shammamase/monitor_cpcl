<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$keyword     = trim($_GET['keyword_jenis'] ?? '');
$id_sumber   = trim($_GET['filter_sumber_id'] ?? '');
$page_number = max(1, (int)($_GET['halaman'] ?? 1));
$limit       = 10;
$offset      = ($page_number - 1) * $limit;

$sumberList = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll();

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "jb.nama_jenis_bantuan LIKE :keyword";
    $params['keyword'] = "%{$keyword}%";
}

if ($id_sumber !== '') {
    $where[] = "jb.id_sumber = :id_sumber";
    $params['id_sumber'] = $id_sumber;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total
             FROM jenis_bantuan jb
             LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
             $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalData = (int)$stmtCount->fetch()['total'];

$totalPage = max(1, (int)ceil($totalData / $limit));

if ($page_number > $totalPage) {
    $page_number = $totalPage;
    $offset = ($page_number - 1) * $limit;
}

$sql = "SELECT 
            jb.id_jenis_bantuan,
            jb.id_sumber,
            jb.nama_jenis_bantuan,
            jb.created_at,
            jb.updated_at,
            sb.nama_sumber
        FROM jenis_bantuan jb
        LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
        $whereSql
        ORDER BY jb.id_jenis_bantuan DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll();

function buildJenisPageUrl($page, $keyword, $id_sumber)
{
    $query = [
        'page' => 'jenis_bantuan',
        'halaman' => $page,
    ];

    if ($keyword !== '') {
        $query['keyword_jenis'] = $keyword;
    }

    if ($id_sumber !== '') {
        $query['filter_sumber_id'] = $id_sumber;
    }

    return base_url('?' . http_build_query($query));
}
?>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Master Jenis Bantuan</h4>
            <a href="<?= base_url('jenis_bantuan/create.php') ?>" class="btn btn-primary">+ Tambah Jenis Bantuan</a>
        </div>

        <form method="GET" action="<?= base_url() ?>" class="row g-2 mb-3">
            <input type="hidden" name="page" value="jenis_bantuan">

            <div class="col-md-4">
                <input type="text" name="keyword_jenis" class="form-control" placeholder="Cari nama jenis bantuan..." value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-4">
                <select name="filter_sumber_id" class="form-select select2-filter">
                    <option value="">-- Semua Sumber Bantuan --</option>
                    <?php foreach ($sumberList as $sumber): ?>
                        <option value="<?= $sumber['id_sumber'] ?>" <?= ($id_sumber == $sumber['id_sumber']) ? 'selected' : '' ?>>
                            <?= e($sumber['nama_sumber']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
            </div>

            <div class="col-md-auto">
                <a href="<?= base_url('?page=jenis_bantuan') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Sumber Bantuan</th>
                        <th>Nama Jenis Bantuan</th>
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
                                <td><?= e($row['nama_jenis_bantuan']) ?></td>
                                <td><?= !empty($row['created_at']) ? e(date('d-m-Y H:i', strtotime($row['created_at']))) : '-' ?></td>
                                <td><?= !empty($row['updated_at']) ? e(date('d-m-Y H:i', strtotime($row['updated_at']))) : '-' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= base_url('jenis_bantuan/edit.php?id=' . $row['id_jenis_bantuan']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="<?= base_url('jenis_bantuan/delete.php?id=' . $row['id_jenis_bantuan']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildJenisPageUrl($page_number - 1, $keyword, $id_sumber) ?>">Sebelumnya</a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildJenisPageUrl($i, $keyword, $id_sumber) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildJenisPageUrl($page_number + 1, $keyword, $id_sumber) ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>