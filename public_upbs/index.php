<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

function formatNumberId($value): string
{
    if ($value === null || $value === '') return '0';
    $num = (float)$value;
    return ((float)(int)$num === $num)
        ? number_format($num, 0, ',', '.')
        : number_format($num, 2, ',', '.');
}

$q = trim($_GET['q'] ?? '');

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

$where = ["u.is_active = 1"];
$params = [];

if ($q !== '') {
    $where[] = "(k.nama_komoditas LIKE :q OR v.nama_varietas LIKE :q)";
    $params['q'] = "%{$q}%";
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

$statsSql = "
    SELECT
        COUNT(DISTINCT k.id_komoditas) AS total_komoditas,
        COUNT(DISTINCT d.id_varietas) AS total_varietas,
        COUNT(DISTINCT p.id) AS total_provinsi,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok
    FROM ($latestReportSql) lr
    INNER JOIN upbs u ON lr.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    LEFT JOIN provinsis p ON s.provinsi_id = p.id
    INNER JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    $whereSql
";
$stmtStats = $pdo->prepare($statsSql);
foreach ($params as $k => $v) {
    $stmtStats->bindValue(':' . $k, $v);
}
$stmtStats->execute();
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$listSql = "
    SELECT
        k.id_komoditas,
        k.nama_komoditas,
        COUNT(DISTINCT d.id_varietas) AS total_varietas,
        COUNT(DISTINCT p.id) AS total_provinsi,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok,
        MAX(lr.tanggal_laporan) AS update_terakhir
    FROM ($latestReportSql) lr
    INNER JOIN upbs u ON lr.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    LEFT JOIN provinsis p ON s.provinsi_id = p.id
    INNER JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    $whereSql
    GROUP BY k.id_komoditas, k.nama_komoditas
    ORDER BY total_stok DESC, total_varietas DESC, k.nama_komoditas ASC
";
$stmtList = $pdo->prepare($listSql);
foreach ($params as $k => $v) {
    $stmtList->bindValue(':' . $k, $v);
}
$stmtList->execute();
$komoditas = $stmtList->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Ketersediaan Benih UPBS Indonesia';
$activePublicPage = 'home';
require_once __DIR__ . '/partials/layout_top.php';
?>

<section class="hero-public mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <div class="mb-2 text-uppercase small fw-semibold text-success">Portal Publik</div>
            <div class="hero-title mb-3">Ketersediaan Benih UPBS Indonesia</div>
            <div class="hero-subtitle">
                Jelajahi komoditas, varietas, provinsi penyedia, hingga UPBS pengelola yang dapat dihubungi langsung melalui WhatsApp.
            </div>
        </div>
        <div class="col-lg-4">
            <form method="GET" action="<?= base_url('public_upbs/index.php') ?>" class="search-shell">
                <label class="form-label fw-semibold">Cari komoditas atau varietas</label>
                <div class="input-group">
                    <input type="text" name="q" class="form-control form-control-lg" placeholder="Contoh: Inpari, Jagung, Cabai" value="<?= e($q) ?>">
                    <button class="btn btn-success px-4" type="submit">Cari</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="mb-4">
    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-value"><?= formatNumberId($stats['total_komoditas'] ?? 0) ?></div>
                    <div class="stats-label">Komoditas</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-value"><?= formatNumberId($stats['total_varietas'] ?? 0) ?></div>
                    <div class="stats-label">Varietas / Galur</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-value"><?= formatNumberId($stats['total_provinsi'] ?? 0) ?></div>
                    <div class="stats-label">Provinsi Penyedia</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-value"><?= formatNumberId($stats['total_stok'] ?? 0) ?></div>
                    <div class="stats-label">Total Stok</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title">Komoditas Tersedia</div>
            <div class="section-subtitle">Klik komoditas untuk melihat varietas yang tersedia.</div>
        </div>
    </div>

    <div class="row g-3">
        <?php if ($komoditas): ?>
            <?php foreach ($komoditas as $item): ?>
                <div class="col-md-6 col-xl-4">
                    <a href="<?= base_url('public_upbs/varietas.php?komoditas_id=' . $item['id_komoditas']) ?>" class="text-decoration-none">
                        <div class="card stock-card">
                            <div class="card-body p-4">
                                <div class="stock-title mb-2"><?= e($item['nama_komoditas']) ?: '-' ?></div>
                                <div class="stock-meta mb-1">Varietas: <?= formatNumberId($item['total_varietas']) ?></div>
                                <div class="stock-meta mb-1">Provinsi: <?= formatNumberId($item['total_provinsi']) ?></div>
                                <div class="stock-number my-3"><?= formatNumberId($item['total_stok']) ?></div>
                                <div class="stock-meta">Update terakhir: <?= !empty($item['update_terakhir']) ? e(date('d-m-Y', strtotime($item['update_terakhir']))) : '-' ?></div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card catalog-card">
                    <div class="card-body text-center py-5 text-muted">
                        Belum ada data komoditas yang sesuai dengan pencarian.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>