<?php
if (!isset($pageTitle)) $pageTitle = 'Portal Ketersediaan Benih UPBS';
if (!isset($activePublicPage)) $activePublicPage = 'home';

function publicActive(string $page, string $activePublicPage): string
{
    return $page === $activePublicPage ? 'active' : '';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --brand-green: #198754;
            --brand-green-dark: #146c43;
            --brand-light: #f6fbf8;
            --brand-border: #e7efe9;
            --text-soft: #6c757d;
        }

        body {
            background: #f7f8fa;
            color: #212529;
            overflow-x: hidden;
        }

        .public-navbar {
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #edf0f2;
        }

        .public-brand {
            font-weight: 700;
            color: var(--brand-green-dark);
        }

        .public-brand img {
            height: 38px;
            width: auto;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            color: #ffffff;
        }

        .navbar-nav .nav-link.active,
        .navbar-nav .nav-link:hover {
            color: #cdf214;
        }

        .hero-public {
            background:
                radial-gradient(circle at top right, rgba(25,135,84,.12), transparent 28%),
                linear-gradient(135deg, #ffffff, #f6fbf8);
            border: 1px solid var(--brand-border);
            border-radius: 28px;
            padding: 38px;
            box-shadow: 0 14px 30px rgba(0,0,0,.04);
        }

        .hero-title {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.15;
            color: #163020;
        }

        .hero-subtitle {
            color: #4b5563;
            max-width: 760px;
            font-size: 1rem;
        }

        .search-shell {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(0,0,0,.05);
        }

        .stats-card,
        .filter-card,
        .catalog-card,
        .province-card,
        .detail-card,
        .info-card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 10px 24px rgba(0,0,0,.05);
        }

        .stats-card {
            background: #fff;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(25,135,84,.12);
            color: var(--brand-green);
            font-size: 1.15rem;
        }

        .stats-value {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            color: #1f2937;
        }

        .stats-label {
            color: var(--text-soft);
            font-size: .92rem;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1f2937;
        }

        .section-subtitle {
            color: var(--text-soft);
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #374151;
            font-size: .9rem;
            font-weight: 600;
        }

        .stock-card {
            border: 1px solid #edf1f3;
            border-radius: 20px;
            background: #fff;
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .stock-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(0,0,0,.07);
        }

        .stock-title {
            font-size: 1.06rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stock-meta {
            font-size: .92rem;
            color: #6b7280;
        }

        .stock-number {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--brand-green-dark);
        }

        .badge-soft-success {
            background: rgba(25,135,84,.12);
            color: var(--brand-green-dark);
        }

        .badge-soft-warning {
            background: rgba(255,193,7,.18);
            color: #8a6d03;
        }

        .badge-soft-danger {
            background: rgba(220,53,69,.14);
            color: #b02a37;
        }

        .badge-soft-secondary {
            background: rgba(108,117,125,.15);
            color: #5c636a;
        }

        .view-switch .btn.active {
            background: var(--brand-green);
            border-color: var(--brand-green);
            color: #fff;
        }

        .catalog-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .catalog-table {
            min-width: 1100px;
        }

        .catalog-table th,
        .catalog-table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .province-card {
            background: #fff;
            border: 1px solid #edf1f3;
            height: 100%;
        }

        .province-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
        }

        .province-metric {
            font-size: .92rem;
            color: #6b7280;
        }

        .footer-public {
            background: #ffffff;
            border-top: 1px solid #eceff1;
            color: #6b7280;
        }

        @media (max-width: 991.98px) {
            .hero-public {
                padding: 24px;
            }

            .hero-title {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg public-navbar sticky-top bg-success">
    <div class="container py-2">
        <a class="navbar-brand public-brand d-flex align-items-center gap-2" href="<?= base_url('public_upbs/index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo">
            <span class="text-white">Portal Benih UPBS</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(35%);"></span>
        </button>

        <div class="collapse navbar-collapse" id="publicNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= publicActive('home', $activePublicPage) ?>" href="<?= base_url('public_upbs/index.php') ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('public_upbs/index.php#katalog') ?>">Katalog Benih</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('public_upbs/index.php#sebaran') ?>">Sebaran Wilayah</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('public_upbs/index.php#tentang') ?>">Tentang</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">