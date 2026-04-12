<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

function formatNumberId($value): string
{
    if ($value === null || $value === '') {
        return '0';
    }

    $num = (float)$value;
    if ((float)(int)$num === $num) {
        return number_format($num, 0, ',', '.');
    }

    return number_format($num, 2, ',', '.');
}

function stokBadge(float $stok): array
{
    if ($stok <= 0) {
        return ['label' => 'Habis', 'class' => 'badge-soft-danger'];
    }

    if ($stok <= 1000) {
        return ['label' => 'Terbatas', 'class' => 'badge-soft-warning'];
    }

    return ['label' => 'Tersedia', 'class' => 'badge-soft-success'];
}

$search       = trim($_GET['q'] ?? '');
$provinsiId   = trim($_GET['provinsi_id'] ?? '');
$komoditasId  = trim($_GET['komoditas_id'] ?? '');
$tersediaOnly = isset($_GET['tersedia']) ? 1 : 0;
$view         = trim($_GET['view'] ?? 'card');
$pageNumber   = max(1, (int)($_GET['page'] ?? 1));
$limit        = 12;
$offset       = ($pageNumber - 1) * $limit;

if (!in_array($view, ['card', 'table'], true)) {
    $view = 'card';
}

$latestReportSql = "
    SELECT l1.id_laporan_stok, l1.id_upbs, l1.tanggal_laporan
    FROM laporan_stok_upbs l1
    INNER JOIN (
        SELECT id_upbs, MAX(tanggal_laporan) AS max_tanggal
        FROM laporan_stok_upbs
        GROUP BY id_upbs
    ) tgl ON tgl.id_upbs = l1.id_upbs
         AND tgl.max_tanggal = l1.tanggal_laporan
    INNER JOIN (
        SELECT id_upbs, tanggal_laporan, MAX(id_laporan_stok) AS max_id
        FROM laporan_stok_upbs
        GROUP BY id_upbs, tanggal_laporan
    ) idx ON idx.id_upbs = l1.id_upbs
         AND idx.tanggal_laporan = l1.tanggal_laporan
         AND idx.max_id = l1.id_laporan_stok
";

$baseJoinSql = "
    FROM ($latestReportSql) lr
    INNER JOIN upbs u ON lr.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    LEFT JOIN provinsis p ON s.provinsi_id = p.id
    LEFT JOIN kabupatens kab ON s.kabupaten_id = kab.id
    INNER JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    LEFT JOIN kelas_benih kb ON d.id_kelas_benih = kb.id_kelas_benih
    LEFT JOIN benih_sumber bs ON d.id_benih_sumber = bs.id_benih_sumber
    LEFT JOIN satuan st ON d.id_satuan_stok = st.id_satuan
";

$where = ["u.is_active = 1"];
$params = [];

if ($search !== '') {
    $where[] = "(k.nama_komoditas LIKE :search
                OR v.nama_varietas LIKE :search
                OR u.nama_upbs LIKE :search
                OR s.nama_satker LIKE :search
                OR p.name LIKE :search
                OR kab.name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($provinsiId !== '') {
    $where[] = "p.id = :provinsi_id";
    $params['provinsi_id'] = $provinsiId;
}

if ($komoditasId !== '') {
    $where[] = "k.id_komoditas = :komoditas_id";
    $params['komoditas_id'] = $komoditasId;
}

if ($tersediaOnly === 1) {
    $where[] = "d.stok_tersedia > 0";
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

/*
|--------------------------------------------------------------------------
| Statistik nasional
|--------------------------------------------------------------------------
*/
$statsSql = "
    SELECT
        COUNT(DISTINCT p.id) AS total_provinsi,
        COUNT(DISTINCT u.id_upbs) AS total_upbs,
        COUNT(DISTINCT d.id_varietas) AS total_varietas,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok
    $baseJoinSql
    WHERE u.is_active = 1
";
$stmtStats = $pdo->prepare($statsSql);
$stmtStats->execute();
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Filter lists
|--------------------------------------------------------------------------
*/
$provinsiListSql = "
    SELECT DISTINCT p.id, p.name
    $baseJoinSql
    WHERE u.is_active = 1
      AND p.id IS NOT NULL
    ORDER BY p.name ASC
";
$stmtProvinsi = $pdo->prepare($provinsiListSql);
$stmtProvinsi->execute();
$provinsiList = $stmtProvinsi->fetchAll(PDO::FETCH_ASSOC);

$komoditasListSql = "
    SELECT DISTINCT k.id_komoditas, k.nama_komoditas
    $baseJoinSql
    WHERE u.is_active = 1
      AND k.id_komoditas IS NOT NULL
    ORDER BY k.nama_komoditas ASC
";
$stmtKomoditas = $pdo->prepare($komoditasListSql);
$stmtKomoditas->execute();
$komoditasList = $stmtKomoditas->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Count hasil katalog
|--------------------------------------------------------------------------
*/
$countSql = "
    SELECT COUNT(*) AS total
    $baseJoinSql
    $whereSql
";
$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $stmtCount->bindValue(':' . $key, $value);
}
$stmtCount->execute();
$totalData = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

$totalPage = max(1, (int)ceil($totalData / $limit));
if ($pageNumber > $totalPage) {
    $pageNumber = $totalPage;
    $offset = ($pageNumber - 1) * $limit;
}

/*
|--------------------------------------------------------------------------
| Data katalog
|--------------------------------------------------------------------------
*/
$listSql = "
    SELECT
        d.id_detail_stok,
        d.id_laporan_stok,
        d.stok_tersedia,
        d.harga_min,
        d.harga_keterangan,
        lr.tanggal_laporan,
        k.nama_komoditas,
        v.nama_varietas,
        kb.nama_kelas,
        bs.nama_benih_sumber,
        st.simbol,
        u.nama_upbs,
        s.nama_satker,
        p.name AS nama_provinsi,
        kab.name AS nama_kabupaten
    $baseJoinSql
    $whereSql
    ORDER BY d.stok_tersedia DESC, lr.tanggal_laporan DESC, d.id_detail_stok DESC
    LIMIT :limit OFFSET :offset
";
$stmtList = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $stmtList->bindValue(':' . $key, $value);
}
$stmtList->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmtList->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtList->execute();
$items = $stmtList->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Ringkasan provinsi
|--------------------------------------------------------------------------
*/
$provinceSummarySql = "
    SELECT
        p.id,
        p.name,
        COUNT(DISTINCT u.id_upbs) AS total_upbs,
        COUNT(d.id_detail_stok) AS total_item,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok
    $baseJoinSql
    WHERE u.is_active = 1
      AND p.id IS NOT NULL
    GROUP BY p.id, p.name
    ORDER BY total_stok DESC, total_item DESC
    LIMIT 12
";
$stmtProvinceSummary = $pdo->prepare($provinceSummarySql);
$stmtProvinceSummary->execute();
$provinceSummary = $stmtProvinceSummary->fetchAll(PDO::FETCH_ASSOC);

function buildPublicUrl(array $overrides = []): string
{
    $query = array_merge($_GET, $overrides);

    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    return base_url('public_upbs/index.php' . (!empty($query) ? '?' . http_build_query($query) : ''));
}

$pageTitle = 'Portal Ketersediaan Benih UPBS';
$activePublicPage = 'home';

require_once __DIR__ . '/partials/layout_top.php';
?>

<section class="hero-public mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="mb-2 text-uppercase small fw-semibold text-success">Portal Publik</div>
            <div class="hero-title mb-3">Informasi Ketersediaan Benih UPBS Seluruh Indonesia</div>
            <div class="hero-subtitle">
                Masyarakat, petani, penyuluh, dan stakeholder dapat melihat stok benih terbaru berdasarkan provinsi, komoditas, varietas, dan UPBS pengelola secara terbuka.
            </div>
        </div>
        <div class="col-lg-5">
            <div class="search-shell">
                <form method="GET" action="<?= base_url('public_upbs/index.php') ?>" class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Cari cepat</label>
                        <input type="text" name="q" class="form-control form-control-lg" placeholder="Cari komoditas, varietas, provinsi, atau UPBS..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-6">
                        <select name="provinsi_id" class="form-select">
                            <option value="">Semua Provinsi</option>
                            <?php foreach ($provinsiList as $prov): ?>
                                <option value="<?= $prov['id'] ?>" <?= ($provinsiId == $prov['id']) ? 'selected' : '' ?>>
                                    <?= e($prov['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="komoditas_id" class="form-select">
                            <option value="">Semua Komoditas</option>
                            <?php foreach ($komoditasList as $kom): ?>
                                <option value="<?= $kom['id_komoditas'] ?>" <?= ($komoditasId == $kom['id_komoditas']) ? 'selected' : '' ?>>
                                    <?= e($kom['nama_komoditas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tersedia" id="tersedia" value="1" <?= $tersediaOnly ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tersedia">Tampilkan hanya yang tersedia</label>
                        </div>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-search me-1"></i> Cari Benih
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="mb-4">
    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <div class="stats-value"><?= formatNumberId($stats['total_provinsi'] ?? 0) ?></div>
                        <div class="stats-label">Provinsi Aktif</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="stats-value"><?= formatNumberId($stats['total_upbs'] ?? 0) ?></div>
                        <div class="stats-label">UPBS Terpublikasi</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon"><i class="bi bi-flower1"></i></div>
                    <div>
                        <div class="stats-value"><?= formatNumberId($stats['total_varietas'] ?? 0) ?></div>
                        <div class="stats-label">Varietas / Galur</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="stats-value"><?= formatNumberId($stats['total_stok'] ?? 0) ?></div>
                        <div class="stats-label">Total Stok</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="katalog" class="mb-4">
    <div class="card filter-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="section-title">Katalog Benih</div>
                    <div class="section-subtitle">Menampilkan stok terbaru per UPBS yang sudah dipublikasikan.</div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="filter-chip">Total hasil: <?= formatNumberId($totalData) ?></span>
                    <div class="btn-group view-switch" role="group">
                        <a href="<?= e(buildPublicUrl(['view' => 'card', 'page' => 1])) ?>" class="btn btn-outline-success <?= $view === 'card' ? 'active' : '' ?>">
                            <i class="bi bi-grid"></i> Kartu
                        </a>
                        <a href="<?= e(buildPublicUrl(['view' => 'table', 'page' => 1])) ?>" class="btn btn-outline-success <?= $view === 'table' ? 'active' : '' ?>">
                            <i class="bi bi-table"></i> Tabel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($view === 'card'): ?>
        <div class="row g-3">
            <?php if ($items): ?>
                <?php foreach ($items as $item): ?>
                    <?php
                        $stok = (float)($item['stok_tersedia'] ?? 0);
                        $badge = stokBadge($stok);
                        $stokTampil = formatNumberId($stok) . (!empty($item['simbol']) ? ' ' . $item['simbol'] : '');
                        $hargaTampil = '-';
                        if ($item['harga_min'] !== null && $item['harga_min'] !== '') {
                            $hargaTampil = 'Rp ' . formatNumberId($item['harga_min']);
                            if (!empty($item['simbol'])) {
                                $hargaTampil .= '/' . $item['simbol'];
                            }
                        }
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card stock-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <div class="stock-title"><?= e($item['nama_komoditas']) ?: '-' ?></div>
                                        <div class="stock-meta"><?= e($item['nama_varietas']) ?: '-' ?></div>
                                    </div>
                                    <span class="badge <?= e($badge['class']) ?> rounded-pill"><?= e($badge['label']) ?></span>
                                </div>

                                <div class="mb-3">
                                    <div class="stock-number"><?= e($stokTampil) ?></div>
                                    <div class="stock-meta">Harga: <?= e($hargaTampil) ?></div>
                                </div>

                                <div class="stock-meta mb-1"><i class="bi bi-geo-alt me-1"></i><?= e($item['nama_provinsi']) ?: '-' ?><?= !empty($item['nama_kabupaten']) ? ' - ' . e($item['nama_kabupaten']) : '' ?></div>
                                <div class="stock-meta mb-1"><i class="bi bi-building me-1"></i><?= e($item['nama_upbs']) ?: '-' ?></div>
                                <div class="stock-meta mb-3"><i class="bi bi-calendar3 me-1"></i>Update <?= !empty($item['tanggal_laporan']) ? e(date('d-m-Y', strtotime($item['tanggal_laporan']))) : '-' ?></div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-muted"><?= !empty($item['nama_kelas']) ? e($item['nama_kelas']) : 'Tanpa kelas benih' ?></div>
                                    <a href="<?= base_url('public_upbs/detail.php?id=' . $item['id_detail_stok']) ?>" class="btn btn-success btn-sm">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card catalog-card">
                        <div class="card-body text-center py-5 text-muted">
                            Belum ada data yang sesuai dengan filter pencarian Anda.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card catalog-card">
            <div class="card-body">
                <div class="catalog-table-wrap">
                    <table class="table table-hover align-middle catalog-table">
                        <thead class="table-light">
                            <tr>
                                <th>Komoditas</th>
                                <th>Varietas / Galur</th>
                                <th>Kelas Benih</th>
                                <th>Benih Sumber</th>
                                <th>Stok</th>
                                <th>Harga</th>
                                <th>Provinsi</th>
                                <th>UPBS</th>
                                <th>Update</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items): ?>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                        $stok = (float)($item['stok_tersedia'] ?? 0);
                                        $stokTampil = formatNumberId($stok) . (!empty($item['simbol']) ? ' ' . $item['simbol'] : '');
                                        $hargaTampil = '-';
                                        if ($item['harga_min'] !== null && $item['harga_min'] !== '') {
                                            $hargaTampil = 'Rp ' . formatNumberId($item['harga_min']);
                                            if (!empty($item['simbol'])) {
                                                $hargaTampil .= '/' . $item['simbol'];
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?= e($item['nama_komoditas']) ?: '-' ?></td>
                                        <td><?= e($item['nama_varietas']) ?: '-' ?></td>
                                        <td><?= !empty($item['nama_kelas']) ? e($item['nama_kelas']) : '-' ?></td>
                                        <td><?= e($item['nama_benih_sumber']) ?: '-' ?></td>
                                        <td><?= e($stokTampil) ?></td>
                                        <td><?= e($hargaTampil) ?></td>
                                        <td><?= e($item['nama_provinsi']) ?: '-' ?></td>
                                        <td><?= e($item['nama_upbs']) ?: '-' ?></td>
                                        <td><?= !empty($item['tanggal_laporan']) ? e(date('d-m-Y', strtotime($item['tanggal_laporan']))) : '-' ?></td>
                                        <td>
                                            <a href="<?= base_url('public_upbs/detail.php?id=' . $item['id_detail_stok']) ?>" class="btn btn-sm btn-outline-success">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Belum ada data yang sesuai dengan filter pencarian Anda.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($totalPage > 1): ?>
        <nav class="mt-4">
            <ul class="pagination flex-wrap">
                <li class="page-item <?= $pageNumber <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $pageNumber <= 1 ? '#' : e(buildPublicUrl(['page' => $pageNumber - 1])) ?>">Sebelumnya</a>
                </li>

                <?php
                $start = max(1, $pageNumber - 2);
                $end = min($totalPage, $pageNumber + 2);
                ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i === $pageNumber ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(buildPublicUrl(['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $pageNumber >= $totalPage ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $pageNumber >= $totalPage ? '#' : e(buildPublicUrl(['page' => $pageNumber + 1])) ?>">Berikutnya</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<section id="sebaran" class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title">Sebaran Ketersediaan per Provinsi</div>
            <div class="section-subtitle">Ringkasan provinsi dengan stok terpublikasi tertinggi.</div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($provinceSummary as $prov): ?>
            <div class="col-md-6 col-xl-3">
                <a href="<?= e(buildPublicUrl(['provinsi_id' => $prov['id'], 'page' => 1])) ?>" class="text-decoration-none">
                    <div class="card province-card">
                        <div class="card-body">
                            <div class="province-name mb-2"><?= e($prov['name']) ?></div>
                            <div class="province-metric mb-1">UPBS: <?= formatNumberId($prov['total_upbs']) ?></div>
                            <div class="province-metric mb-1">Item: <?= formatNumberId($prov['total_item']) ?></div>
                            <div class="province-metric fw-semibold text-success">Stok: <?= formatNumberId($prov['total_stok']) ?></div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="tentang" class="mb-3">
    <div class="card info-card">
        <div class="card-body p-4">
            <div class="section-title mb-2">Tentang Portal</div>
            <div class="section-subtitle">
                Portal ini menyajikan data ketersediaan benih berdasarkan laporan terbaru yang diinput oleh berbagai UPBS di Indonesia. Informasi stok, harga, varietas, dan lokasi dapat berubah sesuai pembaruan dari masing-masing pengelola.
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>