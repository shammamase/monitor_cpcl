<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id = (int)($userLogin['provinsi_id'] ?? 0);

if ($provinsi_id <= 0) {
    die('Provinsi user tidak valid.');
}

$stmtKab = $pdo->prepare("
    SELECT id, name, type
    FROM kabupatens
    WHERE provinsi_id = ?
    ORDER BY name ASC
");
$stmtKab->execute([$provinsi_id]);
$kabupatenList = $stmtKab->fetchAll();

$sumberList = $pdo->query("
    SELECT id_sumber, nama_sumber
    FROM sumber_bantuan
    ORDER BY nama_sumber ASC
")->fetchAll();
$satuanOptions = ['Kg', 'Ton', 'Unit', 'Ha', 'Liter', 'Paket', 'Batang', 'Ekor', 'Meter', 'M2'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Status Verifikasi - Dashboard Provinsi</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            padding: 4px 8px;
            border: 1px solid #ced4da !important;
            border-radius: .375rem !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Dashboard Provinsi CPCL</span>
        </a>

        <div class="d-flex align-items-center text-white gap-3">
            <span><?= e($userLogin['nama_lengkap']) ?> - <?= e($userLogin['nama_provinsi']) ?></span>
            <a href="<?= base_url('page_cpcl/logout.php') ?>" class="btn btn-sm btn-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Tambah Status Verifikasi</h4>

            <form action="<?= base_url('page_cpcl/store_status_verifikasi.php') ?>" method="POST">
                <input type="hidden" name="provinsi_id" value="<?= $provinsi_id ?>">

                <div class="mb-3">
                    <label class="form-label">Provinsi</label>
                    <input type="text" class="form-control" value="<?= e($userLogin['nama_provinsi']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kabupaten</label>
                    <select name="kabupaten_id" id="kabupaten_id" class="form-select select2" required>
                        <option value="">-- Pilih Kabupaten --</option>
                        <?php foreach ($kabupatenList as $kab): ?>
                            <option value="<?= $kab['id'] ?>"><?= e($kab['type'] . ' ' . $kab['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" id="id_sumber" class="form-select select2" required>
                        <option value="">-- Pilih Sumber Bantuan --</option>
                        <?php foreach ($sumberList as $sb): ?>
                            <option value="<?= $sb['id_sumber'] ?>"><?= e($sb['nama_sumber']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Bantuan</label>
                    <select name="id_jenis_bantuan[]" id="id_jenis_bantuan" class="form-select select2-multiple" multiple required></select>
                    <div class="form-text">Pilih satu atau lebih jenis bantuan sesuai sumber bantuan yang dipilih.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Volume</label>
                        <input type="number" name="volume" class="form-control" min="0.01" step="0.01" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Satuan</label>
                        <select name="satuan" id="satuan" class="form-select select2" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuanOptions as $satuan): ?>
                                <option value="<?= e($satuan) ?>"><?= e($satuan) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="status_verifikasi" name="status_verifikasi" value="1">
                    <label class="form-check-label" for="status_verifikasi">Status Verifikasi Sudah</label>
                    <div class="form-text">Jika tidak dicentang, status otomatis tersimpan sebagai <b>Belum</b>.</div>
                </div>

                <div class="mb-3 d-none" id="tanggal_submit_wrapper">
                    <label class="form-label">Tanggal Submit</label>
                    <input type="date" name="tanggal_submit" id="tanggal_submit" class="form-control">
                </div>

                <div class="mb-3" id="keterangan_kendala_wrapper">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan_kendala" id="keterangan_kendala" class="form-control" rows="3"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    $('.select2-multiple').select2({
        width: '100%',
        placeholder: '-- Pilih Jenis Bantuan --'
    });

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

    toggleStatusVerifikasiFields();

    $('#status_verifikasi').on('change', function() {
        toggleStatusVerifikasiFields();
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
});
</script>

</body>
</html>
