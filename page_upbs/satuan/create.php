<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../helpers/functions.php';

$pageTitle = 'Tambah Satuan - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'satuan';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Tambah Satuan</h4>
                <div class="text-muted">Tambahkan data satuan baru.</div>
            </div>
            <a href="<?= base_url('page_upbs/satuan/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/satuan/store.php') ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Nama Satuan</label>
                <input type="text" name="nama_satuan" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Simbol</label>
                <input type="text" name="simbol" class="form-control" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="<?= base_url('page_upbs/satuan/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>