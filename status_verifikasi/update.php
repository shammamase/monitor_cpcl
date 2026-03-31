<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$id_lama              = $_POST['id_status_verif'] ?? 0;
//$id_poktan            = $_POST['id_poktan'] ?? '';
$provinsi_id         = trim($_POST['provinsi_id'] ?? '');
$kabupaten_id        = trim($_POST['kabupaten_id'] ?? '');
$id_sumber            = $_POST['id_sumber'] ?? '';
$id_jenis_bantuan    = $_POST['id_jenis_bantuan'] ?? [];
$status_verifikasi    = isset($_POST['status_verifikasi']) ? 1 : 0;
$tanggal_submit      = trim($_POST['tanggal_submit'] ?? '');
$keterangan_kendala   = trim($_POST['keterangan_kendala'] ?? '');
$keterangan_umum      = trim($_POST['keterangan_umum'] ?? '');

if ($id_lama <= 0) {
    die('ID data lama tidak valid.');
}

/*
if ($id_poktan === '' || $id_sumber === '') {
    die('Data poktan dan sumber bantuan wajib diisi.');
}
*/

if ($provinsi_id === '' || $kabupaten_id === '' || $id_sumber === '') {
    die('Data provinsi, kabupaten, dan sumber bantuan wajib diisi.');
}

if (!is_array($id_jenis_bantuan) || count($id_jenis_bantuan) === 0) {
    die('Jenis bantuan wajib dipilih minimal 1.');
}

/*
|--------------------------------------------------------------------------
| Validasi status
|--------------------------------------------------------------------------
*/
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
| Pastikan data lama masih aktif
|--------------------------------------------------------------------------
*/
$stmtCekLama = $pdo->prepare("
    SELECT *
    FROM status_verifikasi
    WHERE id_status_verif = ?
      AND is_active = 1
    LIMIT 1
");
$stmtCekLama->execute([$id_lama]);
$dataLama = $stmtCekLama->fetch();

if (!$dataLama) {
    die('Data lama tidak ditemukan atau sudah tidak aktif.');
}

$root_id_baru = !empty($dataLama['root_id'])
    ? (int)$dataLama['root_id']
    : (int)$id_lama;

/*
|--------------------------------------------------------------------------
| Validasi jenis bantuan harus sesuai dengan sumber bantuan yang dipilih
|--------------------------------------------------------------------------
*/
$idJenisValid = [];

foreach ($id_jenis_bantuan as $idjb) {
    $idjb = (int)$idjb;
    if ($idjb > 0) {
        $idJenisValid[] = $idjb;
    }
}

$idJenisValid = array_values(array_unique($idJenisValid));

if (count($idJenisValid) === 0) {
    die('Jenis bantuan tidak valid.');
}

$placeholders = implode(',', array_fill(0, count($idJenisValid), '?'));
$sqlValidasiJenis = "
    SELECT COUNT(*) AS total
    FROM jenis_bantuan
    WHERE id_sumber = ?
      AND id_jenis_bantuan IN ($placeholders)
";

$paramsValidasi = array_merge([$id_sumber], $idJenisValid);

$stmtValidasiJenis = $pdo->prepare($sqlValidasiJenis);
$stmtValidasiJenis->execute($paramsValidasi);
$jumlahJenisSesuai = (int)$stmtValidasiJenis->fetch()['total'];

if ($jumlahJenisSesuai !== count($idJenisValid)) {
    die('Ada jenis bantuan yang tidak sesuai dengan sumber bantuan yang dipilih.');
}

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Insert data baru sebagai versi terbaru
    |--------------------------------------------------------------------------
    */
    $stmtInsertBaru = $pdo->prepare("
        INSERT INTO status_verifikasi
        (
            provinsi_id,
            kabupaten_id,
            id_sumber,
            status_verifikasi,
            tanggal_submit,
            is_active,
            copied_from_id,
            root_id,
            keterangan_kendala,
            keterangan_umum,
            created_at,
            updated_at
        )
        VALUES
        (
            :provinsi_id,
            :kabupaten_id,
            :id_sumber,
            :status_verifikasi,
            :tanggal_submit,
            1,
            :copied_from_id,
            :root_id,
            :keterangan_kendala,
            :keterangan_umum,
            NOW(),
            NOW()
        )
    ");

    $stmtInsertBaru->execute([
        'provinsi_id'        => $provinsi_id,
        'kabupaten_id'       => $kabupaten_id,
        'id_sumber'          => $id_sumber,
        'status_verifikasi'  => $status_verifikasi,
        'tanggal_submit'     => $tanggal_submit,
        'copied_from_id'     => $id_lama,
        'root_id'            => $root_id_baru,
        'keterangan_kendala' => $keterangan_kendala,
        'keterangan_umum'    => $keterangan_umum,
    ]);

    $id_baru = (int)$pdo->lastInsertId();

    if ($id_baru <= 0) {
        throw new Exception('Gagal mendapatkan ID data baru.');
    }

    /*
    |--------------------------------------------------------------------------
    | Insert ulang relasi jenis bantuan ke record baru
    |--------------------------------------------------------------------------
    */
    $stmtInsertRelasi = $pdo->prepare("
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

    foreach ($idJenisValid as $idjb) {
        $stmtInsertRelasi->execute([
            'id_status_verif'  => $id_baru,
            'id_jenis_bantuan' => $idjb,
        ]);
    }

    $pdo->commit();

    header('Location: ' . base_url('?page=status_verifikasi'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Gagal menyimpan perubahan data: ' . $e->getMessage());
}