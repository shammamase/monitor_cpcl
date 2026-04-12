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

$id_detail_stok = (int)($_GET['id'] ?? 0);

if ($id_detail_stok <= 0) {
    header('Location: ' . base_url('public_upbs/index.php'));
    exit;
}

$detailSql = "
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
        st.nama_satuan,
        st.simbol,
        u.nama_upbs,
        s.nama_satker,
        s.alamat,
        p.name AS nama_provinsi,
        kab.name AS nama_kabupaten
    FROM laporan_stok_upbs_detail d
    INNER JOIN laporan_stok_upbs lr ON d.id_laporan_stok = lr.id_laporan_stok
    INNER JOIN upbs u ON lr.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    LEFT JOIN provinsis p ON s.provinsi_id = p.id
    LEFT JOIN kabupatens kab ON s.kabupaten_id = kab.id
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    LEFT JOIN kelas_benih kb ON d.id_kelas_benih = kb.id_kelas_benih
    LEFT JOIN benih_sumber bs ON d.id_benih_sumber = bs.id_benih_sumber
    LEFT JOIN satuan st ON d.id_satuan_stok = st.id_satuan
    WHERE d.id_detail_stok = ?
    LIMIT 1
";
$stmtDetail = $pdo->prepare($detailSql);
$stmtDetail->execute([$id_detail_stok]);
$item = $stmtDetail->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Location: ' . base_url('public_upbs/index.php'));
    exit;
}

$relatedSql = "
    SELECT
        d.id_detail_stok,
        d.stok_tersedia,
        d.harga_min,
        k.nama_komoditas,
        v.nama_varietas,
        st.simbol
    FROM laporan_stok_upbs_detail d
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    LEFT JOIN satuan st ON d.id_satuan_stok = st.id_satuan
    WHERE d.id_laporan_stok = ?
      AND d.id_detail_stok != ?
    ORDER BY d.stok_tersedia DESC, d.id_detail_stok DESC
    LIMIT 6
";
$stmtRelated = $pdo->prepare($relatedSql);
$stmtRelated->execute([$item['id_laporan_stok'], $id_detail_stok]);
$relatedItems = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);

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

$pageTitle = 'Detail Benih - Portal UPBS';
$activePublicPage = 'home';

require_once __DIR__ . '/partials/layout_top.php';
?>

<style>
    .detail-hero {
        border: 0;
        border-radius: 26px;
        background: linear-gradient(135deg, #ffffff, #f7fbf8);
        box-shadow: 0 14px 28px rgba(0,0,0,.05);
    }

    .detail-title {
        font-size: 2rem;
        font-weight: 800;
        color: #1f2937;
        line-height: 1.15;
    }

    .detail-subtitle {
        color: #6b7280;
        font-size: 1rem;
    }

    .metric-card,
    .meta-card,
    .related-card {
        border: 0;
        border-radius: 22px;
        box-shadow: 0 10px 24px rgba(0,0,0,.05);
    }

    .metric-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: #166534;
    }

    .metric-label {
        color: #6b7280;
        font-size: .92rem;
    }

    .meta-label {
        color: #6b7280;
        font-size: .88rem;
        margin-bottom: 4px;
    }

    .meta-value {
        font-weight: 600;
        color: #1f2937;
    }

    .related-item {
        border: 1px solid #edf1f3;
        border-radius: 18px;
        background: #fff;
        height: 100%;
    }
</style>

<div class="mb-4">
    <a href="<?= base_url('public_upbs/index.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
    </a>
</div>

<section class="card detail-hero mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <div class="mb-2">
                    <span class="badge <?= e($badge['class']) ?> rounded-pill"><?= e($badge['label']) ?></span>
                </div>
                <div class="detail-title"><?= e($item['nama_komoditas']) ?: '-' ?></div>
                <div class="detail-subtitle mt-2">
                    Varietas / Galur: <?= e($item['nama_varietas']) ?: '-' ?>
                </div>
            </div>
            <div class="text-lg-end">
                <div class="small text-muted">Update terakhir</div>
                <div class="fw-semibold"><?= !empty($item['tanggal_laporan']) ? e(date('d-m-Y', strtotime($item['tanggal_laporan']))) : '-' ?></div>
            </div>
        </div>
    </div>
</section>

<section class="mb-4">
    <div class="row g-3">
        <div class="col-md-6 col-xl-4">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-value"><?= e($stokTampil) ?></div>
                    <div class="metric-label">Stok Tersedia</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-value"><?= e($hargaTampil) ?></div>
                    <div class="metric-label">Harga</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-value"><?= !empty($item['nama_kelas']) ? e($item['nama_kelas']) : '-' ?></div>
                    <div class="metric-label">Kelas Benih</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-4">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card meta-card h-100">
                <div class="card-body p-4">
                    <div class="section-title mb-3">Informasi Benih</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="meta-label">Komoditas</div>
                            <div class="meta-value"><?= e($item['nama_komoditas']) ?: '-' ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="meta-label">Varietas / Galur</div>
                            <div class="meta-value"><?= e($item['nama_varietas']) ?: '-' ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="meta-label">Kelas Benih</div>
                            <div class="meta-value"><?= !empty($item['nama_kelas']) ? e($item['nama_kelas']) : '-' ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="meta-label">Benih Sumber</div>
                            <div class="meta-value"><?= !empty($item['nama_benih_sumber']) ? e($item['nama_benih_sumber']) : '-' ?></div>
                        </div>

                        <div class="col-12">
                            <div class="meta-label">Keterangan Harga</div>
                            <div class="meta-value"><?= !empty($item['harga_keterangan']) ? e($item['harga_keterangan']) : '-' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card meta-card h-100">
                <div class="card-body p-4">
                    <div class="section-title mb-3">Asal Data</div>

                    <div class="mb-3">
                        <div class="meta-label">UPBS</div>
                        <div class="meta-value"><?= e($item['nama_upbs']) ?: '-' ?></div>
                    </div>

                    <div class="mb-3">
                        <div class="meta-label">Satker</div>
                        <div class="meta-value"><?= e($item['nama_satker']) ?: '-' ?></div>
                    </div>

                    <div class="mb-3">
                        <div class="meta-label">Provinsi</div>
                        <div class="meta-value"><?= e($item['nama_provinsi']) ?: '-' ?></div>
                    </div>

                    <div class="mb-3">
                        <div class="meta-label">Kabupaten</div>
                        <div class="meta-value"><?= e($item['nama_kabupaten']) ?: '-' ?></div>
                    </div>

                    <div>
                        <div class="meta-label">Alamat</div>
                        <div class="meta-value"><?= !empty($item['alamat']) ? e($item['alamat']) : '-' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($relatedItems): ?>
<section>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title">Item Lain dari Laporan yang Sama</div>
            <div class="section-subtitle">Daftar benih lain yang dipublikasikan pada pembaruan yang sama.</div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($relatedItems as $rel): ?>
            <?php
                $stokRel = formatNumberId($rel['stok_tersedia']) . (!empty($rel['simbol']) ? ' ' . $rel['simbol'] : '');
                $hargaRel = '-';
                if ($rel['harga_min'] !== null && $rel['harga_min'] !== '') {
                    $hargaRel = 'Rp ' . formatNumberId($rel['harga_min']);
                    if (!empty($rel['simbol'])) {
                        $hargaRel .= '/' . $rel['simbol'];
                    }
                }
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card related-item">
                    <div class="card-body">
                        <div class="fw-bold mb-1"><?= e($rel['nama_komoditas']) ?: '-' ?></div>
                        <div class="text-muted small mb-2"><?= e($rel['nama_varietas']) ?: '-' ?></div>
                        <div class="mb-1">Stok: <strong><?= e($stokRel) ?></strong></div>
                        <div class="mb-3">Harga: <strong><?= e($hargaRel) ?></strong></div>
                        <a href="<?= base_url('public_upbs/detail.php?id=' . $rel['id_detail_stok']) ?>" class="btn btn-sm btn-outline-success">Lihat Detail</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>