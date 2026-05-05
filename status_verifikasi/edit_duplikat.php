<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);
$redirect = $_GET['redirect'] ?? base_url('status_verifikasi/cek_duplikat.php');
$basePath = base_url();

if (!is_string($redirect) || strpos($redirect, $basePath) !== 0) {
    $redirect = base_url('status_verifikasi/cek_duplikat.php');
}

$stmt = $pdo->prepare("SELECT * FROM status_verifikasi WHERE id_status_verif = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die('Data tidak ditemukan.');
}

$provinsi = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();

$stmtKab = $pdo->prepare("
    SELECT id, type, name
    FROM kabupatens
    WHERE provinsi_id = ?
    ORDER BY name ASC
");
$stmtKab->execute([$data['provinsi_id']]);
$kabupaten = $stmtKab->fetchAll();

$sumber = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll();

$stmtJenis = $pdo->prepare("
    SELECT id_jenis_bantuan, nama_jenis_bantuan
    FROM jenis_bantuan
    WHERE id_sumber = ?
    ORDER BY nama_jenis_bantuan ASC
");
$stmtJenis->execute([$data['id_sumber']]);
$jenisBantuanList = $stmtJenis->fetchAll();

$stmtSelectedJenis = $pdo->prepare("
    SELECT id_jenis_bantuan
    FROM status_verifikasi_jenis_bantuan
    WHERE id_status_verif = ?
");
$stmtSelectedJenis->execute([$data['id_status_verif']]);
$selectedJenisBantuan = $stmtSelectedJenis->fetchAll(PDO::FETCH_COLUMN);

$satuanOptions = ['Kg', 'Ton', 'Unit', 'Ha', 'Liter', 'Paket', 'Batang', 'Ekor', 'Meter', 'M2', 'Kelompok Masyarakat'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Duplikat</title>
    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url() ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL BRMP</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Edit Data Duplikat</h3>
            <div class="text-muted">Perubahan pada halaman ini akan meng-update data yang sama, bukan membuat data baru.</div>
        </div>
        <a href="<?= e($redirect) ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?= base_url('status_verifikasi/update_duplikat.php') ?>" method="POST">
                <input type="hidden" name="id_status_verif" value="<?= e($data['id_status_verif']) ?>">
                <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                <div class="mb-3">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi_id" id="provinsi_id" class="form-select select2" required>
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsi as $pr): ?>
                            <option value="<?= e($pr['id']) ?>" <?= $pr['id'] == $data['provinsi_id'] ? 'selected' : '' ?>>
                                <?= e($pr['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kabupaten</label>
                    <select name="kabupaten_id" id="kabupaten_id" class="form-select select2" required>
                        <option value="">-- Pilih Kabupaten --</option>
                        <?php foreach ($kabupaten as $kb): ?>
                            <option value="<?= e($kb['id']) ?>" <?= $kb['id'] == $data['kabupaten_id'] ? 'selected' : '' ?>>
                                <?= e($kb['type']) ?> <?= e($kb['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" id="id_sumber" class="form-select select2" required>
                        <option value="">-- Pilih Sumber Bantuan --</option>
                        <?php foreach ($sumber as $sb): ?>
                            <option value="<?= e($sb['id_sumber']) ?>" <?= $sb['id_sumber'] == $data['id_sumber'] ? 'selected' : '' ?>>
                                <?= e($sb['nama_sumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Bantuan</label>
                    <select name="id_jenis_bantuan[]" id="id_jenis_bantuan" class="form-select select2-multiple" multiple required>
                        <?php foreach ($jenisBantuanList as $jb): ?>
                            <option value="<?= e($jb['id_jenis_bantuan']) ?>" <?= in_array($jb['id_jenis_bantuan'], $selectedJenisBantuan) ? 'selected' : '' ?>>
                                <?= e($jb['nama_jenis_bantuan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Daftar jenis bantuan mengikuti sumber bantuan yang dipilih.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Volume</label>
                        <input type="text" name="volume" class="form-control volume-input" inputmode="decimal" required
                               value="<?= e($data['volume'] !== null && $data['volume'] !== '' ? rtrim(rtrim(number_format((float)$data['volume'], 2, ',', '.'), '0'), ',') : '') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Satuan</label>
                        <select name="satuan" id="satuan" class="form-select select2" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuanOptions as $satuan): ?>
                                <option value="<?= e($satuan) ?>" <?= ($data['satuan'] ?? '') === $satuan ? 'selected' : '' ?>>
                                    <?= e($satuan) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="status_verifikasi" name="status_verifikasi" value="1" <?= (int)$data['status_verifikasi'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_verifikasi">Status Verifikasi Sudah Memenuhi Syarat</label>
                    <div class="form-text">Jika tidak dicentang, status otomatis tersimpan sebagai <b>Belum</b>.</div>
                </div>

                <div class="mb-3 <?= (int)$data['status_verifikasi'] === 1 ? '' : 'd-none' ?>" id="tanggal_submit_wrapper">
                    <label class="form-label">Tanggal Submit Ke Eselon 1</label>
                    <input type="date" name="tanggal_submit" id="tanggal_submit" class="form-control"
                           value="<?= !empty($data['tanggal_submit']) ? e(date('Y-m-d', strtotime($data['tanggal_submit']))) : '' ?>">
                </div>

                <div class="mb-3 <?= (int)$data['status_verifikasi'] === 1 ? 'd-none' : '' ?>" id="keterangan_kendala_wrapper">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan_kendala" id="keterangan_kendala" class="form-control" rows="3"><?= e($data['keterangan_kendala']) ?></textarea>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= e($redirect) ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function toggleStatusVerifikasiFields() {
    if ($('#status_verifikasi').is(':checked')) {
        $('#tanggal_submit_wrapper').removeClass('d-none');
        $('#keterangan_kendala_wrapper').addClass('d-none');
        $('#keterangan_kendala').val('');
    } else {
        $('#tanggal_submit_wrapper').addClass('d-none');
        $('#keterangan_kendala_wrapper').removeClass('d-none');
        $('#tanggal_submit').val('');
    }
}

function formatVolumeInput(value) {
    let cleaned = value.replace(/[^\d,]/g, '');
    let parts = cleaned.split(',');
    let integerPart = parts[0].replace(/^0+(?=\d)/, '');
    let decimalPart = parts.length > 1 ? ',' + parts.slice(1).join('').replace(/\D/g, '') : '';

    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return integerPart + decimalPart;
}

$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    $('.select2-multiple').select2({
        width: '100%',
        placeholder: '-- Pilih Jenis Bantuan --'
    });

    $('.volume-input').on('keyup', function() {
        $(this).val(formatVolumeInput($(this).val()));
    });

    $('#provinsi_id').on('change', function() {
        let provinsiId = $(this).val();
        $('#kabupaten_id').html('<option value="">-- Pilih Kabupaten --</option>');

        if (provinsiId) {
            $.ajax({
                url: '<?= base_url("ajax/get_kabupaten.php") ?>',
                type: 'GET',
                data: { provinsi_id: provinsiId },
                success: function(response) {
                    $('#kabupaten_id').html(response);
                }
            });
        }
    });

    $('#id_sumber').on('change', function() {
        let idSumber = $(this).val();
        $('#id_jenis_bantuan').html('').trigger('change');

        if (idSumber) {
            $.ajax({
                url: '<?= base_url("ajax/get_jenis_bantuan.php") ?>',
                type: 'GET',
                data: { id_sumber: idSumber },
                success: function(response) {
                    $('#id_jenis_bantuan').html(response).trigger('change');
                }
            });
        }
    });

    toggleStatusVerifikasiFields();
    $('#status_verifikasi').on('change', toggleStatusVerifikasiFields);
});
</script>
</body>
</html>
