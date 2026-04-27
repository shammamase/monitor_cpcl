<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id_user    = (int)($userLogin['provinsi_id'] ?? 0);
$id_lama             = (int)($_POST['id_status_verif'] ?? 0);
$kabupaten_id        = trim($_POST['kabupaten_id'] ?? '');
$id_sumber           = trim($_POST['id_sumber'] ?? '');
$id_jenis_bantuan    = $_POST['id_jenis_bantuan'] ?? [];
$status_verifikasi   = isset($_POST['status_verifikasi']) ? 1 : 0;
$tanggal_submit      = trim($_POST['tanggal_submit'] ?? '');
$volume              = trim($_POST['volume'] ?? '');
$satuan              = trim($_POST['satuan'] ?? '');
$keterangan_kendala  = trim($_POST['keterangan_kendala'] ?? '');
$keterangan_umum     = '';
$satuanOptions       = ['Kg', 'Ton', 'Unit', 'Ha', 'Liter', 'Paket', 'Batang', 'Ekor', 'Meter', 'M2'];

if ($provinsi_id_user <= 0) {
    die('Provinsi user tidak valid.');
}

if ($id_lama <= 0) {
    die('ID data lama tidak valid.');
}

if ($kabupaten_id === '' || $id_sumber === '') {
    die('Data kabupaten dan sumber bantuan wajib diisi.');
}

if (!is_array($id_jenis_bantuan) || count($id_jenis_bantuan) === 0) {
    die('Jenis bantuan wajib dipilih minimal 1.');
}

if ($volume === '' || !is_numeric($volume) || (float)$volume <= 0) {
    die('Volume wajib diisi dengan angka lebih dari 0.');
}

if ($satuan === '' || !in_array($satuan, $satuanOptions, true)) {
    die('Satuan wajib dipilih.');
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
| Pastikan data lama masih aktif dan milik provinsi user
|--------------------------------------------------------------------------
*/
$stmtCekLama = $pdo->prepare("
    SELECT *
    FROM status_verifikasi
    WHERE id_status_verif = ?
      AND is_active = 1
      AND provinsi_id = ?
    LIMIT 1
");
$stmtCekLama->execute([$id_lama, $provinsi_id_user]);
$dataLama = $stmtCekLama->fetch();

if (!$dataLama) {
    die('Data lama tidak ditemukan atau bukan milik provinsi Anda.');
}

/*
|--------------------------------------------------------------------------
| Guard: jika root sudah final, tidak boleh update lagi
|--------------------------------------------------------------------------
*/
$stmtGuard = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM status_verifikasi
    WHERE root_id = ?
      AND status_verifikasi = 1
");
$stmtGuard->execute([(int)$dataLama['root_id']]);
$guard = $stmtGuard->fetch();

if ((int)$guard['total'] > 0) {
    die('Data ini sudah final dan tidak dapat diperbarui lagi.');
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
$stmtKab->execute([$kabupaten_id, $provinsi_id_user]);
$cekKab = $stmtKab->fetch();

if ((int)$cekKab['total'] === 0) {
    die('Kabupaten tidak sesuai dengan provinsi akun Anda.');
}

$root_id_baru = !empty($dataLama['root_id'])
    ? (int)$dataLama['root_id']
    : (int)$id_lama;

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
    | Nonaktifkan data lama
    |--------------------------------------------------------------------------
    
    $stmtNonaktifkan = $pdo->prepare("
        UPDATE status_verifikasi
        SET is_active = 0,
            updated_at = NOW()
        WHERE id_status_verif = ?
          AND is_active = 1
    ");
    $stmtNonaktifkan->execute([$id_lama]);

    */

    /*
    |--------------------------------------------------------------------------
    | Insert data baru
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
            volume,
            satuan,
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
            :volume,
            :satuan,
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
        'provinsi_id'        => $provinsi_id_user,
        'kabupaten_id'       => $kabupaten_id,
        'id_sumber'          => $id_sumber,
        'status_verifikasi'  => $status_verifikasi,
        'tanggal_submit'     => $tanggal_submit,
        'volume'             => $volume,
        'satuan'             => $satuan,
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
    | Simpan relasi jenis bantuan ke record baru
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

    header('Location: ' . base_url('page_cpcl/dashboard_provinsi.php'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Gagal menyimpan perubahan data: ' . $e->getMessage());
}
