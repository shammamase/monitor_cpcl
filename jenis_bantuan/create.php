<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$sumberList = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jenis Bantuan</title>
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
            <h4 class="mb-4">Tambah Jenis Bantuan</h4>

            <form action="<?= base_url('jenis_bantuan/store.php') ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" class="form-select select2" required>
                        <option value="">-- Pilih Sumber Bantuan --</option>
                        <?php foreach ($sumberList as $sumber): ?>
                            <option value="<?= $sumber['id_sumber'] ?>"><?= e($sumber['nama_sumber']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Jenis Bantuan</label>
                    <input type="text" name="nama_jenis_bantuan" class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= base_url('?page=jenis_bantuan') ?>" class="btn btn-secondary">Kembali</a>
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
    });
</script>
</body>
</html>