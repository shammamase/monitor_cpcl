<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

$provinsi_id_user    = (int)($userLogin['provinsi_id'] ?? 0);
$id_status_verif     = (int)($_POST['id_status_verif'] ?? 0);
$kabupaten_id        = trim($_POST['kabupaten_id'] ?? '');
$id_sumber           = trim($_POST['id_sumber'] ?? '');
$id_jenis_bantuan    = $_POST['id_jenis_bantuan'] ?? [];
$status_verifikasi   = isset($_POST['status_verifikasi']) ? 1 : 0;
$tanggal_submit      = trim($_POST['tanggal_submit'] ?? '');
$volume              = trim($_POST['volume'] ?? '');
$volume              = str_replace(',', '.', str_replace('.', '', $volume));
$satuan              = trim($_POST['satuan'] ?? '');
$keterangan_kendala  = trim($_POST['keterangan_kendala'] ?? '');
$satuanOptions       = ['Kg', 'Ton', 'Unit', 'Ha', 'Liter', 'Paket', 'Batang', 'Ekor', 'Meter', 'M2', 'Kelompok Masyarakat'];

if ($provinsi_id_user <= 0) {
    die('Provinsi user tidak valid.');
}

if ($id_status_verif <= 0) {
    die('ID data tidak valid.');
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

$stmtCek = $pdo->prepare("
    SELECT *
    FROM status_verifikasi
    WHERE id_status_verif = ?
      AND is_active = 1
      AND provinsi_id = ?
      AND status_verifikasi = 1
    LIMIT 1
");
$stmtCek->execute([$id_status_verif, $provinsi_id_user]);
$data = $stmtCek->fetch();

if (!$data) {
    die('Data sudah verifikasi tidak ditemukan atau tidak termasuk provinsi Anda.');
}

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

    $stmtUpdate = $pdo->prepare("
        UPDATE status_verifikasi
        SET kabupaten_id = :kabupaten_id,
            id_sumber = :id_sumber,
            status_verifikasi = :status_verifikasi,
            tanggal_submit = :tanggal_submit,
            volume = :volume,
            satuan = :satuan,
            keterangan_kendala = :keterangan_kendala,
            updated_at = NOW()
        WHERE id_status_verif = :id_status_verif
          AND is_active = 1
          AND provinsi_id = :provinsi_id
    ");

    $stmtUpdate->execute([
        'kabupaten_id'        => $kabupaten_id,
        'id_sumber'           => $id_sumber,
        'status_verifikasi'   => $status_verifikasi,
        'tanggal_submit'      => $tanggal_submit,
        'volume'              => $volume,
        'satuan'              => $satuan,
        'keterangan_kendala'  => $keterangan_kendala,
        'id_status_verif'     => $id_status_verif,
        'provinsi_id'         => $provinsi_id_user,
    ]);

    $stmtDeleteRelasi = $pdo->prepare("
        DELETE FROM status_verifikasi_jenis_bantuan
        WHERE id_status_verif = ?
    ");
    $stmtDeleteRelasi->execute([$id_status_verif]);

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

    die('Gagal menyimpan perubahan data: ' . $e->getMessage());
}
