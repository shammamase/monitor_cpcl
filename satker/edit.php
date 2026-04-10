<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM satker WHERE id_satker = ? LIMIT 1");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die('Data satker tidak ditemukan.');
}

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();

$stmtKab = $pdo->prepare("
    SELECT id, name
    FROM kabupatens
    WHERE provinsi_id = ?
    ORDER BY name ASC
");
$stmtKab->execute([$data['provinsi_id']]);
$kabupatenList = $stmtKab->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Satker</title>

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
<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Edit Satker</h4>

            <form action="<?= base_url('satker/update.php') ?>" method="POST">
                <input type="hidden" name="id_satker" value="<?= $data['id_satker'] ?>">

                <div class="mb-3">
                    <label class="form-label">Kode Satker</label>
                    <input type="text" name="kode_satker" class="form-control" value="<?= e($data['kode_satker']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Satker</label>
                    <input type="text" name="nama_satker" class="form-control" value="<?= e($data['nama_satker']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Satker</label>
                    <input type="text" name="jenis_satker" class="form-control" value="<?= e($data['jenis_satker']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi_id" id="provinsi_id" class="form-select select2" required>
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsiList as $prov): ?>
                            <option value="<?= $prov['id'] ?>" <?= ($data['provinsi_id'] == $prov['id']) ? 'selected' : '' ?>>
                                <?= e($prov['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kabupaten</label>
                    <select name="kabupaten_id" id="kabupaten_id" class="form-select select2" required>
                        <option value="">-- Pilih Kabupaten --</option>
                        <?php foreach ($kabupatenList as $kab): ?>
                            <option value="<?= $kab['id'] ?>" <?= ($data['kabupaten_id'] == $kab['id']) ? 'selected' : '' ?>>
                                <?= e($kab['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3"><?= e($data['alamat']) ?></textarea>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ((int)$data['is_active'] === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Status Aktif</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= base_url('indexyz.php?page=satker') ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

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
});
</script>
</body>
</html>