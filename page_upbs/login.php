<?php
session_start();
require_once __DIR__ . '/../helpers/functions.php';

if (isset($_SESSION['user_upbs'])) {
    header('Location: ' . base_url('page_upbs/dashboard.php'));
    exit;
}

$error = $_SESSION['login_error_upbs'] ?? '';
unset($_SESSION['login_error_upbs']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login UPBS</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        :root {
            --upbs-primary: #198754;
            --upbs-primary-dark: #157347;
            --upbs-bg-soft: #f4f8f5;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(25, 135, 84, 0.18), transparent 30%),
                radial-gradient(circle at bottom right, rgba(13, 110, 253, 0.10), transparent 25%),
                linear-gradient(135deg, #f7faf8, #eef6f0);
            font-family: Arial, Helvetica, sans-serif;
        }

        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 980px;
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.12);
            background: #fff;
        }

        .login-left {
            background: linear-gradient(135deg, var(--upbs-primary), var(--upbs-primary-dark));
            color: #fff;
            padding: 48px 36px;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-left .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .login-left .brand img {
            height: 58px;
            width: auto;
        }

        .login-left h1 {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 14px;
        }

        .login-left p {
            font-size: 0.98rem;
            opacity: 0.95;
            margin-bottom: 0;
        }

        .feature-list {
            margin-top: 28px;
            padding-left: 0;
            list-style: none;
        }

        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.14);
        }

        .feature-list li:last-child {
            border-bottom: 0;
        }

        .login-right {
            padding: 42px 34px;
            background: #fff;
            display: flex;
            align-items: center;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .login-form-wrap h3 {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-form-wrap .subtitle {
            color: #6c757d;
            margin-bottom: 26px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            height: 46px;
            border-radius: 12px;
        }

        .btn-login {
            height: 46px;
            border-radius: 12px;
            font-weight: 600;
            background: var(--upbs-primary);
            border-color: var(--upbs-primary);
        }

        .btn-login:hover {
            background: var(--upbs-primary-dark);
            border-color: var(--upbs-primary-dark);
        }

        .login-footer-note {
            margin-top: 18px;
            color: #6c757d;
            font-size: 0.92rem;
            text-align: center;
        }

        .info-badge {
            display: inline-block;
            background: rgba(255,255,255,0.14);
            color: #fff;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.88rem;
            margin-bottom: 16px;
        }

        @media (max-width: 991.98px) {
            .login-left {
                padding: 34px 26px;
            }

            .login-right {
                padding: 34px 24px;
            }

            .login-left h1 {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 767.98px) {
            .login-card {
                border-radius: 20px;
            }

            .login-left,
            .login-right {
                padding: 28px 20px;
            }

            .login-left .brand img {
                height: 50px;
            }

            .login-form-wrap {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid login-shell">
    <div class="card login-card">
        <div class="row g-0">
            <div class="col-lg-6">
                <div class="login-left h-100">
                    <span class="info-badge">Portal Login UPBS</span>

                    <div class="brand">
                        <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo">
                        <div>
                            <div class="fw-bold fs-5">Sistem UPBS</div>
                            <div class="small opacity-75">Unit Pengelola Benih Sumber</div>
                        </div>
                    </div>

                    <h1>Selamat Datang di Portal UPBS BRMP</h1>
                    <p>
                        Gunakan akun Anda untuk mengakses pengelolaan data stok, laporan UPBS,
                        dan informasi pendukung lainnya secara terpusat.
                    </p>

                    <ul class="feature-list">
                        <li>Input dan pembaruan data stok UPBS</li>
                        <li>Monitoring ketersediaan benih sumber</li>
                        <li>Akses cepat ke data per satker dan wilayah</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="login-right h-100">
                    <div class="login-form-wrap">
                        <h3>Masuk ke Sistem</h3>
                        <div class="subtitle">Silakan login menggunakan akun UPBS Anda.</div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form action="<?= base_url('page_upbs/proses_login.php') ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-login">Login</button>
                            </div>
                        </form>

                        <div class="login-footer-note">
                            Pastikan username dan password sesuai dengan akun yang telah diberikan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>