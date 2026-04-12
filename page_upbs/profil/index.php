<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_user = (int)($userUpbs['id_user'] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        u.id_user,
        u.nama_lengkap,
        u.username,
        u.provinsi_id AS user_provinsi_id,
        u.kabupaten_id AS user_kabupaten_id,
        u.id_satker,
        s.nama_satker,
        s.provinsi_id AS satker_provinsi_id,
        s.kabupaten_id AS satker_kabupaten_id
    FROM users u
    LEFT JOIN satker s ON u.id_satker = s.id_satker
    WHERE u.id_user = ?
    LIMIT 1
");
$stmt->execute([$id_user]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['login_error_upbs'] = 'Data akun tidak ditemukan.';
    header('Location: ' . base_url('page_upbs/logout.php'));
    exit;
}

if (empty($data['id_satker'])) {
    $_SESSION['error_message_upbs'] = 'Akun ini belum terhubung ke satker.';
    header('Location: ' . base_url('page_upbs/dashboard.php'));
    exit;
}

$provinsiDipakai  = $data['satker_provinsi_id'] ?: $data['user_provinsi_id'];
$kabupatenDipakai = $data['satker_kabupaten_id'] ?: $data['user_kabupaten_id'];

$provinsiList = $pdo->query("
    SELECT id, name
    FROM provinsis
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$kabupatenList = [];
if (!empty($provinsiDipakai)) {
    $stmtKab = $pdo->prepare("
        SELECT id, name
        FROM kabupatens
        WHERE provinsi_id = ?
        ORDER BY name ASC
    ");
    $stmtKab->execute([$provinsiDipakai]);
    $kabupatenList = $stmtKab->fetchAll(PDO::FETCH_ASSOC);
}

$success = $_SESSION['success_message_upbs'] ?? '';
$error   = $_SESSION['error_message_upbs'] ?? '';
unset($_SESSION['success_message_upbs'], $_SESSION['error_message_upbs']);

$pageTitle = 'Profil - UPBS';
$activeMenu = 'profil';
$activeSubmenu = '';

require_once __DIR__ . '/../partials/layout_top.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 4px 8px;
        border: 1px solid #ced4da !important;
        border-radius: .375rem !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Profil Akun</h4>
                <div class="text-muted">Kelola informasi akun dan wilayah satker Anda.</div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('page_upbs/profil/update.php') ?>" method="POST">
            <input type="hidden" name="id_user" value="<?= $data['id_user'] ?>">

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" value="<?= e($data['nama_lengkap']) ?>" required>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= e($data['username']) ?>" required>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Satker</label>
                    <input type="text" class="form-control" value="<?= e($data['nama_satker']) ?: '-' ?>" readonly>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi_id" id="provinsi_id" class="form-select select2" required>
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsiList as $prov): ?>
                            <option value="<?= $prov['id'] ?>" <?= ($provinsiDipakai == $prov['id']) ? 'selected' : '' ?>>
                                <?= e($prov['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Kabupaten</label>
                    <select name="kabupaten_id" id="kabupaten_id" class="form-select select2" required>
                        <option value="">-- Pilih Kabupaten --</option>
                        <?php foreach ($kabupatenList as $kab): ?>
                            <option value="<?= $kab['id'] ?>" <?= ($kabupatenDipakai == $kab['id']) ? 'selected' : '' ?>>
                                <?= e($kab['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control">
                    <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password_baru" class="form-control">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="<?= base_url('page_upbs/dashboard.php') ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    $('#provinsi_id').on('change', function() {
        let provinsiId = $(this).val();

        $('#kabupaten_id').html('<option value="">-- Pilih Kabupaten --</option>').trigger('change');

        if (provinsiId) {
            $.ajax({
                url: '<?= base_url("ajax/get_kabupaten.php") ?>',
                type: 'GET',
                data: { provinsi_id: provinsiId },
                success: function(response) {
                    $('#kabupaten_id').html(response).trigger('change');
                }
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../partials/layout_bottom.php'; ?>