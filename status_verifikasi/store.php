<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_poktan          = $_POST['id_poktan'] ?? '';
$id_sumber          = $_POST['id_sumber'] ?? '';
$id_jenis_bantuan   = $_POST['id_jenis_bantuan'] ?? [];
$status_verifikasi  = isset($_POST['status_verifikasi']) ? 1 : 0;
$tanggal_submit     = trim($_POST['tanggal_submit'] ?? '');
$keterangan_kendala = trim($_POST['keterangan_kendala'] ?? '');
$keterangan_umum    = trim($_POST['keterangan_umum'] ?? '');

/*
|--------------------------------------------------------------------------
| Validasi dasar
|--------------------------------------------------------------------------
*/
if ($id_poktan === '' || $id_sumber === '') {
    die('Data wajib belum lengkap.');
}

if (!is_array($id_jenis_bantuan) || count($id_jenis_bantuan) === 0) {
    die('Jenis bantuan wajib dipilih minimal 1.');
}

if ($status_verifikasi === 1) {
    if ($tanggal_submit === '') {
        die('Tanggal submit wajib diisi jika status verifikasi sudah.');
    }
    $keterangan_kendala = null;
} else {
    $tanggal_submit = null;
}

/*
|--------------------------------------------------------------------------
| Cek duplikasi status_verifikasi untuk poktan + sumber
|--------------------------------------------------------------------------
| Sesuaikan dengan struktur yang Anda pakai sebelumnya.
|--------------------------------------------------------------------------
*/
$cek = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM status_verifikasi
    WHERE id_poktan = ? AND id_sumber = ?
");
$cek->execute([$id_poktan, $id_sumber]);
$exists = $cek->fetch();

if ((int)$exists['total'] > 0) {
    die('Data status verifikasi untuk poktan dan sumber bantuan tersebut sudah ada.');
}

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Simpan ke tabel status_verifikasi
    |--------------------------------------------------------------------------
    */
    $sql = "INSERT INTO status_verifikasi
            (
                id_poktan,
                id_sumber,
                status_verifikasi,
                tanggal_submit,
                keterangan_kendala,
                keterangan_umum,
                created_at,
                updated_at
            )
            VALUES
            (
                :id_poktan,
                :id_sumber,
                :status_verifikasi,
                :tanggal_submit,
                :keterangan_kendala,
                :keterangan_umum,
                NOW(),
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_poktan'           => $id_poktan,
        'id_sumber'           => $id_sumber,
        'status_verifikasi'   => $status_verifikasi,
        'tanggal_submit'      => $tanggal_submit,
        'keterangan_kendala'  => $keterangan_kendala,
        'keterangan_umum'     => $keterangan_umum,
    ]);

    $id_status_verif = $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Simpan multi jenis bantuan ke tabel relasi
    |--------------------------------------------------------------------------
    */
    $stmtRelasi = $pdo->prepare("
        INSERT INTO status_verifikasi_jenis_bantuan
        (
            id_status_verif,
            id_jenis_bantuan,
            created_at,
            updated_at
        )
        VALUES
        (
            :id_status_verif,
            :id_jenis_bantuan,
            NOW(),
            NOW()
        )
    ");

    foreach ($id_jenis_bantuan as $idjb) {
        $idjb = (int)$idjb;
        if ($idjb <= 0) {
            continue;
        }

        $stmtRelasi->execute([
            'id_status_verif'   => $id_status_verif,
            'id_jenis_bantuan'  => $idjb,
        ]);
    }

    $pdo->commit();

    header('Location: ' . base_url('?page=status_verifikasi'));
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    die('Gagal menyimpan data: ' . $e->getMessage());
}