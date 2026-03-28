<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM poktan WHERE id_poktan = ?");
$stmt->execute([$id]);
$poktan = $stmt->fetch();

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
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Poktan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= base_url() ?>">Monitoring CPCL BRMP</a>
    </div>
</nav>
<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Edit Poktan</h4>

            <form action="<?= base_url('poktan/update.php') ?>" method="POST">
                <input type="hidden" name="id_poktan" value="<?= $poktan['id_poktan'] ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Poktan</label>
                    <input type="text" name="nama_poktan" class="form-control" value="<?= e($poktan['nama_poktan']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3"><?= e($poktan['alamat']) ?></textarea>
                </div>

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

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= base_url('?page=poktan') ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    $('#provinsi_id').on('change', function() {
        let provinsiId = $(this).val();

        $('#kabupaten_id').html('<option value="">-- Pilih Kabupaten --</option>');
        $('#kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');

        if (provinsiId) {
            $.ajax({
                url: '<?= base_url("ajax/get_kabupaten.php") ?>',
                type: 'GET',
                data: { provinsi_id: provinsiId },
                success: function(response) {
                    $('#kabupaten_id').html(response);
                    $('#kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
                }
            });
        }
    });

    $('#kabupaten_id').on('change', function() {
        let kabupatenId = $(this).val();

        $('#kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');

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
});
</script>
</body>
</html>