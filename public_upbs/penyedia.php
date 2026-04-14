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

function normalizeWaNumber(?string $phone): string
{
    $phone = preg_replace('/\D+/', '', (string)$phone);
    if ($phone === '') return '';

    if (strpos($phone, '62') === 0) return $phone;
    if (strpos($phone, '0') === 0) return '62' . substr($phone, 1);
    if (strpos($phone, '8') === 0) return '62' . $phone;

    return $phone;
}

$komoditasId = (int)($_GET['komoditas_id'] ?? 0);
$varietasId  = (int)($_GET['varietas_id'] ?? 0);
$provinsiId  = (int)($_GET['provinsi_id'] ?? 0);

if ($komoditasId <= 0 || $varietasId <= 0 || $provinsiId <= 0) {
    header('Location: ' . base_url('public_upbs/index.php'));
    exit;
}

$stmtMeta = $pdo->prepare("
    SELECT
        k.nama_komoditas,
        v.nama_varietas,
        p.name AS nama_provinsi
    FROM komoditas k
    CROSS JOIN varietas v
    CROSS JOIN provinsis p
    WHERE k.id_komoditas = ?
      AND v.id_varietas = ?
      AND p.id = ?
    LIMIT 1
");
$stmtMeta->execute([$komoditasId, $varietasId, $provinsiId]);
$meta = $stmtMeta->fetch(PDO::FETCH_ASSOC);

if (!$meta) {
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

$sql = "
    SELECT
        u.id_upbs,
        u.nama_upbs,
        u.no_hp_pengelola,
        s.nama_satker,
        s.alamat,
        kab.name AS nama_kabupaten,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok,
        MIN(d.harga_min) AS harga_min,
        MAX(d.harga_max) AS harga_max,
        MAX(d.harga_keterangan) AS harga_keterangan,
        MAX(lr.tanggal_laporan) AS update_terakhir,
        MAX(st.simbol) AS simbol
    FROM ($latestReportSql) lr
    INNER JOIN upbs u ON lr.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    LEFT JOIN kabupatens kab ON s.kabupaten_id = kab.id
    INNER JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    LEFT JOIN satuan st ON d.id_satuan_stok = st.id_satuan
    WHERE u.is_active = 1
      AND s.provinsi_id = :provinsi_id
      AND d.id_komoditas = :komoditas_id
      AND d.id_varietas = :varietas_id
    GROUP BY u.id_upbs, u.nama_upbs, u.no_hp_pengelola, s.nama_satker, s.alamat, kab.name
    ORDER BY total_stok DESC, u.nama_upbs ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':provinsi_id', $provinsiId, PDO::PARAM_INT);
$stmt->bindValue(':komoditas_id', $komoditasId, PDO::PARAM_INT);
$stmt->bindValue(':varietas_id', $varietasId, PDO::PARAM_INT);
$stmt->execute();
$penyedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'UPBS Penyedia - ' . $meta['nama_varietas'];
$activePublicPage = 'home';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="mb-4">
    <a href="<?= base_url('public_upbs/provinsi.php?komoditas_id=' . $komoditasId . '&varietas_id=' . $varietasId) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Provinsi
    </a>
</div>

<section class="hero-public mb-4">
    <div class="hero-title mb-2"><?= e($meta['nama_varietas']) ?></div>
    <div class="hero-subtitle">
        Komoditas: <?= e($meta['nama_komoditas']) ?> · Provinsi: <?= e($meta['nama_provinsi']) ?>
    </div>
</section>

<section>
    <div class="row g-3">
        <?php if ($penyedia): ?>
            <?php foreach ($penyedia as $item): ?>
                <?php
                    $stokTampil = formatNumberId($item['total_stok']) . (!empty($item['simbol']) ? ' ' . $item['simbol'] : '');
                    $hargaTampil = '-';
                    if ($item['harga_min'] !== null && $item['harga_min'] !== '') {
                        if ($item['harga_max'] !== null && $item['harga_max'] !== '' && (float)$item['harga_max'] !== (float)$item['harga_min']) {
                            $hargaTampil = 'Rp ' . formatNumberId($item['harga_min']) . ' - Rp ' . formatNumberId($item['harga_max']);
                        } else {
                            $hargaTampil = 'Rp ' . formatNumberId($item['harga_min']);
                        }

                        if (!empty($item['simbol'])) {
                            $hargaTampil .= '/' . $item['simbol'];
                        }
                    }

                    $wa = normalizeWaNumber($item['no_hp_pengelola']);
                    $pesan = rawurlencode('Halo, saya melihat informasi ketersediaan benih di Portal Benih UPBS dan ingin menanyakan detail lebih lanjut.');
                    $waLink = $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . $pesan : '';
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card stock-card">
                        <div class="card-body p-4">
                            <div class="stock-title mb-1"><?= e($item['nama_upbs']) ?></div>
                            <div class="stock-meta mb-3"><?= e($item['nama_satker']) ?></div>

                            <div class="stock-number"><?= e($stokTampil) ?></div>
                            <div class="stock-meta mb-3">Harga: <?= e($hargaTampil) ?></div>

                            <div class="stock-meta mb-1">Kabupaten: <?= !empty($item['nama_kabupaten']) ? e($item['nama_kabupaten']) : '-' ?></div>
                            <div class="stock-meta mb-1">Update: <?= !empty($item['update_terakhir']) ? e(date('d-m-Y', strtotime($item['update_terakhir']))) : '-' ?></div>
                            <div class="stock-meta mb-3">Alamat: <?= !empty($item['alamat']) ? e($item['alamat']) : '-' ?></div>

                            <?php if (!empty($item['harga_keterangan'])): ?>
                                <div class="small text-muted mb-3">
                                    <strong>Keterangan harga:</strong><br><?= e($item['harga_keterangan']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($waLink !== ''): ?>
                                <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="btn btn-success w-100">
                                    <i class="bi bi-whatsapp me-1"></i> Hubungi Pengelola
                                </a>
                            <?php else: ?>
                                <div class="badge badge-soft-secondary rounded-pill px-3 py-2">Nomor WA belum tersedia</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card catalog-card">
                    <div class="card-body text-center py-5 text-muted">
                        Belum ada UPBS yang menyediakan varietas ini di provinsi tersebut.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>