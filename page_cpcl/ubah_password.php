<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../helpers/functions.php';

$success = $_SESSION['success_message'] ?? '';
$error   = $_SESSION['error_message'] ?? '';

unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Dashboard Provinsi</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Dashboard Provinsi CPCL</span>
        </a>

        <div class="d-flex align-items-center text-white gap-2">
            <span><?= e($userLogin['nama_lengkap']) ?> - <?= e($userLogin['nama_provinsi']) ?></span>
            <a href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>" class="btn btn-sm btn-outline-light">Kembali</a>
            <a href="<?= base_url('page_cpcl/logout.php') ?>" class="btn btn-sm btn-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h4 class="mb-4">Ubah Password</h4>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('page_cpcl/proses_ubah_password.php') ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="password_lama" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" required>
                    <div class="form-text">Gunakan password yang kuat dan mudah Anda ingat.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password_baru" class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
                    <a href="<?= base_url('page_cpcl/dashboard_provinsi.php') ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>