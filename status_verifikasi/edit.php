<?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/functions.php';

    $id = $_GET['id'] ?? 0;

    $stmt = $pdo->prepare("SELECT * FROM status_verifikasi WHERE id_status_verif = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if (!$data) {
        die('Data tidak ditemukan.');
    }

    $stmtPoktan = $pdo->prepare("SELECT * FROM poktan WHERE id_poktan = ?");
    $stmtPoktan->execute([$data['id_poktan']]);
    $poktan = $stmtPoktan->fetch();

    if (!$poktan) {
        die('Data poktan tidak ditemukan.');
    }

    $provinsi = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();

    $stmtKab = $pdo->prepare("SELECT id, name FROM kabupatens WHERE provinsi_id = ? ORDER BY name ASC");
    $stmtKab->execute([$poktan['provinsi_id']]);
    $kabupaten = $stmtKab->fetchAll();

    $stmtKec = $pdo->prepare("SELECT id, name FROM kecamatans WHERE kabupaten_id = ? ORDER BY name ASC");
    $stmtKec->execute([$poktan['kabupaten_id']]);
    $kecamatan = $stmtKec->fetchAll();

    $stmtPoktanList = $pdo->prepare("SELECT id_poktan, nama_poktan FROM poktan WHERE kecamatan_id = ? ORDER BY nama_poktan ASC");
    $stmtPoktanList->execute([$poktan['kecamatan_id']]);
    $poktanList = $stmtPoktanList->fetchAll();

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
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Status Verifikasi</title>
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
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Edit Status Verifikasi</h4>

            <form action="<?= base_url('status_verifikasi/update.php') ?>" method="POST">
                <input type="hidden" name="id_status_verif" value="<?= $data['id_status_verif'] ?>">

                <div class="mb-3">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi_id" id="provinsi_id" class="form-select select2" required>
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsi as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= $pr['id'] == $poktan['provinsi_id'] ? 'selected' : '' ?>>
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
                            <option value="<?= $kb['id'] ?>" <?= $kb['id'] == $poktan['kabupaten_id'] ? 'selected' : '' ?>>
                                <?= e($kb['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kecamatan</label>
                    <select name="kecamatan_id" id="kecamatan_id" class="form-select select2" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($kecamatan as $kc): ?>
                            <option value="<?= $kc['id'] ?>" <?= $kc['id'] == $poktan['kecamatan_id'] ? 'selected' : '' ?>>
                                <?= e($kc['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Poktan</label>
                    <select name="id_poktan" id="id_poktan" class="form-select select2" required>
                        <option value="">-- Pilih Poktan --</option>
                        <?php foreach ($poktanList as $pk): ?>
                            <option value="<?= $pk['id_poktan'] ?>" <?= $pk['id_poktan'] == $data['id_poktan'] ? 'selected' : '' ?>>
                                <?= e($pk['nama_poktan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" id="id_sumber" class="form-select select2" required>
                        <option value="">-- Pilih Sumber Bantuan --</option>
                        <?php foreach ($sumber as $sb): ?>
                            <option value="<?= $sb['id_sumber'] ?>" <?= $sb['id_sumber'] == $data['id_sumber'] ? 'selected' : '' ?>>
                                <?= e($sb['nama_sumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Bantuan</label>
                    <select name="id_jenis_bantuan[]" id="id_jenis_bantuan" class="form-select select2-multiple" multiple required>
                        <?php foreach ($jenisBantuanList as $jb): ?>
                            <option value="<?= $jb['id_jenis_bantuan'] ?>" <?= in_array($jb['id_jenis_bantuan'], $selectedJenisBantuan) ? 'selected' : '' ?>>
                                <?= e($jb['nama_jenis_bantuan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Daftar jenis bantuan akan menyesuaikan sumber bantuan yang dipilih.</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="status_verifikasi" name="status_verifikasi" value="1" <?= (int)$data['status_verifikasi'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_verifikasi">Status Verifikasi Sudah Memenuhi Syarat</label>
                    <div class="form-text">Jika tidak dicentang, status otomatis tersimpan sebagai <b>Belum</b>.</div>
                </div>

                <div class="mb-3 <?= (int)$data['status_verifikasi'] === 1 ? '' : 'd-none' ?>" id="tanggal_submit_wrapper">
                    <label class="form-label">Tanggal Submit Ke Eselon 1</label>
                    <input type="date" name="tanggal_submit" id="tanggal_submit" class="form-control"
                        value="<?= !empty($data['tanggal_submit']) ? date('Y-m-d', strtotime($data['tanggal_submit'])) : '' ?>">
                </div>

                <div class="mb-3 <?= (int)$data['status_verifikasi'] === 1 ? 'd-none' : '' ?>" id="keterangan_kendala_wrapper">
                    <label class="form-label">Keterangan Kendala</label>
                    <textarea name="keterangan_kendala" id="keterangan_kendala" class="form-control" rows="3"><?= e($data['keterangan_kendala']) ?></textarea>
                </div>
                <?php
                /*
                <div class="mb-3">
                    <label class="form-label">Keterangan Umum</label>
                    <textarea name="keterangan_umum" class="form-control" rows="3"><?= e($data['keterangan_umum']) ?></textarea>
                </div>
                */
                ?>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= base_url('?page=status_verifikasi') ?>" class="btn btn-secondary">Kembali</a>
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
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    $('.select2-multiple').select2({
        width: '100%',
        placeholder: '-- Pilih Jenis Bantuan --'
    });

    $('#provinsi_id').on('change', function() {
        let provinsiId = $(this).val();

        $('#kabupaten_id').html('<option value="">-- Pilih Kabupaten --</option>');
        $('#kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#id_poktan').html('<option value="">-- Pilih Poktan --</option>');

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

    $('#kabupaten_id').on('change', function() {
        let kabupatenId = $(this).val();

        $('#kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#id_poktan').html('<option value="">-- Pilih Poktan --</option>');

        if (kabupatenId) {
            $.ajax({
                url: '<?= base_url("ajax/get_kecamatan.php") ?>',
                type: 'GET',
                data: { kabupaten_id: kabupatenId },
                success: function(response) {
                    $('#kecamatan_id').html(response);
                }
            });
        }
    });

    $('#kecamatan_id').on('change', function() {
        let kecamatanId = $(this).val();

        $('#id_poktan').html('<option value="">-- Pilih Poktan --</option>');

        if (kecamatanId) {
            $.ajax({
                url: '<?= base_url("ajax/get_poktan.php") ?>',
                type: 'GET',
                data: { kecamatan_id: kecamatanId },
                success: function(response) {
                    $('#id_poktan').html(response);
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

    $('#status_verifikasi').on('change', function() {
        toggleStatusVerifikasiFields();
    });
});
</script>
</body>
</html>