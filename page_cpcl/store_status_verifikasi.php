<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id        = (int)($userLogin['provinsi_id'] ?? 0);
$kabupaten_id       = trim($_POST['kabupaten_id'] ?? '');
$id_sumber          = trim($_POST['id_sumber'] ?? '');
$id_jenis_bantuan   = $_POST['id_jenis_bantuan'] ?? [];
$status_verifikasi  = isset($_POST['status_verifikasi']) ? 1 : 0;
$tanggal_submit     = trim($_POST['tanggal_submit'] ?? '');
$keterangan_kendala = trim($_POST['keterangan_kendala'] ?? '');
$keterangan_umum    = '';

if ($provinsi_id <= 0) {
    die('Provinsi user tidak valid.');
}

if ($kabupaten_id === '' || $id_sumber === '') {
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
| Validasi kabupaten harus milik provinsi user
|--------------------------------------------------------------------------
*/
$stmtKab = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM kabupatens
    WHERE id = ?
      AND provinsi_id = ?
");
$stmtKab->execute([$kabupaten_id, $provinsi_id]);
$cekKab = $stmtKab->fetch();

if ((int)$cekKab['total'] === 0) {
    die('Kabupaten tidak sesuai dengan provinsi akun Anda.');
}

/*
|--------------------------------------------------------------------------
| Cek duplikasi data aktif wilayah + sumber
|--------------------------------------------------------------------------

$cek = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM status_verifikasi
    WHERE provinsi_id = ? AND kabupaten_id = ? AND id_sumber = ? AND is_active = 1
");
$cek->execute([$provinsi_id, $kabupaten_id, $id_sumber]);
$exists = $cek->fetch();

if ((int)$exists['total'] > 0) {
    die('Data aktif untuk kabupaten dan sumber bantuan tersebut sudah ada.');
}
*/

/*
|--------------------------------------------------------------------------
| Validasi jenis bantuan sesuai sumber bantuan
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
    | Simpan data utama
    |--------------------------------------------------------------------------
    */
    $sql = "INSERT INTO status_verifikasi
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
                NULL,
                NULL,
                :keterangan_kendala,
                :keterangan_umum,
                NOW(),
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'provinsi_id'        => $provinsi_id,
        'kabupaten_id'       => $kabupaten_id,
        'id_sumber'          => $id_sumber,
        'status_verifikasi'  => $status_verifikasi,
        'tanggal_submit'     => $tanggal_submit,
        'keterangan_kendala' => $keterangan_kendala,
        'keterangan_umum'    => $keterangan_umum,
    ]);

    $id_status_verif = (int)$pdo->lastInsertId();

    if ($id_status_verif <= 0) {
        throw new Exception('Gagal mendapatkan ID status verifikasi baru.');
    }

    /*
    |--------------------------------------------------------------------------
    | Set root_id = id sendiri untuk record pertama
    |--------------------------------------------------------------------------
    */
    $stmtSetRoot = $pdo->prepare("
        UPDATE status_verifikasi
        SET root_id = ?
        WHERE id_status_verif = ?
    ");
    $stmtSetRoot->execute([$id_status_verif, $id_status_verif]);

    /*
    |--------------------------------------------------------------------------
    | Simpan relasi jenis bantuan
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

    foreach ($idJenisValid as $idjb) {
        $stmtRelasi->execute([
            'id_status_verif'  => $id_status_verif,
            'id_jenis_bantuan' => $idjb,
        ]);
    }

    $pdo->commit();

    header('Location: ' . base_url('page_cpcl/dashboard_provinsi.php'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Gagal menyimpan data: ' . $e->getMessage());
}