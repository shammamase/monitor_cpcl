<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsiList = $pdo->query("SELECT id, name FROM provinsis ORDER BY name ASC")->fetchAll();
$satkerList = $pdo->query("
    SELECT id_satker, nama_satker, provinsi_id
    FROM satker
    WHERE is_active = 1
    ORDER BY nama_satker ASC
")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
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
            <h4 class="mb-4">Tambah User</h4>

            <form action="<?= base_url('users/store.php') ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Satker</label>
                    <select name="id_satker" id="id_satker" class="form-select select2" required>
                        <option value="">-- Pilih Satker --</option>
                        <?php foreach ($satkerList as $s): ?>
                            <option value="<?= $s['id_satker'] ?>" data-provinsi="<?= $s['provinsi_id'] ?>">
                                <?= e($s['nama_satker']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="provinsi_id" id="provinsi_id">

                <div class="mb-3">
                    <label class="form-label">Provinsi</label>
                    <select id="provinsi_preview" class="form-select select2" disabled>
                        <option value="">-- Otomatis dari Satker --</option>
                        <?php foreach ($provinsiList as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= e($prov['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">Status Aktif</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= base_url('indexyz.php?page=users') ?>" class="btn btn-secondary">Kembali</a>
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

    $('#id_satker').on('change', function() {
        let provinsiId = $(this).find(':selected').data('provinsi') || '';
        $('#provinsi_id').val(provinsiId);
        $('#provinsi_preview').val(provinsiId).trigger('change');
    });
});
</script>
</body>
</html>