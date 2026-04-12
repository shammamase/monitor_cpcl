<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

function normalizeNumberForInput($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $num = (float)$value;

    if ((float)(int)$num === $num) {
        return (string)(int)$num;
    }

    $text = rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    return $text;
}

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
        l.id_upbs,
        l.tanggal_laporan,
        l.periode_text,
        l.nama_petugas_input,
        l.keterangan,
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
| Master data
|--------------------------------------------------------------------------
*/
$stmtUpbs = $pdo->prepare("
    SELECT id_upbs, nama_upbs
    FROM upbs
    WHERE id_satker = ?
      AND is_active = 1
    ORDER BY nama_upbs ASC
");
$stmtUpbs->execute([$id_satker]);
$upbsList = $stmtUpbs->fetchAll(PDO::FETCH_ASSOC);

$komoditasList = $pdo->query("
    SELECT id_komoditas, nama_komoditas, kategori_komoditas
    FROM komoditas
    WHERE is_active = 1
    ORDER BY nama_komoditas ASC
")->fetchAll(PDO::FETCH_ASSOC);

$varietasList = $pdo->query("
    SELECT id_varietas, id_komoditas, nama_varietas, jenis_varietas
    FROM varietas
    WHERE is_active = 1
    ORDER BY nama_varietas ASC
")->fetchAll(PDO::FETCH_ASSOC);

$kelasBenihList = $pdo->query("
    SELECT id_kelas_benih, kode_kelas, nama_kelas
    FROM kelas_benih
    WHERE is_active = 1
    ORDER BY kode_kelas ASC
")->fetchAll(PDO::FETCH_ASSOC);

$benihSumberList = $pdo->query("
    SELECT id_benih_sumber, nama_benih_sumber, kategori_komoditas
    FROM benih_sumber
    WHERE is_active = 1
    ORDER BY nama_benih_sumber ASC
")->fetchAll(PDO::FETCH_ASSOC);

$satuanList = $pdo->query("
    SELECT id_satuan, nama_satuan, simbol
    FROM satuan
    ORDER BY nama_satuan ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Ambil detail laporan
|--------------------------------------------------------------------------
*/
$stmtDetail = $pdo->prepare("
    SELECT
        d.id_detail_stok,
        d.id_komoditas,
        d.id_varietas,
        d.id_kelas_benih,
        d.id_benih_sumber,
        d.stok_tersedia,
        d.id_satuan_stok,
        d.harga_min,
        d.harga_keterangan,
        d.urutan_tampil
    FROM laporan_stok_upbs_detail d
    WHERE d.id_laporan_stok = ?
    ORDER BY d.urutan_tampil ASC, d.id_detail_stok ASC
");
$stmtDetail->execute([$id_laporan_stok]);
$detailRowsDb = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$oldInput = $_SESSION['old_input_edit_stok'] ?? [];
$error = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['old_input_edit_stok'], $_SESSION['error_message_upbs']);

if (!empty($oldInput)) {
    $formHeader = [
        'id_laporan_stok'     => $id_laporan_stok,
        'id_upbs'             => $oldInput['id_upbs'] ?? $header['id_upbs'],
        'tanggal_laporan'     => $oldInput['tanggal_laporan'] ?? $header['tanggal_laporan'],
        'periode_text'        => $oldInput['periode_text'] ?? $header['periode_text'],
        'nama_petugas_input'  => $oldInput['nama_petugas_input'] ?? $header['nama_petugas_input'],
        'keterangan'          => $oldInput['keterangan'] ?? $header['keterangan'],
    ];
    $detailRows = array_values($oldInput['detail'] ?? []);
} else {
    $formHeader = [
        'id_laporan_stok'     => $id_laporan_stok,
        'id_upbs'             => $header['id_upbs'],
        'tanggal_laporan'     => $header['tanggal_laporan'],
        'periode_text'        => $header['periode_text'],
        'nama_petugas_input'  => $header['nama_petugas_input'],
        'keterangan'          => $header['keterangan'],
    ];

    $detailRows = [];
    foreach ($detailRowsDb as $d) {
        $detailRows[] = [
            'id_komoditas'     => $d['id_komoditas'],
            'id_varietas'      => $d['id_varietas'],
            'id_kelas_benih'   => $d['id_kelas_benih'],
            'id_benih_sumber'  => $d['id_benih_sumber'],
            'stok_tersedia'    => normalizeNumberForInput($d['stok_tersedia']),
            'id_satuan_stok'   => $d['id_satuan_stok'],
            'harga'            => normalizeNumberForInput($d['harga_min']),
            'harga_keterangan' => $d['harga_keterangan'],
        ];
    }
}

$pageTitle = 'Edit Input Stok - UPBS';
$activeMenu = 'input_stok';
$activeSubmenu = '';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<style>
    .section-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 22px rgba(0,0,0,0.06);
    }

    .section-card .section-title {
        font-weight: 700;
        margin-bottom: 4px;
    }

    .section-card .section-subtitle {
        color: #6c757d;
        margin-bottom: 0;
    }

    .detail-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .detail-table {
        min-width: 1400px;
    }

    .detail-table th,
    .detail-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .detail-table th:nth-child(2),
    .detail-table td:nth-child(2) {
        min-width: 170px;
    }

    .detail-table th:nth-child(3),
    .detail-table td:nth-child(3) {
        min-width: 190px;
    }

    .detail-table th:nth-child(4),
    .detail-table td:nth-child(4) {
        min-width: 150px;
    }

    .detail-table th:nth-child(5),
    .detail-table td:nth-child(5) {
        min-width: 170px;
    }

    .detail-table th:nth-child(6),
    .detail-table td:nth-child(6) {
        min-width: 150px;
    }

    .detail-table th:nth-child(7),
    .detail-table td:nth-child(7) {
        min-width: 120px;
    }

    .detail-table th:nth-child(8),
    .detail-table td:nth-child(8) {
        min-width: 130px;
    }

    .detail-table th:nth-child(9),
    .detail-table td:nth-child(9) {
        min-width: 200px;
    }

    .detail-table .form-select,
    .detail-table .form-control {
        min-width: 100%;
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

    .mini-note {
        font-size: .87rem;
        color: #6c757d;
    }
</style>

<form action="<?= base_url('page_upbs/input_stok/update.php') ?>" method="POST" id="formInputStokEdit">
    <input type="hidden" name="id_laporan_stok" value="<?= (int)$formHeader['id_laporan_stok'] ?>">

    <div class="card section-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                <div>
                    <h4 class="section-title">Edit Input Stok</h4>
                    <p class="section-subtitle">Perbarui informasi laporan dan detail stok.</p>
                </div>
                <a href="<?= base_url('page_upbs/input_stok/detail.php?id=' . $id_laporan_stok) ?>" class="btn btn-outline-secondary">Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Satker</label>
                    <input type="text" class="form-control" value="<?= e($userUpbs['nama_satker']) ?: '-' ?>" readonly>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Nama Petugas Input</label>
                    <input type="text" name="nama_petugas_input" class="form-control" value="<?= e($formHeader['nama_petugas_input']) ?>">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">UPBS</label>
                    <select name="id_upbs" class="form-select" required>
                        <option value="">-- Pilih UPBS --</option>
                        <?php foreach ($upbsList as $u): ?>
                            <option value="<?= $u['id_upbs'] ?>" <?= ($formHeader['id_upbs'] == $u['id_upbs']) ? 'selected' : '' ?>>
                                <?= e($u['nama_upbs']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Tanggal Laporan</label>
                    <input type="date" name="tanggal_laporan" class="form-control" value="<?= e($formHeader['tanggal_laporan']) ?>" required>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Periode Teks</label>
                    <input type="text" name="periode_text" class="form-control" placeholder="Contoh: Maret 2026" value="<?= e($formHeader['periode_text']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Keterangan tambahan jika ada"><?= e($formHeader['keterangan']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card section-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h5 class="section-title mb-1">Detail Stok</h5>
                    <p class="section-subtitle">Tambahkan, ubah, atau hapus item stok.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="summary-chip">Total Baris: <span id="totalBaris">0</span></span>
                    <button type="button" class="btn btn-primary" id="btnTambahBaris">+ Tambah Baris</button>
                </div>
            </div>

            <div class="table-responsive detail-table-wrap">
                <table class="table table-bordered align-middle detail-table">
                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th>Komoditas</th>
                            <th>Varietas / Galur</th>
                            <th>Kelas Benih</th>
                            <th>Benih Sumber</th>
                            <th>Stok Tersedia</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Keterangan Harga</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="detailBody"></tbody>
                </table>
            </div>

            <div class="mini-note mb-3">
                Catatan: untuk komoditas kategori ternak, field Kelas Benih otomatis dinonaktifkan. Untuk harga rentang, isi angka utama pada kolom Harga dan jelaskan rentangnya pada Keterangan Harga.
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success" id="btnUpdateFinal">Simpan Perubahan</button>
                <a href="<?= base_url('page_upbs/input_stok/detail.php?id=' . $id_laporan_stok) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>

<script>
const komoditasData = <?= json_encode($komoditasList, JSON_UNESCAPED_UNICODE) ?>;
const varietasData = <?= json_encode($varietasList, JSON_UNESCAPED_UNICODE) ?>;
const kelasBenihData = <?= json_encode($kelasBenihList, JSON_UNESCAPED_UNICODE) ?>;
const benihSumberData = <?= json_encode($benihSumberList, JSON_UNESCAPED_UNICODE) ?>;
const satuanData = <?= json_encode($satuanList, JSON_UNESCAPED_UNICODE) ?>;
const oldDetailData = <?= json_encode($detailRows, JSON_UNESCAPED_UNICODE) ?>;

let rowCounter = 0;

function formatAngkaIndonesia(value) {
    value = String(value ?? '').trim();
    if (value === '') return '';

    // format dari database, contoh 3000.00 atau 5500.50
    if (/^\d+\.\d{1,2}$/.test(value)) {
        const num = Number(value);
        if (!Number.isNaN(num)) {
            return num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
        }
    }

    value = value.replace(/[^\d,]/g, '');
    const parts = value.split(',');
    let integerPart = parts[0].replace(/\./g, '');

    if (integerPart === '') integerPart = '';
    integerPart = integerPart.replace(/^0+(?=\d)/, '');
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (parts.length > 1) {
        const decimalPart = parts.slice(1).join('').replace(/,/g, '').slice(0, 2);
        return decimalPart !== '' ? `${integerPart},${decimalPart}` : integerPart;
    }

    return integerPart;
}

function getKategoriKomoditas(idKomoditas) {
    const found = komoditasData.find(item => String(item.id_komoditas) === String(idKomoditas));
    return found ? found.kategori_komoditas : '';
}

function optionKomoditas(selected = '') {
    let html = '<option value="">-- Pilih Komoditas --</option>';
    komoditasData.forEach(item => {
        html += `<option value="${item.id_komoditas}" data-kategori="${item.kategori_komoditas}" ${selected == item.id_komoditas ? 'selected' : ''}>${item.nama_komoditas}</option>`;
    });
    return html;
}

function optionVarietas(idKomoditas = '', selected = '') {
    let html = '<option value="">-- Pilih Varietas/Galur --</option>';
    varietasData.forEach(item => {
        if (!idKomoditas || String(item.id_komoditas) === String(idKomoditas)) {
            html += `<option value="${item.id_varietas}" ${selected == item.id_varietas ? 'selected' : ''}>${item.nama_varietas}</option>`;
        }
    });
    return html;
}

function optionKelasBenih(selected = '') {
    let html = '<option value="">-- Pilih Kelas --</option>';
    kelasBenihData.forEach(item => {
        const label = item.nama_kelas ? `${item.kode_kelas} - ${item.nama_kelas}` : item.kode_kelas;
        html += `<option value="${item.id_kelas_benih}" ${selected == item.id_kelas_benih ? 'selected' : ''}>${label}</option>`;
    });
    return html;
}

function optionBenihSumber(kategori = '', selected = '') {
    let html = '<option value="">-- Pilih Benih Sumber --</option>';
    benihSumberData.forEach(item => {
        const cocok = !item.kategori_komoditas || item.kategori_komoditas === kategori;
        if (!kategori || cocok) {
            html += `<option value="${item.id_benih_sumber}" ${selected == item.id_benih_sumber ? 'selected' : ''}>${item.nama_benih_sumber}</option>`;
        }
    });
    return html;
}

function optionSatuan(selected = '') {
    let html = '<option value="">-- Pilih Satuan --</option>';
    satuanData.forEach(item => {
        html += `<option value="${item.id_satuan}" ${selected == item.id_satuan ? 'selected' : ''}>${item.nama_satuan} (${item.simbol})</option>`;
    });
    return html;
}

function buildRowHtml(index, data = {}) {
    const kategori = getKategoriKomoditas(data.id_komoditas || '');
    return `
        <td class="text-center row-number"></td>
        <td>
            <select class="form-select komoditas-select" name="detail[${index}][id_komoditas]">
                ${optionKomoditas(data.id_komoditas || '')}
            </select>
        </td>
        <td>
            <select class="form-select varietas-select" name="detail[${index}][id_varietas]">
                ${optionVarietas(data.id_komoditas || '', data.id_varietas || '')}
            </select>
        </td>
        <td>
            <select class="form-select kelas-select" name="detail[${index}][id_kelas_benih]" ${kategori === 'ternak' ? 'disabled' : ''}>
                ${optionKelasBenih(data.id_kelas_benih || '')}
            </select>
        </td>
        <td>
            <select class="form-select benih-sumber-select" name="detail[${index}][id_benih_sumber]">
                ${optionBenihSumber(kategori, data.id_benih_sumber || '')}
            </select>
        </td>
        <td>
            <input type="text" class="form-control input-ribuan text-end" name="detail[${index}][stok_tersedia]" placeholder="0" value="${formatAngkaIndonesia(data.stok_tersedia || '')}">
        </td>
        <td>
            <select class="form-select" name="detail[${index}][id_satuan_stok]">
                ${optionSatuan(data.id_satuan_stok || '')}
            </select>
        </td>
        <td>
            <input type="text" class="form-control input-ribuan text-end" name="detail[${index}][harga]" placeholder="0" value="${formatAngkaIndonesia(data.harga || '')}">
        </td>
        <td>
            <input type="text" class="form-control" name="detail[${index}][harga_keterangan]" placeholder="Opsional" value="${data.harga_keterangan || ''}">
        </td>
        <td>
            <div class="d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary btn-duplicate">Duplikat</button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove">Hapus</button>
            </div>
        </td>
    `;
}

function tambahBaris(data = {}) {
    rowCounter++;
    const tr = document.createElement('tr');
    tr.setAttribute('data-row', rowCounter);
    tr.innerHTML = buildRowHtml(rowCounter, data);
    document.getElementById('detailBody').appendChild(tr);
    refreshNomor();
}

function refreshNomor() {
    const rows = document.querySelectorAll('#detailBody tr');
    rows.forEach((row, index) => {
        row.querySelector('.row-number').textContent = index + 1;
    });
    document.getElementById('totalBaris').textContent = rows.length;
}

function updateBarisByKomoditas(row) {
    const komoditasSelect = row.querySelector('.komoditas-select');
    const idKomoditas = komoditasSelect.value;
    const kategori = getKategoriKomoditas(idKomoditas);

    const varietasSelect = row.querySelector('.varietas-select');
    const kelasSelect = row.querySelector('.kelas-select');
    const benihSumberSelect = row.querySelector('.benih-sumber-select');

    varietasSelect.innerHTML = optionVarietas(idKomoditas);
    benihSumberSelect.innerHTML = optionBenihSumber(kategori);

    if (kategori === 'ternak') {
        kelasSelect.value = '';
        kelasSelect.setAttribute('disabled', 'disabled');
    } else {
        kelasSelect.removeAttribute('disabled');
    }
}

function ambilDataBaris(row) {
    return {
        id_komoditas: row.querySelector('.komoditas-select')?.value || '',
        id_varietas: row.querySelector('.varietas-select')?.value || '',
        id_kelas_benih: row.querySelector('.kelas-select')?.value || '',
        id_benih_sumber: row.querySelector('.benih-sumber-select')?.value || '',
        stok_tersedia: row.querySelector('input[name*="[stok_tersedia]"]')?.value || '',
        id_satuan_stok: row.querySelector('select[name*="[id_satuan_stok]"]')?.value || '',
        harga: row.querySelector('input[name*="[harga]"]')?.value || '',
        harga_keterangan: row.querySelector('input[name*="[harga_keterangan]"]')?.value || ''
    };
}

document.getElementById('btnTambahBaris').addEventListener('click', function() {
    tambahBaris();
});

document.getElementById('detailBody').addEventListener('change', function(e) {
    if (e.target.classList.contains('komoditas-select')) {
        const row = e.target.closest('tr');
        updateBarisByKomoditas(row);
    }
});

document.getElementById('detailBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('input-ribuan')) {
        e.target.value = formatAngkaIndonesia(e.target.value);
    }
});

document.getElementById('detailBody').addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove')) {
        const rows = document.querySelectorAll('#detailBody tr');
        if (rows.length > 1) {
            e.target.closest('tr').remove();
            refreshNomor();
        } else {
            alert('Minimal harus ada 1 baris detail.');
        }
    }

    if (e.target.classList.contains('btn-duplicate')) {
        const currentRow = e.target.closest('tr');
        const data = ambilDataBaris(currentRow);
        tambahBaris(data);
    }
});

document.getElementById('btnUpdateFinal').addEventListener('click', function() {
    document.getElementById('formInputStokEdit').submit();
});

if (oldDetailData.length > 0) {
    oldDetailData.forEach(item => tambahBaris(item));
} else {
    tambahBaris();
}
</script>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>