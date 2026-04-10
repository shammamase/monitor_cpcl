<?php
if (!isset($pageTitle)) $pageTitle = 'Dashboard UPBS';
if (!isset($activeMenu)) $activeMenu = '';
if (!isset($activeSubmenu)) $activeSubmenu = '';

function isActiveMenu($menu, $activeMenu) {
    return $menu === $activeMenu ? 'active-link' : '';
}

function isActiveSubmenu($submenu, $activeSubmenu) {
    return $submenu === $activeSubmenu ? 'active-link' : '';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7f6;
        }

        .topbar {
            height: 64px;
            background: #198754;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            z-index: 1030;
        }

        .topbar .navbar-brand img {
            height: 36px;
            width: auto;
        }

        .layout-shell {
            min-height: calc(100vh - 64px);
        }

        .sidebar-desktop {
            width: 280px;
            background: #ffffff;
            border-right: 1px solid #e9ecef;
            min-height: calc(100vh - 64px);
            position: sticky;
            top: 64px;
        }

        .sidebar-inner {
            padding: 22px 16px;
        }

        .sidebar-userbox {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.10), rgba(25, 135, 84, 0.04));
            border: 1px solid rgba(25, 135, 84, 0.12);
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .sidebar-userbox .user-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sidebar-userbox .user-meta {
            color: #6c757d;
            font-size: .92rem;
            line-height: 1.4;
        }

        .menu-section-title {
            font-size: .78rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin: 20px 10px 10px;
        }

        .sidebar-menu .nav-link {
            color: #212529;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-weight: 500;
        }

        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link:focus {
            background: #f1f3f5;
            color: #198754;
        }

        .sidebar-menu .nav-link.active-link {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-weight: 700;
        }

        .menu-label-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(25, 135, 84, 0.10);
            color: #198754;
            font-size: .95rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .submenu {
            padding-left: 14px;
            margin-top: 6px;
        }

        .submenu .nav-link {
            padding: 10px 12px;
            font-size: .95rem;
        }

        .content-area {
            padding: 24px;
            flex: 1;
        }

        .page-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.06);
        }

        .offcanvas-sidebar .offcanvas-header {
            background: #198754;
            color: #fff;
        }

        .offcanvas-sidebar .btn-close {
            filter: invert(1);
        }

        .welcome-card {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            background: linear-gradient(135deg, #198754, #157347);
            color: #fff;
            box-shadow: 0 18px 38px rgba(25, 135, 84, 0.18);
        }

        .welcome-card .badge-soft {
            display: inline-block;
            background: rgba(255,255,255,0.16);
            color: #fff;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: .88rem;
            margin-bottom: 16px;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .welcome-text {
            font-size: 1rem;
            opacity: .95;
            margin-bottom: 0;
            max-width: 760px;
        }

        .content-placeholder {
            border: 1px dashed #ced4da;
            border-radius: 18px;
            background: #fff;
            padding: 28px;
            color: #6c757d;
            margin-top: 24px;
        }

        @media (max-width: 991.98px) {
            .content-area {
                padding: 18px;
            }

            .welcome-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark topbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarUpbs" aria-controls="sidebarUpbs">
                ☰
            </button>

            <a class="navbar-brand d-flex align-items-center gap-2 mb-0" href="<?= base_url('page_upbs/dashboard.php') ?>">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo">
                <span>Dashboard UPBS</span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-md-inline"><?= e($userUpbs['nama_lengkap']) ?></span>
            <a href="<?= base_url('page_upbs/logout.php') ?>" class="btn btn-sm btn-light">Logout</a>
        </div>
    </div>
</nav>

<div class="d-flex layout-shell">
    <aside class="sidebar-desktop d-none d-lg-block">
        <div class="sidebar-inner">
            <div class="sidebar-userbox">
                <div class="user-name"><?= e($userUpbs['nama_lengkap']) ?></div>
                <div class="user-meta"><?= e($userUpbs['nama_satker']) ?: '-' ?></div>
                <div class="user-meta"><?= e($userUpbs['nama_provinsi']) ?: '-' ?></div>
            </div>

            <div class="menu-section-title">Navigasi</div>

            <ul class="nav flex-column sidebar-menu">
                <li class="nav-item mb-1">
                    <a href="<?= base_url('page_upbs/dashboard.php') ?>" class="nav-link <?= isActiveMenu('dashboard', $activeMenu) ?>">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">D</span>
                            <span>Dashboard</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item mb-1">
                    <a class="nav-link <?= isActiveMenu('master', $activeMenu) ?>" data-bs-toggle="collapse" href="#submenuMasterDesktop" role="button" aria-expanded="true" aria-controls="submenuMasterDesktop">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">M</span>
                            <span>Data Master</span>
                        </span>
                        <span>▾</span>
                    </a>

                    <div class="collapse show submenu" id="submenuMasterDesktop">
                        <ul class="nav flex-column">
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/komoditas/index.php') ?>" class="nav-link <?= isActiveSubmenu('komoditas', $activeSubmenu) ?>">Komoditas</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="nav-link <?= isActiveSubmenu('varietas', $activeSubmenu) ?>">Varietas / Galur</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/kelas_benih/index.php') ?>" class="nav-link <?= isActiveSubmenu('kelas_benih', $activeSubmenu) ?>">Kelas Benih</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/satuan/index.php') ?>" class="nav-link <?= isActiveSubmenu('satuan', $activeSubmenu) ?>">Satuan</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="#" class="nav-link <?= isActiveSubmenu('benih_sumber', $activeSubmenu) ?>">Benih Sumber</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item mb-1">
                    <a href="#" class="nav-link <?= isActiveMenu('input_stok', $activeMenu) ?>">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">I</span>
                            <span>Input Stok</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item mb-1">
                    <a href="#" class="nav-link <?= isActiveMenu('profil', $activeMenu) ?>">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">P</span>
                            <span>Profil</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item mb-1">
                    <a href="<?= base_url('page_upbs/logout.php') ?>" class="nav-link">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">L</span>
                            <span>Logout</span>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <div class="offcanvas offcanvas-start offcanvas-sidebar d-lg-none" tabindex="-1" id="sidebarUpbs" aria-labelledby="sidebarUpbsLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarUpbsLabel">Menu UPBS</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="sidebar-userbox">
                <div class="user-name"><?= e($userUpbs['nama_lengkap']) ?></div>
                <div class="user-meta"><?= e($userUpbs['nama_satker']) ?: '-' ?></div>
                <div class="user-meta"><?= e($userUpbs['nama_provinsi']) ?: '-' ?></div>
            </div>

            <ul class="nav flex-column sidebar-menu">
                <li class="nav-item mb-1">
                    <a href="<?= base_url('page_upbs/dashboard.php') ?>" class="nav-link <?= isActiveMenu('dashboard', $activeMenu) ?>">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">D</span>
                            <span>Dashboard</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item mb-1">
                    <a class="nav-link <?= isActiveMenu('master', $activeMenu) ?>" data-bs-toggle="collapse" href="#submenuMasterMobile" role="button" aria-expanded="true" aria-controls="submenuMasterMobile">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">M</span>
                            <span>Data Master</span>
                        </span>
                        <span>▾</span>
                    </a>

                    <div class="collapse show submenu" id="submenuMasterMobile">
                        <ul class="nav flex-column">
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/komoditas/index.php') ?>" class="nav-link <?= isActiveSubmenu('komoditas', $activeSubmenu) ?>">Komoditas</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/varietas/index.php') ?>" class="nav-link <?= isActiveSubmenu('varietas', $activeSubmenu) ?>">Varietas / Galur</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/kelas_benih/index.php') ?>" class="nav-link <?= isActiveSubmenu('kelas_benih', $activeSubmenu) ?>">Kelas Benih</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="<?= base_url('page_upbs/satuan/index.php') ?>" class="nav-link <?= isActiveSubmenu('satuan', $activeSubmenu) ?>">Satuan</a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="#" class="nav-link <?= isActiveSubmenu('benih_sumber', $activeSubmenu) ?>">Benih Sumber</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item mb-1">
                    <a href="#" class="nav-link <?= isActiveMenu('input_stok', $activeMenu) ?>">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">I</span>
                            <span>Input Stok</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item mb-1">
                    <a href="#" class="nav-link <?= isActiveMenu('profil', $activeMenu) ?>">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">P</span>
                            <span>Profil</span>
                        </span>
                    </a>
                </li>

                <li class="nav-item mb-1">
                    <a href="<?= base_url('page_upbs/logout.php') ?>" class="nav-link">
                        <span class="menu-label-wrap">
                            <span class="menu-icon">L</span>
                            <span>Logout</span>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <main class="content-area">