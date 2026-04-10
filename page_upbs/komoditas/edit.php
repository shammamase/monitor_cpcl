<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM komoditas WHERE id_komoditas = ? LIMIT 1");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error_message_upbs'] = 'Data komoditas tidak ditemukan.';
    header('Location: ' . base_url('page_upbs/komoditas/index.php'));
    exit;
}

$pageTitle = 'Edit Komoditas - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'komoditas';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Edit Komoditas</h4>
                <div class="text-muted">Perbarui data komoditas pada modul UPBS.</div>
            </div>
            <a href="<?= base_url('page_upbs/komoditas/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/komoditas/update.php') ?>" method="POST">
            <input type="hidden" name="id_komoditas" value="<?= $data['id_komoditas'] ?>">

            <div class="mb-3">
                <label class="form-label">Nama Komoditas</label>
                <input type="text" name="nama_komoditas" class="form-control" value="<?= e($data['nama_komoditas']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Kategori Komoditas</label>
                <select name="kategori_komoditas" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="tanaman" <?= $data['kategori_komoditas'] === 'tanaman' ? 'selected' : '' ?>>Tanaman</option>
                    <option value="ternak" <?= $data['kategori_komoditas'] === 'ternak' ? 'selected' : '' ?>>Ternak</option>
                </select>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= (int)$data['is_active'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Status Aktif</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="<?= base_url('page_upbs/komoditas/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>