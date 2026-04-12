<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../helpers/functions.php';

$pageTitle = 'Tambah UPBS - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'upbs';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Tambah UPBS</h4>
                <div class="text-muted">Tambahkan data UPBS baru untuk satker Anda.</div>
            </div>
            <a href="<?= base_url('page_upbs/upbs/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/upbs/store.php') ?>" method="POST">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Satker</label>
                    <input type="text" class="form-control" value="<?= e($userUpbs['nama_satker'] ?? '-') ?>" readonly>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Kode UPBS</label>
                    <input type="text" name="kode_upbs" class="form-control" required>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Nama UPBS</label>
                    <input type="text" name="nama_upbs" class="form-control" required>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Jenis UPBS</label>
                    <select name="jenis_upbs" class="form-select" required>
                        <option value="">-- Pilih Jenis UPBS --</option>
                        <option value="tanaman_pangan">Tanaman Pangan</option>
                        <option value="hortikultura">Hortikultura</option>
                        <option value="perkebunan">Perkebunan</option>
                        <option value="peternakan">Peternakan</option>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">No. HP Pengelola</label>
                    <input type="text" name="no_hp_pengelola" class="form-control" placeholder="Contoh: 081234567890">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Status Aktif</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="<?= base_url('page_upbs/upbs/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>