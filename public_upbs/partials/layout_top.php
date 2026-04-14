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
            --brand-green-soft: rgba(25,135,84,.12);
            --brand-light: #f6fbf8;
            --brand-border: #e7efe9;
            --text-soft: #6c757d;
            --text-main: #1f2937;
            --surface-card: #ffffff;
        }

        html, body {
            overflow-x: hidden;
        }

        body {
            background: #f7f8fa;
            color: var(--text-main);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .public-navbar {
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #edf0f2;
        }

        .public-brand {
            font-weight: 800;
            color: var(--brand-green-dark);
            letter-spacing: -.2px;
        }

        .public-brand img {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .navbar-nav .nav-link {
            font-weight: 600;
            color: #334155;
            border-radius: 999px;
            padding: .55rem .95rem;
        }

        .navbar-nav .nav-link.active,
        .navbar-nav .nav-link:hover {
            color: var(--brand-green);
            background: rgba(25,135,84,.08);
        }

        .navbar-cta {
            border-radius: 999px;
            font-weight: 600;
            padding-inline: 16px;
        }

        .hero-public {
            background:
                radial-gradient(circle at top right, rgba(25,135,84,.14), transparent 28%),
                linear-gradient(135deg, #ffffff, #f6fbf8);
            border: 1px solid var(--brand-border);
            border-radius: 28px;
            padding: 38px;
            box-shadow: 0 14px 30px rgba(0,0,0,.04);
        }

        .hero-title {
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1.12;
            color: #163020;
            letter-spacing: -.5px;
        }

        .hero-subtitle {
            color: #4b5563;
            max-width: 760px;
            font-size: 1rem;
            line-height: 1.65;
        }

        .search-shell {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(0,0,0,.05);
        }

        .stats-card,
        .catalog-card,
        .province-card,
        .detail-card,
        .info-card,
        .metric-card,
        .meta-card,
        .related-card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 10px 24px rgba(0,0,0,.05);
            background: var(--surface-card);
        }

        .stats-card {
            height: 100%;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-green-soft);
            color: var(--brand-green);
            font-size: 1.15rem;
        }

        .stats-value {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-main);
        }

        .stats-label {
            color: var(--text-soft);
            font-size: .92rem;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -.3px;
        }

        .section-subtitle {
            color: var(--text-soft);
            line-height: 1.55;
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
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .stock-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(0,0,0,.07);
            border-color: #dce7df;
        }

        .stock-title {
            font-size: 1.06rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.4;
        }

        .stock-meta {
            font-size: .92rem;
            color: #6b7280;
            line-height: 1.5;
        }

        .stock-number {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--brand-green-dark);
            line-height: 1.2;
        }

        .province-card {
            background: #fff;
            border: 1px solid #edf1f3;
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .province-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(0,0,0,.07);
            border-color: #dce7df;
        }

        .province-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .province-metric {
            font-size: .92rem;
            color: #6b7280;
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

        .label-muted,
        .meta-label {
            font-size: .88rem;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .value-strong,
        .meta-value {
            font-weight: 600;
            color: var(--text-main);
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand-green-dark);
            line-height: 1.15;
        }

        .metric-label {
            color: #6b7280;
            font-size: .92rem;
        }

        .related-item {
            border: 1px solid #edf1f3;
            border-radius: 18px;
            background: #fff;
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .related-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(0,0,0,.07);
        }

        .footer-public {
            background: #ffffff;
            border-top: 1px solid #eceff1;
            color: #6b7280;
        }

        .footer-title {
            font-weight: 700;
            color: var(--text-main);
        }

        .public-breadcrumb {
            font-size: .92rem;
            color: #6b7280;
        }

        .public-breadcrumb a {
            color: var(--brand-green-dark);
            text-decoration: none;
        }

        .public-breadcrumb a:hover {
            text-decoration: underline;
        }

        @media (max-width: 991.98px) {
            .hero-public {
                padding: 24px;
            }

            .hero-title {
                font-size: 1.7rem;
            }

            .navbar-cta {
                width: 100%;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg public-navbar sticky-top">
    <div class="container py-2">
        <a class="navbar-brand public-brand d-flex align-items-center gap-2" href="<?= base_url('public_upbs/index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo">
            <span>Portal Benih UPBS</span>
        </a>
        <!--
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(35%);"></span>
        </button>

        <div class="collapse navbar-collapse" id="publicNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= publicActive('home', $activePublicPage) ?>" href="<?= base_url('public_upbs/index.php') ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('public_upbs/index.php') ?>">Komoditas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('public_upbs/index.php#tentang') ?>">Tentang</a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-success navbar-cta" href="<?= base_url('public_upbs/index.php') ?>">
                        <i class="bi bi-search me-1"></i> Cari Benih
                    </a>
                </li>
            </ul>
        </div>
        -->
    </div>
</nav>

<main class="py-4">
    <div class="container">