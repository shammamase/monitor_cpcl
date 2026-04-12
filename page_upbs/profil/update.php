<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id_user_session = (int)($userUpbs['id_user'] ?? 0);
$id_user_form    = (int)($_POST['id_user'] ?? 0);

$nama_lengkap             = trim($_POST['nama_lengkap'] ?? '');
$username                 = trim($_POST['username'] ?? '');
$provinsi_id              = trim($_POST['provinsi_id'] ?? '');
$kabupaten_id             = trim($_POST['kabupaten_id'] ?? '');
$password_baru            = trim($_POST['password_baru'] ?? '');
$konfirmasi_password_baru = trim($_POST['konfirmasi_password_baru'] ?? '');

if ($id_user_session <= 0 || $id_user_form <= 0 || $id_user_session !== $id_user_form) {
    $_SESSION['error_message_upbs'] = 'Permintaan tidak valid.';
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

if ($nama_lengkap === '' || $username === '' || $provinsi_id === '' || $kabupaten_id === '') {
    $_SESSION['error_message_upbs'] = 'Nama lengkap, username, provinsi, dan kabupaten wajib diisi.';
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

$stmtUser = $pdo->prepare("
    SELECT
        u.id_user,
        u.username,
        u.password,
        u.id_satker
    FROM users u
    WHERE u.id_user = ?
    LIMIT 1
");
$stmtUser->execute([$id_user_session]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error_message_upbs'] = 'Data user tidak ditemukan.';
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

if (empty($user['id_satker'])) {
    $_SESSION['error_message_upbs'] = 'Akun ini belum terhubung ke satker.';
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Validasi kabupaten sesuai provinsi
|--------------------------------------------------------------------------
*/
$stmtKab = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM kabupatens
    WHERE id = ?
      AND provinsi_id = ?
");
$stmtKab->execute([$kabupaten_id, $provinsi_id]);
$validKab = (int)$stmtKab->fetch(PDO::FETCH_ASSOC)['total'];

if ($validKab === 0) {
    $_SESSION['error_message_upbs'] = 'Kabupaten tidak sesuai dengan provinsi yang dipilih.';
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Validasi username unik
|--------------------------------------------------------------------------
*/
$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM users
    WHERE username = ?
      AND id_user != ?
");
$stmtCheck->execute([$username, $id_user_session]);
$exists = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

if ($exists > 0) {
    $_SESSION['error_message_upbs'] = 'Username sudah digunakan.';
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Validasi password baru jika diisi
|--------------------------------------------------------------------------
*/
$ubahPassword = false;
$password_hash = null;

if ($password_baru !== '' || $konfirmasi_password_baru !== '') {
    if ($password_baru !== $konfirmasi_password_baru) {
        $_SESSION['error_message_upbs'] = 'Konfirmasi password baru tidak sama.';
        header('Location: ' . base_url('page_upbs/profil/index.php'));
        exit;
    }

    if (strlen($password_baru) < 8) {
        $_SESSION['error_message_upbs'] = 'Password baru minimal 8 karakter.';
        header('Location: ' . base_url('page_upbs/profil/index.php'));
        exit;
    }

    if (password_verify($password_baru, $user['password'])) {
        $_SESSION['error_message_upbs'] = 'Password baru tidak boleh sama dengan password lama.';
        header('Location: ' . base_url('page_upbs/profil/index.php'));
        exit;
    }

    $ubahPassword = true;
    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
}

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Update satker
    |--------------------------------------------------------------------------
    */
    $stmtSatker = $pdo->prepare("
        UPDATE satker
        SET
            provinsi_id = :provinsi_id,
            kabupaten_id = :kabupaten_id,
            updated_at = NOW()
        WHERE id_satker = :id_satker
    ");
    $stmtSatker->execute([
        'provinsi_id'  => $provinsi_id,
        'kabupaten_id' => $kabupaten_id,
        'id_satker'    => $user['id_satker'],
    ]);

    /*
    |--------------------------------------------------------------------------
    | Update user
    |--------------------------------------------------------------------------
    */
    if ($ubahPassword) {
        $stmtUpdateUser = $pdo->prepare("
            UPDATE users
            SET
                nama_lengkap = :nama_lengkap,
                username = :username,
                provinsi_id = :provinsi_id,
                kabupaten_id = :kabupaten_id,
                password = :password,
                updated_at = NOW()
            WHERE id_user = :id_user
        ");

        $stmtUpdateUser->execute([
            'nama_lengkap' => $nama_lengkap,
            'username'     => $username,
            'provinsi_id'  => $provinsi_id,
            'kabupaten_id' => $kabupaten_id,
            'password'     => $password_hash,
            'id_user'      => $id_user_session,
        ]);
    } else {
        $stmtUpdateUser = $pdo->prepare("
            UPDATE users
            SET
                nama_lengkap = :nama_lengkap,
                username = :username,
                provinsi_id = :provinsi_id,
                kabupaten_id = :kabupaten_id,
                updated_at = NOW()
            WHERE id_user = :id_user
        ");

        $stmtUpdateUser->execute([
            'nama_lengkap' => $nama_lengkap,
            'username'     => $username,
            'provinsi_id'  => $provinsi_id,
            'kabupaten_id' => $kabupaten_id,
            'id_user'      => $id_user_session,
        ]);
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_message_upbs'] = 'Gagal memperbarui profil: ' . $e->getMessage();
    header('Location: ' . base_url('page_upbs/profil/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Refresh session user_upbs
|--------------------------------------------------------------------------
*/
$stmtRefresh = $pdo->prepare("
    SELECT
        u.id_user,
        u.nama_lengkap,
        u.username,
        u.provinsi_id,
        u.kabupaten_id,
        u.id_satker,
        p.name AS nama_provinsi,
        k.name AS nama_kabupaten,
        s.nama_satker,
        s.jenis_satker
    FROM users u
    LEFT JOIN provinsis p ON u.provinsi_id = p.id
    LEFT JOIN kabupatens k ON u.kabupaten_id = k.id
    LEFT JOIN satker s ON u.id_satker = s.id_satker
    WHERE u.id_user = ?
    LIMIT 1
");
$stmtRefresh->execute([$id_user_session]);
$userRefresh = $stmtRefresh->fetch(PDO::FETCH_ASSOC);

if ($userRefresh) {
    $_SESSION['user_upbs'] = [
        'id_user'        => $userRefresh['id_user'],
        'nama_lengkap'   => $userRefresh['nama_lengkap'],
        'username'       => $userRefresh['username'],
        'provinsi_id'    => $userRefresh['provinsi_id'],
        'kabupaten_id'   => $userRefresh['kabupaten_id'] ?? null,
        'id_satker'      => $userRefresh['id_satker'],
        'nama_provinsi'  => $userRefresh['nama_provinsi'] ?? '',
        'nama_kabupaten' => $userRefresh['nama_kabupaten'] ?? '',
        'nama_satker'    => $userRefresh['nama_satker'] ?? '',
        'jenis_satker'   => $userRefresh['jenis_satker'] ?? '',
    ];
}

$_SESSION['success_message_upbs'] = 'Profil berhasil diperbarui.';
header('Location: ' . base_url('page_upbs/profil/index.php'));
exit;