<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../helpers/functions.php';

$pageTitle = 'Tambah Kelas Benih - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'kelas_benih';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Tambah Kelas Benih</h4>
                <div class="text-muted">Tambahkan data kelas benih baru.</div>
            </div>
            <a href="<?= base_url('page_upbs/kelas_benih/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/kelas_benih/store.php') ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Kode Kelas</label>
                <input type="text" name="kode_kelas" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Kelas</label>
                <input type="text" name="nama_kelas" class="form-control">
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">Status Aktif</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="<?= base_url('page_upbs/kelas_benih/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>