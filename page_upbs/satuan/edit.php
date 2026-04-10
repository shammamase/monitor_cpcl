<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM satuan WHERE id_satuan = ? LIMIT 1");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error_message_upbs'] = 'Data satuan tidak ditemukan.';
    header('Location: ' . base_url('page_upbs/satuan/index.php'));
    exit;
}

$pageTitle = 'Edit Satuan - UPBS';
$activeMenu = 'master';
$activeSubmenu = 'satuan';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Edit Satuan</h4>
                <div class="text-muted">Perbarui data satuan.</div>
            </div>
            <a href="<?= base_url('page_upbs/satuan/index.php') ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <form action="<?= base_url('page_upbs/satuan/update.php') ?>" method="POST">
            <input type="hidden" name="id_satuan" value="<?= $data['id_satuan'] ?>">

            <div class="mb-3">
                <label class="form-label">Nama Satuan</label>
                <input type="text" name="nama_satuan" class="form-control" value="<?= e($data['nama_satuan']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Simbol</label>
                <input type="text" name="simbol" class="form-control" value="<?= e($data['simbol']) ?>" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="<?= base_url('page_upbs/satuan/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>