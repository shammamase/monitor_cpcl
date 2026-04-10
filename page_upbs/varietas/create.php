<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$komoditasList = $pdo->query("
    SELECT id_komoditas, nama_komoditas
    FROM komoditas
    WHERE is_active = 1
    ORDER BY nama_komoditas ASC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Tambah Varietas / Galur - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'varietas';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Tambah Varietas / Galur</h4>
                <div class="text-muted">Tambahkan data varietas atau galur baru.</div>
            </div>
            <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/varietas/store.php') ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Komoditas</label>
                <select name="id_komoditas" class="form-select" required>
                    <option value="">-- Pilih Komoditas --</option>
                    <?php foreach ($komoditasList as $k): ?>
                        <option value="<?= $k['id_komoditas'] ?>"><?= e($k['nama_komoditas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Varietas / Galur</label>
                <input type="text" name="nama_varietas" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Jenis</label>
                <select name="jenis_varietas" class="form-select" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="varietas">Varietas</option>
                    <option value="galur">Galur</option>
                </select>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">Status Aktif</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>