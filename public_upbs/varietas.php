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

$komoditasId = (int)($_GET['komoditas_id'] ?? 0);
if ($komoditasId <= 0) {
    header('Location: ' . base_url('public_upbs/index.php'));
    exit;
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

$stmtKom = $pdo->prepare("SELECT id_komoditas, nama_komoditas FROM komoditas WHERE id_komoditas = ? LIMIT 1");
$stmtKom->execute([$komoditasId]);
$komoditas = $stmtKom->fetch(PDO::FETCH_ASSOC);

if (!$komoditas) {
    header('Location: ' . base_url('public_upbs/index.php'));
    exit;
}

$sql = "
    SELECT
        v.id_varietas,
        v.nama_varietas,
        COUNT(DISTINCT p.id) AS total_provinsi,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok,
        MAX(lr.tanggal_laporan) AS update_terakhir
    FROM ($latestReportSql) lr
    INNER JOIN upbs u ON lr.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    LEFT JOIN provinsis p ON s.provinsi_id = p.id
    INNER JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    WHERE u.is_active = 1
      AND d.id_komoditas = :komoditas_id
    GROUP BY v.id_varietas, v.nama_varietas
    ORDER BY total_stok DESC, total_provinsi DESC, v.nama_varietas ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':komoditas_id', $komoditasId, PDO::PARAM_INT);
$stmt->execute();
$varietas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Varietas - ' . $komoditas['nama_komoditas'];
$activePublicPage = 'home';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="mb-4">
    <a href="<?= base_url('public_upbs/index.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Komoditas
    </a>
</div>

<section class="hero-public mb-4">
    <div class="hero-title mb-2"><?= e($komoditas['nama_komoditas']) ?></div>
    <div class="hero-subtitle">Pilih varietas untuk melihat provinsi yang menyediakan benih tersebut.</div>
</section>

<section>
    <div class="row g-3">
        <?php if ($varietas): ?>
            <?php foreach ($varietas as $item): ?>
                <div class="col-md-6 col-xl-4">
                    <a href="<?= base_url('public_upbs/provinsi.php?komoditas_id=' . $komoditasId . '&varietas_id=' . $item['id_varietas']) ?>" class="text-decoration-none">
                        <div class="card stock-card">
                            <div class="card-body p-4">
                                <div class="stock-title mb-2"><?= e($item['nama_varietas']) ?: '-' ?></div>
                                <div class="stock-meta mb-1">Provinsi penyedia: <?= formatNumberId($item['total_provinsi']) ?></div>
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
                        Belum ada varietas terdata untuk komoditas ini.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>