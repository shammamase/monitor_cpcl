<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM sumber_bantuan WHERE id_sumber = ? LIMIT 1");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die('Data sumber bantuan tidak ditemukan.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sumber Bantuan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h4 class="mb-4">Edit Sumber Bantuan</h4>

            <form action="<?= base_url('sumber_bantuan/update.php') ?>" method="POST">
                <input type="hidden" name="id_sumber" value="<?= $data['id_sumber'] ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Sumber Bantuan</label>
                    <input type="text" name="nama_sumber" class="form-control" value="<?= e($data['nama_sumber']) ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= base_url('?page=sumber_bantuan') ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>