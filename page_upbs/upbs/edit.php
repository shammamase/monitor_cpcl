<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_satker = (int)($userUpbs['id_satker'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM upbs
    WHERE id_upbs = ?
      AND id_satker = ?
    LIMIT 1
");
$stmt->execute([$id, $id_satker]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error_message_upbs'] = 'Data UPBS tidak ditemukan.';
    header('Location: ' . base_url('page_upbs/upbs/index.php'));
    exit;
}

$pageTitle = 'Edit UPBS - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'upbs';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Edit UPBS</h4>
                <div class="text-muted">Perbarui data UPBS pada satker Anda.</div>
            </div>
            <a href="<?= base_url('page_upbs/upbs/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/upbs/update.php') ?>" method="POST">
            <input type="hidden" name="id_upbs" value="<?= (int)$data['id_upbs'] ?>">

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Satker</label>
                    <input type="text" class="form-control" value="<?= e($userUpbs['nama_satker'] ?? '-') ?>" readonly>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Kode UPBS</label>
                    <input type="text" name="kode_upbs" class="form-control" value="<?= e($data['kode_upbs']) ?>" required>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Nama UPBS</label>
                    <input type="text" name="nama_upbs" class="form-control" value="<?= e($data['nama_upbs']) ?>" required>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Jenis UPBS</label>
                    <select name="jenis_upbs" class="form-select" required>
                        <option value="">-- Pilih Jenis UPBS --</option>
                        <option value="tanaman_pangan" <?= $data['jenis_upbs'] === 'tanaman_pangan' ? 'selected' : '' ?>>Tanaman Pangan</option>
                        <option value="hortikultura" <?= $data['jenis_upbs'] === 'hortikultura' ? 'selected' : '' ?>>Hortikultura</option>
                        <option value="perkebunan" <?= $data['jenis_upbs'] === 'perkebunan' ? 'selected' : '' ?>>Perkebunan</option>
                        <option value="peternakan" <?= $data['jenis_upbs'] === 'peternakan' ? 'selected' : '' ?>>Peternakan</option>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">No. HP Pengelola</label>
                    <input type="text" name="no_hp_pengelola" class="form-control" value="<?= e($data['no_hp_pengelola']) ?>" placeholder="Contoh: 081234567890">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= (int)$data['is_active'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Status Aktif</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="<?= base_url('page_upbs/upbs/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>