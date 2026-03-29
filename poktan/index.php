<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$keyword      = trim($_GET['keyword'] ?? '');
$provinsi_id  = trim($_GET['provinsi_id'] ?? '');
$kabupaten_id = trim($_GET['kabupaten_id'] ?? '');
$page_number  = max(1, (int)($_GET['halaman'] ?? 1));
$limit        = 10;
$offset       = ($page_number - 1) * $limit;

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();

$kabupatenList = [];
if ($provinsi_id !== '') {
    $stmtKab = $pdo->prepare("SELECT id, name FROM kabupatens WHERE provinsi_id = ? ORDER BY name ASC");
    $stmtKab->execute([$provinsi_id]);
    $kabupatenList = $stmtKab->fetchAll();
}

/*
|--------------------------------------------------------------------------
| WHERE dinamis
|--------------------------------------------------------------------------
*/
$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "p.nama_poktan LIKE :keyword";
    $params['keyword'] = "%{$keyword}%";
}

if ($provinsi_id !== '') {
    $where[] = "p.provinsi_id = :provinsi_id";
    $params['provinsi_id'] = $provinsi_id;
}

if ($kabupaten_id !== '') {
    $where[] = "p.kabupaten_id = :kabupaten_id";
    $params['kabupaten_id'] = $kabupaten_id;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Hitung total data
|--------------------------------------------------------------------------
*/
$countSql = "SELECT COUNT(*) AS total
             FROM poktan p
             LEFT JOIN provinsis pr ON p.provinsi_id = pr.id
             LEFT JOIN kabupatens kb ON p.kabupaten_id = kb.id
             LEFT JOIN kecamatans kc ON p.kecamatan_id = kc.id
             $whereSql";

$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalData = (int)$stmtCount->fetch()['total'];

$totalPage = max(1, (int)ceil($totalData / $limit));

if ($page_number > $totalPage) {
    $page_number = $totalPage;
    $offset = ($page_number - 1) * $limit;
}

/*
|--------------------------------------------------------------------------
| Ambil data utama
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
            p.id_poktan,
            p.nama_poktan,
            p.nama_ketua_poktan,
            p.alamat,
            pr.name AS provinsi,
            kb.name AS kabupaten,
            kc.name AS kecamatan
        FROM poktan p
        LEFT JOIN provinsis pr ON p.provinsi_id = pr.id
        LEFT JOIN kabupatens kb ON p.kabupaten_id = kb.id
        LEFT JOIN kecamatans kc ON p.kecamatan_id = kc.id
        $whereSql
        ORDER BY p.id_poktan DESC
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
| Helper URL pagination
|--------------------------------------------------------------------------
*/
function buildPoktanPageUrl($page, $keyword, $provinsi_id, $kabupaten_id)
{
    $query = [
        'page' => 'poktan',
        'halaman' => $page,
    ];

    if ($keyword !== '') {
        $query['keyword'] = $keyword;
    }

    if ($provinsi_id !== '') {
        $query['provinsi_id'] = $provinsi_id;
    }

    if ($kabupaten_id !== '') {
        $query['kabupaten_id'] = $kabupaten_id;
    }

    return base_url('?' . http_build_query($query));
}
?>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Data Poktan</h4>
            <a href="<?= base_url('poktan/create.php') ?>" class="btn btn-primary">+ Tambah Poktan</a>
        </div>

        <form method="GET" action="<?= base_url() ?>" class="row g-2 mb-3" id="filterFormPoktan">
            <input type="hidden" name="page" value="poktan">

            <div class="col-md-3">
                <input type="text" 
                       name="keyword" 
                       class="form-control" 
                       placeholder="Cari nama poktan..." 
                       value="<?= e($keyword) ?>">
            </div>

            <div class="col-md-3">
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
                <button type="submit" class="btn btn-outline-primary">Filter</button>
            </div>

            <div class="col-md-auto">
                <a href="<?= base_url('?page=poktan') ?>" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Nama Poktan</th>
                        <th>Nama Ketua Poktan</th>
                        <th>Alamat</th>
                        <th>Provinsi</th>
                        <th>Kabupaten</th>
                        <th>Kecamatan</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= e($row['nama_poktan']) ?></td>
                                <td><?= e($row['nama_ketua_poktan']) ?></td>
                                <td><?= e($row['alamat']) ?></td>
                                <td><?= e($row['provinsi']) ?></td>
                                <td><?= e($row['kabupaten']) ?></td>
                                <td><?= e($row['kecamatan']) ?></td>
                                <td>
                                    <a href="<?= base_url('poktan/edit.php?id=' . $row['id_poktan']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="<?= base_url('poktan/delete.php?id=' . $row['id_poktan']) ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPage > 1): ?>
            <nav class="mt-4">
                <ul class="pagination flex-wrap">
                    <li class="page-item <?= ($page_number <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number <= 1) ? '#' : buildPoktanPageUrl($page_number - 1, $keyword, $provinsi_id, $kabupaten_id) ?>">
                            Sebelumnya
                        </a>
                    </li>

                    <?php
                    $start = max(1, $page_number - 2);
                    $end   = min($totalPage, $page_number + 2);
                    ?>

                    <?php if ($start > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPoktanPageUrl(1, $keyword, $provinsi_id, $kabupaten_id) ?>">1</a>
                        </li>
                        <?php if ($start > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page_number) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildPoktanPageUrl($i, $keyword, $provinsi_id, $kabupaten_id) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end < $totalPage): ?>
                        <?php if ($end < $totalPage - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPoktanPageUrl($totalPage, $keyword, $provinsi_id, $kabupaten_id) ?>"><?= $totalPage ?></a>
                        </li>
                    <?php endif; ?>

                    <li class="page-item <?= ($page_number >= $totalPage) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page_number >= $totalPage) ? '#' : buildPoktanPageUrl($page_number + 1, $keyword, $provinsi_id, $kabupaten_id) ?>">
                            Berikutnya
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#filter_provinsi_id').on('change', function() {
        let provinsiId = $(this).val();

        $('#filter_kabupaten_id').html('<option value="">-- Semua Kabupaten --</option>');

        if (provinsiId) {
            $.ajax({
                url: '<?= base_url("ajax/get_kabupaten_json.php") ?>',
                type: 'GET',
                dataType: 'json',
                data: { provinsi_id: provinsiId },
                success: function(response) {
                    let options = '<option value="">-- Semua Kabupaten --</option>';
                    $.each(response, function(i, item) {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });
                    $('#filter_kabupaten_id').html(options);
                }
            });
        }
    });
});
</script>