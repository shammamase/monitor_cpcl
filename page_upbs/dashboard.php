<?php
require_once __DIR__ . '/auth_check.php';
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

function formatRupiah($value): string
{
    if ($value === null || $value === '') return 'Rp 0';
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function formatDateId(?string $date): string
{
    if (empty($date)) return '-';

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $ts = strtotime($date);
    if (!$ts) return '-';

    $d = (int)date('d', $ts);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);

    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

function percentValue($num, $den): float
{
    if ((float)$den <= 0) return 0;
    return round(((float)$num / (float)$den) * 100, 1);
}

function labelJenisUpbs(?string $jenis): string
{
    return match ($jenis) {
        'tanaman_pangan' => 'Tanaman Pangan',
        'hortikultura'   => 'Hortikultura',
        'perkebunan'     => 'Perkebunan',
        'peternakan'     => 'Peternakan',
        default          => '-',
    };
}

$id_satker   = (int)($userUpbs['id_satker'] ?? 0);
$nama_satker = $userUpbs['nama_satker'] ?? 'Satker';

$id_satker_sql = (int)$id_satker;

$success = $_SESSION['success_message_upbs'] ?? '';
$error   = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['success_message_upbs'], $_SESSION['error_message_upbs']);

$latestReportSql = "
    SELECT l1.id_laporan_stok, l1.id_upbs, l1.tanggal_laporan
    FROM laporan_stok_upbs l1
    INNER JOIN upbs u1 ON l1.id_upbs = u1.id_upbs
    INNER JOIN (
        SELECT l2.id_upbs, MAX(l2.tanggal_laporan) AS max_tanggal
        FROM laporan_stok_upbs l2
        INNER JOIN upbs u2 ON l2.id_upbs = u2.id_upbs
        WHERE u2.id_satker = {$id_satker_sql}
        GROUP BY l2.id_upbs
    ) tgl ON tgl.id_upbs = l1.id_upbs
         AND tgl.max_tanggal = l1.tanggal_laporan
    INNER JOIN (
        SELECT l3.id_upbs, l3.tanggal_laporan, MAX(l3.id_laporan_stok) AS max_id
        FROM laporan_stok_upbs l3
        INNER JOIN upbs u3 ON l3.id_upbs = u3.id_upbs
        WHERE u3.id_satker = {$id_satker_sql}
        GROUP BY l3.id_upbs, l3.tanggal_laporan
    ) idx ON idx.id_upbs = l1.id_upbs
         AND idx.tanggal_laporan = l1.tanggal_laporan
         AND idx.max_id = l1.id_laporan_stok
    WHERE u1.id_satker = {$id_satker_sql}
";

/*
|--------------------------------------------------------------------------
| Statistik master UPBS
|--------------------------------------------------------------------------
*/
$stmtMaster = $pdo->prepare("
    SELECT
        COUNT(*) AS total_upbs,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS total_upbs_aktif,
        SUM(CASE WHEN no_hp_pengelola IS NOT NULL AND no_hp_pengelola <> '' THEN 1 ELSE 0 END) AS total_kontak_wa,
        SUM(CASE WHEN jenis_upbs = 'tanaman_pangan' THEN 1 ELSE 0 END) AS j_tanaman_pangan,
        SUM(CASE WHEN jenis_upbs = 'hortikultura' THEN 1 ELSE 0 END) AS j_hortikultura,
        SUM(CASE WHEN jenis_upbs = 'perkebunan' THEN 1 ELSE 0 END) AS j_perkebunan,
        SUM(CASE WHEN jenis_upbs = 'peternakan' THEN 1 ELSE 0 END) AS j_peternakan
    FROM upbs
    WHERE id_satker = ?
");
$stmtMaster->execute([$id_satker]);
$masterStats = $stmtMaster->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Statistik laporan historis
|--------------------------------------------------------------------------
*/
$stmtHistory = $pdo->prepare("
    SELECT
        COUNT(*) AS total_laporan,
        MAX(l.tanggal_laporan) AS laporan_terbaru
    FROM laporan_stok_upbs l
    INNER JOIN upbs u ON l.id_upbs = u.id_upbs
    WHERE u.id_satker = ?
");
$stmtHistory->execute([$id_satker]);
$historyStats = $stmtHistory->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Statistik posisi stok terkini (berdasarkan laporan terbaru per UPBS)
|--------------------------------------------------------------------------
*/
$sqlCurrent = "
    SELECT
        COUNT(DISTINCT lr.id_upbs) AS upbs_terlapor,
        COUNT(d.id_detail_stok) AS total_item,
        COUNT(DISTINCT d.id_komoditas) AS total_komoditas,
        COUNT(DISTINCT d.id_varietas) AS total_varietas,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok,
        COALESCE(SUM(d.stok_tersedia * COALESCE(d.harga_min, 0)), 0) AS total_nilai,
        MAX(lr.tanggal_laporan) AS update_terakhir
    FROM ({$latestReportSql}) lr
    LEFT JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
";
$stmtCurrent = $pdo->query($sqlCurrent);
$currentStats = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Top komoditas
|--------------------------------------------------------------------------
*/
$sqlTopKomoditas = "
    SELECT
        k.nama_komoditas,
        COUNT(DISTINCT d.id_varietas) AS total_varietas,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok,
        COALESCE(SUM(d.stok_tersedia * COALESCE(d.harga_min, 0)), 0) AS total_nilai
    FROM ({$latestReportSql}) lr
    INNER JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    GROUP BY k.id_komoditas, k.nama_komoditas
    ORDER BY total_stok DESC, total_varietas DESC, k.nama_komoditas ASC
    LIMIT 5
";
$topKomoditas = $pdo->query($sqlTopKomoditas)->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Ringkasan per UPBS (berdasarkan laporan terbaru)
|--------------------------------------------------------------------------
*/
$sqlRecapUpbs = "
    SELECT
        u.id_upbs,
        u.kode_upbs,
        u.nama_upbs,
        u.jenis_upbs,
        u.is_active,
        u.no_hp_pengelola,
        lr.tanggal_laporan,
        COUNT(d.id_detail_stok) AS total_item,
        COUNT(DISTINCT d.id_varietas) AS total_varietas,
        COALESCE(SUM(d.stok_tersedia), 0) AS total_stok,
        COALESCE(SUM(d.stok_tersedia * COALESCE(d.harga_min, 0)), 0) AS total_nilai
    FROM upbs u
    LEFT JOIN ({$latestReportSql}) lr ON u.id_upbs = lr.id_upbs
    LEFT JOIN laporan_stok_upbs_detail d ON d.id_laporan_stok = lr.id_laporan_stok
    WHERE u.id_satker = {$id_satker_sql}
    GROUP BY
        u.id_upbs, u.kode_upbs, u.nama_upbs, u.jenis_upbs,
        u.is_active, u.no_hp_pengelola, lr.tanggal_laporan
    ORDER BY total_stok DESC, u.nama_upbs ASC
";
$recapUpbs = $pdo->query($sqlRecapUpbs)->fetchAll(PDO::FETCH_ASSOC);

$total_upbs        = (int)($masterStats['total_upbs'] ?? 0);
$total_upbs_aktif  = (int)($masterStats['total_upbs_aktif'] ?? 0);
$total_kontak_wa   = (int)($masterStats['total_kontak_wa'] ?? 0);
$total_laporan     = (int)($historyStats['total_laporan'] ?? 0);
$total_item        = (int)($currentStats['total_item'] ?? 0);
$total_komoditas   = (int)($currentStats['total_komoditas'] ?? 0);
$total_varietas    = (int)($currentStats['total_varietas'] ?? 0);
$total_stok        = (float)($currentStats['total_stok'] ?? 0);
$total_nilai       = (float)($currentStats['total_nilai'] ?? 0);
$upbs_terlapor     = (int)($currentStats['upbs_terlapor'] ?? 0);

$persen_upbs_aktif = percentValue($total_upbs_aktif, max($total_upbs, 1));
$persen_wa         = percentValue($total_kontak_wa, max($total_upbs, 1));
$persen_lapor      = percentValue($upbs_terlapor, max($total_upbs, 1));
$rata_item_upbs    = $upbs_terlapor > 0 ? round($total_item / $upbs_terlapor, 1) : 0;

$pageTitle = 'Dashboard - UPBS';
$activeMenu = 'dashboard';
$activeSubmenu = '';

require_once __DIR__ . '/partials/layout_top.php';
?>

<style>
    .dashboard-shell {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .hero-admin {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 26px;
        background:
            radial-gradient(circle at top right, rgba(25,135,84,.18), transparent 26%),
            linear-gradient(135deg, #ffffff, #f6fbf8);
        box-shadow: 0 14px 28px rgba(0,0,0,.06);
    }

    .hero-admin .card-body {
        padding: 28px;
    }

    .hero-kicker {
        font-size: .82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #198754;
    }

    .hero-title {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.15;
        color: #1f2937;
    }

    .hero-subtitle {
        color: #6b7280;
        max-width: 760px;
        line-height: 1.65;
    }

    .glass-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(255,255,255,.75);
        border: 1px solid rgba(25,135,84,.12);
        font-weight: 600;
        color: #1f2937;
    }

    .info-card,
    .stats-card,
    .list-card {
        border: 0;
        border-radius: 22px;
        box-shadow: 0 10px 24px rgba(0,0,0,.05);
        background: #fff;
    }

    .stats-card {
        height: 100%;
    }

    .stats-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(25,135,84,.12);
        color: #198754;
        font-size: 1.2rem;
    }

    .stats-label {
        color: #6b7280;
        font-size: .92rem;
        margin-bottom: 6px;
    }

    .stats-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
        color: #1f2937;
    }

    .stats-help {
        color: #94a3b8;
        font-size: .85rem;
    }

    .section-title {
        font-size: 1.22rem;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .section-subtitle {
        color: #6b7280;
        font-size: .95rem;
        margin-bottom: 0;
    }

    .mini-stat {
        padding: 14px 16px;
        border-radius: 18px;
        background: #f8fafb;
        border: 1px solid #eef2f4;
        height: 100%;
    }

    .mini-stat-label {
        color: #6b7280;
        font-size: .88rem;
        margin-bottom: 6px;
    }

    .mini-stat-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #1f2937;
        line-height: 1.2;
    }

    .progress-shell {
        margin-top: 10px;
    }

    .progress {
        height: 10px;
        border-radius: 999px;
        background: #edf1f3;
    }

    .progress-bar {
        border-radius: 999px;
    }

    .list-row {
        padding: 14px 0;
        border-bottom: 1px solid #f0f2f4;
    }

    .list-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .item-title {
        font-weight: 700;
        color: #1f2937;
        line-height: 1.4;
    }

    .item-meta {
        color: #6b7280;
        font-size: .9rem;
        line-height: 1.5;
    }

    .item-value {
        font-weight: 800;
        color: #146c43;
        white-space: nowrap;
    }

    .upbs-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .upbs-table {
        min-width: 1100px;
    }

    .upbs-table th,
    .upbs-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .badge-soft-success {
        background: rgba(25,135,84,.12);
        color: #146c43;
    }

    .badge-soft-warning {
        background: rgba(255,193,7,.18);
        color: #8a6d03;
    }

    .badge-soft-secondary {
        background: rgba(108,117,125,.14);
        color: #5c636a;
    }

    .info-note {
        font-size: .88rem;
        color: #6b7280;
        line-height: 1.6;
    }

    @media (max-width: 991.98px) {
        .hero-admin .card-body {
            padding: 22px;
        }

        .hero-title {
            font-size: 1.55rem;
        }
    }
</style>

<div class="dashboard-shell">

    <?php if ($success): ?>
        <div class="alert alert-success mb-0"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-0"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card hero-admin">
        <div class="card-body">
            <div class="hero-kicker mb-2">Dashboard Satker</div>
            <div class="hero-title mb-2"><?= e($nama_satker) ?></div>
            <div class="hero-subtitle mb-4">
                Ringkasan ini menampilkan posisi master dan stok terkini berdasarkan laporan terbaru dari setiap UPBS pada satker Anda. Tampilannya dibuat ringkas agar mudah dibaca dan siap di-screenshot.
            </div>

            <div class="d-flex flex-wrap gap-2">
                <span class="glass-chip">
                    <i class="fa-solid fa-calendar-check text-success"></i>
                    Laporan tersimpan: <?= formatNumberId($total_laporan) ?>
                </span>
                <span class="glass-chip">
                    <i class="fa-solid fa-clock text-success"></i>
                    Update stok terakhir: <?= formatDateId($currentStats['update_terakhir'] ?? null) ?>
                </span>
                <span class="glass-chip">
                    <i class="fa-solid fa-building text-success"></i>
                    UPBS terlapor: <?= formatNumberId($upbs_terlapor) ?>/<?= formatNumberId($total_upbs) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex gap-3 align-items-start">
                    <div class="stats-icon"><i class="fa-solid fa-building"></i></div>
                    <div>
                        <div class="stats-label">UPBS Aktif</div>
                        <div class="stats-value"><?= formatNumberId($total_upbs_aktif) ?></div>
                        <div class="stats-help">Dari total <?= formatNumberId($total_upbs) ?> UPBS</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex gap-3 align-items-start">
                    <div class="stats-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div>
                        <div class="stats-label">Total Stok Terkini</div>
                        <div class="stats-value"><?= formatNumberId($total_stok) ?></div>
                        <div class="stats-help">Posisi dari laporan terbaru</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex gap-3 align-items-start">
                    <div class="stats-icon"><i class="fa-solid fa-seedling"></i></div>
                    <div>
                        <div class="stats-label">Komoditas / Varietas</div>
                        <div class="stats-value"><?= formatNumberId($total_komoditas) ?> / <?= formatNumberId($total_varietas) ?></div>
                        <div class="stats-help">Komoditas & varietas yang terlapor</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body d-flex gap-3 align-items-start">
                    <div class="stats-icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                    <div>
                        <div class="stats-label">Estimasi Nilai Stok</div>
                        <div class="stats-value" style="font-size:1.35rem;"><?= formatRupiah($total_nilai) ?></div>
                        <div class="stats-help">Akumulasi stok × harga</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card info-card h-100">
                <div class="card-body">
                    <div class="section-title">Insight Cepat</div>
                    <div class="section-subtitle mb-4">Indikator penting untuk membaca kesiapan data satker.</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mini-stat">
                                <div class="mini-stat-label">Rasio UPBS aktif</div>
                                <div class="mini-stat-value"><?= formatNumberId($total_upbs_aktif) ?> / <?= formatNumberId($total_upbs) ?></div>
                                <div class="progress-shell">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?= $persen_upbs_aktif ?>%"></div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2"><?= rtrim(rtrim(number_format($persen_upbs_aktif, 1, ',', '.'), '0'), ',') ?>%</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mini-stat">
                                <div class="mini-stat-label">Kelengkapan nomor WA</div>
                                <div class="mini-stat-value"><?= formatNumberId($total_kontak_wa) ?> / <?= formatNumberId($total_upbs) ?></div>
                                <div class="progress-shell">
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: <?= $persen_wa ?>%"></div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2"><?= rtrim(rtrim(number_format($persen_wa, 1, ',', '.'), '0'), ',') ?>%</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mini-stat">
                                <div class="mini-stat-label">UPBS yang sudah melapor</div>
                                <div class="mini-stat-value"><?= formatNumberId($upbs_terlapor) ?> / <?= formatNumberId($total_upbs) ?></div>
                                <div class="progress-shell">
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: <?= $persen_lapor ?>%"></div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2"><?= rtrim(rtrim(number_format($persen_lapor, 1, ',', '.'), '0'), ',') ?>%</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mini-stat">
                                <div class="mini-stat-label">Rata-rata item per UPBS</div>
                                <div class="mini-stat-value"><?= formatNumberId($rata_item_upbs) ?></div>
                                <div class="small text-muted mt-2">Berdasarkan laporan terbaru yang tersedia</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-note mt-4">
                        Dashboard ini menggunakan <strong>laporan stok terbaru per UPBS</strong> untuk menghitung posisi stok saat ini, sehingga nilainya tidak menumpuk dari histori laporan lama.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card list-card h-100">
                <div class="card-body">
                    <div class="section-title">Top Komoditas Berdasarkan Stok Terkini</div>
                    <div class="section-subtitle mb-3">Komoditas dengan kontribusi stok terbesar pada satker Anda.</div>

                    <?php if ($topKomoditas): ?>
                        <?php foreach ($topKomoditas as $row): ?>
                            <div class="list-row d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="item-title"><?= e($row['nama_komoditas']) ?: '-' ?></div>
                                    <div class="item-meta">
                                        Varietas terlapor: <?= formatNumberId($row['total_varietas']) ?> ·
                                        Nilai estimasi: <?= formatRupiah($row['total_nilai']) ?>
                                    </div>
                                </div>
                                <div class="item-value"><?= formatNumberId($row['total_stok']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            Belum ada data komoditas dari laporan terbaru UPBS.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <div class="section-title">Ringkasan Posisi per UPBS</div>
                    <div class="section-subtitle">Tampilan ini cocok untuk dokumentasi cepat atau screenshot pelaporan internal.</div>
                </div>
                <div class="small text-muted">
                    Data stok berdasarkan laporan terbaru masing-masing UPBS
                </div>
            </div>

            <div class="upbs-table-wrap">
                <table class="table table-hover align-middle upbs-table">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode UPBS</th>
                            <th>Nama UPBS</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>WA</th>
                            <th>Update Terakhir</th>
                            <th>Total Item</th>
                            <th>Total Varietas</th>
                            <th>Total Stok</th>
                            <th>Estimasi Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recapUpbs): ?>
                            <?php foreach ($recapUpbs as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($row['kode_upbs']) ?: '-' ?></td>
                                    <td><?= e($row['nama_upbs']) ?: '-' ?></td>
                                    <td><?= e(labelJenisUpbs($row['jenis_upbs'] ?? null)) ?></td>
                                    <td>
                                        <?php if ((int)$row['is_active'] === 1): ?>
                                            <span class="badge badge-soft-success rounded-pill">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-secondary rounded-pill">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['no_hp_pengelola'])): ?>
                                            <span class="badge badge-soft-success rounded-pill">Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-warning rounded-pill">Belum ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDateId($row['tanggal_laporan'] ?? null) ?></td>
                                    <td><?= formatNumberId($row['total_item']) ?></td>
                                    <td><?= formatNumberId($row['total_varietas']) ?></td>
                                    <td><?= formatNumberId($row['total_stok']) ?></td>
                                    <td><?= formatRupiah($row['total_nilai']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Belum ada data UPBS pada satker ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>