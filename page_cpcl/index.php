<?php
session_start();
require_once __DIR__ . '/../helpers/functions.php';

if (isset($_SESSION['user_login'])) {
    header('Location: ' . base_url('page_cpcl/dashboard_provinsi.php'));
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Monitoring CPCL BRMP</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.ico') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #198754, #157347);
            min-height: 100vh;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            border: 0;
            border-radius: 20px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        }

        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .login-logo {
            height: 64px;
            width: auto;
            margin-bottom: 12px;
        }

        .login-title {
            font-weight: 700;
            margin-bottom: 6px;
        }

        .login-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="login-header">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" class="login-logo">
                <h3 class="login-title">Login Monitoring CPCL BRMP</h3>
                <p class="login-subtitle">Silakan masuk menggunakan akun provinsi</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('page_cpcl/proses_login.php') ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Masuk</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>