<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM jenis_bantuan WHERE id_jenis_bantuan = ? LIMIT 1");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die('Data jenis bantuan tidak ditemukan.');
}

$sumberList = $pdo->query("SELECT id_sumber, nama_sumber FROM sumber_bantuan ORDER BY nama_sumber ASC")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jenis Bantuan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Edit Jenis Bantuan</h4>

            <form action="<?= base_url('jenis_bantuan/update.php') ?>" method="POST">
                <input type="hidden" name="id_jenis_bantuan" value="<?= $data['id_jenis_bantuan'] ?>">

                <div class="mb-3">
                    <label class="form-label">Sumber Bantuan</label>
                    <select name="id_sumber" class="form-select" required>
                        <option value="">-- Pilih Sumber Bantuan --</option>
                        <?php foreach ($sumberList as $sumber): ?>
                            <option value="<?= $sumber['id_sumber'] ?>" <?= $data['id_sumber'] == $sumber['id_sumber'] ? 'selected' : '' ?>>
                                <?= e($sumber['nama_sumber']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Jenis Bantuan</label>
                    <input type="text" name="nama_jenis_bantuan" class="form-control" value="<?= e($data['nama_jenis_bantuan']) ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= base_url('?page=jenis_bantuan') ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>