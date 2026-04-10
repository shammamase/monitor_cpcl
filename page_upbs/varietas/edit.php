<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM varietas WHERE id_varietas = ? LIMIT 1");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error_message_upbs'] = 'Data varietas/galur tidak ditemukan.';
    header('Location: ' . base_url('page_upbs/varietas/index.php'));
    exit;
}

$komoditasList = $pdo->query("
    SELECT id_komoditas, nama_komoditas
    FROM komoditas
    WHERE is_active = 1
    ORDER BY nama_komoditas ASC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Edit Varietas / Galur - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'varietas';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Edit Varietas / Galur</h4>
                <div class="text-muted">Perbarui data varietas atau galur.</div>
            </div>
            <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/varietas/update.php') ?>" method="POST">
            <input type="hidden" name="id_varietas" value="<?= $data['id_varietas'] ?>">

            <div class="mb-3">
                <label class="form-label">Komoditas</label>
                <select name="id_komoditas" class="form-select" required>
                    <option value="">-- Pilih Komoditas --</option>
                    <?php foreach ($komoditasList as $k): ?>
                        <option value="<?= $k['id_komoditas'] ?>" <?= ($data['id_komoditas'] == $k['id_komoditas']) ? 'selected' : '' ?>>
                            <?= e($k['nama_komoditas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Varietas / Galur</label>
                <input type="text" name="nama_varietas" class="form-control" value="<?= e($data['nama_varietas']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Jenis</label>
                <select name="jenis_varietas" class="form-select" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="varietas" <?= $data['jenis_varietas'] === 'varietas' ? 'selected' : '' ?>>Varietas</option>
                    <option value="galur" <?= $data['jenis_varietas'] === 'galur' ? 'selected' : '' ?>>Galur</option>
                </select>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= (int)$data['is_active'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Status Aktif</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>