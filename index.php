<?php
require_once __DIR__ . '/helpers/functions.php';

$page = $_GET['page'] ?? 'status_verifikasi';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring CPCL BRMP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url() ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL BRMP</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="menu-card mb-4 bg-warning">
        <h3 class="mb-1">Sistem Pemantauan CPCL</h3>
        <p class="mb-0">Pemantauan internal BRMP untuk data Poktan dan Status Verifikasi.</p>
    </div>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item me-2">
            <a class="nav-link <?= $page == 'poktan' ? 'active' : '' ?>" href="<?= base_url('?page=poktan') ?>">Poktan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page == 'status_verifikasi' ? 'active' : '' ?>" href="<?= base_url('?page=status_verifikasi') ?>">Status Verifikasi</a>
        </li>
    </ul>

    <?php if ($page == 'poktan'): ?>
        <?php include __DIR__ . '/poktan/index.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/status_verifikasi/index.php'; ?>
    <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2-filter').select2({
        width: '100%',
        allowClear: true
    });
});
</script>
</body>
</html>