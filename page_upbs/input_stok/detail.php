<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker = (int)($userUpbs['id_satker'] ?? 0);
$id_laporan_stok = (int)($_GET['id'] ?? 0);

if ($id_satker <= 0 || $id_laporan_stok <= 0) {
    $_SESSION['error_message_upbs'] = 'Data laporan stok tidak valid.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil header laporan, pastikan milik satker user
|--------------------------------------------------------------------------
*/
$stmtHeader = $pdo->prepare("
    SELECT
        l.id_laporan_stok,
        l.tanggal_laporan,
        l.periode_text,
        l.nama_petugas_input,
        l.keterangan,
        u.id_upbs,
        u.nama_upbs,
        s.id_satker,
        s.nama_satker
    FROM laporan_stok_upbs l
    INNER JOIN upbs u ON l.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    WHERE l.id_laporan_stok = ?
      AND s.id_satker = ?
    LIMIT 1
");
$stmtHeader->execute([$id_laporan_stok, $id_satker]);
$header = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if (!$header) {
    $_SESSION['error_message_upbs'] = 'Laporan stok tidak ditemukan atau bukan milik satker Anda.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil detail laporan
|--------------------------------------------------------------------------
*/
$stmtDetail = $pdo->prepare("
    SELECT
        d.id_detail_stok,
        d.stok_tersedia,
        d.harga_min,
        d.harga_max,
        d.harga_keterangan,
        d.urutan_tampil,

        k.nama_komoditas,
        k.kategori_komoditas,

        v.nama_varietas,
        v.jenis_varietas,

        kb.nama_kelas,

        bs.nama_benih_sumber,

        st.nama_satuan,
        st.simbol

    FROM laporan_stok_upbs_detail d
    LEFT JOIN komoditas k ON d.id_komoditas = k.id_komoditas
    LEFT JOIN varietas v ON d.id_varietas = v.id_varietas
    LEFT JOIN kelas_benih kb ON d.id_kelas_benih = kb.id_kelas_benih
    LEFT JOIN benih_sumber bs ON d.id_benih_sumber = bs.id_benih_sumber
    LEFT JOIN satuan st ON d.id_satuan_stok = st.id_satuan
    WHERE d.id_laporan_stok = ?
    ORDER BY d.urutan_tampil ASC, d.id_detail_stok ASC
");
$stmtDetail->execute([$id_laporan_stok]);
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$totalItem = count($details);
$totalStok = 0;
foreach ($details as $item) {
    $totalStok += (float)($item['stok_tersedia'] ?? 0);
}

$pageTitle = 'Detail Input Stok - UPBS';
$activeMenu = 'input_stok';
$activeSubmenu = '';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<style>
    .info-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 22px rgba(0,0,0,0.06);
    }

    .label-muted {
        font-size: .88rem;
        color: #6c757d;
        margin-bottom: 4px;
    }

    .value-strong {
        font-weight: 600;
    }

    .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: #f1f3f5;
        font-weight: 600;
        font-size: .92rem;
    }

    .detail-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .detail-table {
        min-width: 1000px;
    }

    .detail-table th,
    .detail-table td {
        vertical-align: middle;
        white-space: nowrap;
    }
</style>

<div class="card info-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="mb-1">Detail Laporan Stok</h4>
                <div class="text-muted">Informasi laporan dan rincian item stok yang telah diinput.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('page_upbs/input_stok/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
                <a href="<?= base_url('page_upbs/input_stok/edit.php?id=' . $header['id_laporan_stok']) ?>" class="btn btn-outline-warning">Edit</a>
                <a href="<?= base_url('page_upbs/input_stok/salin.php?id=' . $header['id_laporan_stok']) ?>" class="btn btn-outline-success">Update</a>
                <a href="<?= base_url('page_upbs/input_stok/delete.php?id=' . $header['id_laporan_stok']) ?>"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Yakin ingin menghapus laporan stok ini?')">Hapus</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-3">
                <div class="label-muted">Satker</div>
                <div class="value-strong"><?= e($header['nama_satker']) ?: '-' ?></div>
            </div>

            <div class="col-lg-3">
                <div class="label-muted">UPBS</div>
                <div class="value-strong"><?= e($header['nama_upbs']) ?: '-' ?></div>
            </div>

            <div class="col-lg-3">
                <div class="label-muted">Tanggal Laporan</div>
                <div class="value-strong">
                    <?= !empty($header['tanggal_laporan']) ? e(date('d-m-Y', strtotime($header['tanggal_laporan']))) : '-' ?>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="label-muted">Periode</div>
                <div class="value-strong"><?= !empty($header['periode_text']) ? e($header['periode_text']) : '-' ?></div>
            </div>

            <div class="col-lg-4">
                <div class="label-muted">Nama Petugas Input</div>
                <div class="value-strong"><?= !empty($header['nama_petugas_input']) ? e($header['nama_petugas_input']) : '-' ?></div>
            </div>

            <div class="col-lg-8">
                <div class="label-muted">Keterangan</div>
                <div class="value-strong"><?= !empty($header['keterangan']) ? e($header['keterangan']) : '-' ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card info-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">Rincian Item Stok</h5>
                <div class="text-muted">Daftar item yang tersimpan pada laporan ini.</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <span class="summary-chip">Total Item: <?= number_format($totalItem) ?></span>
                <span class="summary-chip">Total Stok: <?= number_format($totalStok, 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="table-responsive detail-table-wrap">
            <table class="table table-bordered table-hover align-middle detail-table">
                <thead class="table-light">
                    <tr>
                        <th width="60">No</th>
                        <th>Komoditas</th>
                        <th>Varietas / Galur</th>
                        <th>Kelas Benih</th>
                        <th>Benih Sumber</th>
                        <th>Stok Tersedia</th>
                        <th>Harga</th>
                        <th>Keterangan Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($details): ?>
                        <?php foreach ($details as $i => $row): ?>
                            <?php
                                $simbolSatuan = !empty($row['simbol']) ? $row['simbol'] : '';
                                $stokTampil = number_format((float)$row['stok_tersedia'], 0, ',', '.');
                                if ($simbolSatuan !== '') {
                                    $stokTampil .= ' ' . $simbolSatuan;
                                }

                                $hargaTampil = '-';
                                if ($row['harga_min'] !== null && $row['harga_min'] !== '') {
                                    $hargaTampil = number_format((float)$row['harga_min'], 0, ',', '.');
                                    if ($simbolSatuan !== '') {
                                        $hargaTampil .= '/' . $simbolSatuan;
                                    }
                                }
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($row['nama_komoditas']) ?: '-' ?></td>
                                <td><?= e($row['nama_varietas']) ?: '-' ?></td>
                                <td><?= !empty($row['nama_kelas']) ? e($row['nama_kelas']) : '-' ?></td>
                                <td><?= e($row['nama_benih_sumber']) ?: '-' ?></td>
                                <td class="text-end"><?= e($stokTampil) ?></td>
                                <td class="text-end"><?= e($hargaTampil) ?></td>
                                <td><?= !empty($row['harga_keterangan']) ? e($row['harga_keterangan']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada detail item stok.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>