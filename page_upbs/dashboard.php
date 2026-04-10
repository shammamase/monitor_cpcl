<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../helpers/functions.php';

$pageTitle = 'Dashboard UPBS';
$activeMenu = 'dashboard';
$activeSubmenu = '';

require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="card welcome-card">
    <div class="card-body p-4 p-lg-5">
        <span class="badge-soft">Selamat Datang</span>
        <div class="welcome-title">Portal Pengelolaan UPBS</div>
        <p class="welcome-text">
            Sistem ini digunakan untuk mendukung pengelolaan data Unit Pengelola Benih Sumber secara lebih tertib, terstruktur, dan mudah dipantau. Silakan gunakan menu di sisi kiri untuk mengakses data master maupun input stok.
        </p>
    </div>
</div>

<div class="content-placeholder">
    Area konten utama dashboard dapat dikembangkan lebih lanjut sesuai menu yang dipilih.
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>