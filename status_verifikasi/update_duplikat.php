<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ");
    $stmt->execute([$table]);

    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ");
    $stmt->execute([$table, $column]);

    return (int)$stmt->fetchColumn() > 0;
}

$id = (int)($_POST['id_status_verif'] ?? 0);
$provinsiId = trim($_POST['provinsi_id'] ?? '');
$kabupatenId = trim($_POST['kabupaten_id'] ?? '');
$idSumber = trim($_POST['id_sumber'] ?? '');
$idJenisBantuan = $_POST['id_jenis_bantuan'] ?? [];
$statusVerifikasi = isset($_POST['status_verifikasi']) ? 1 : 0;
$tanggalSubmit = trim($_POST['tanggal_submit'] ?? '');
$volume = trim($_POST['volume'] ?? '');
$volume = str_replace(',', '.', str_replace('.', '', $volume));
$satuan = trim($_POST['satuan'] ?? '');
$keteranganKendala = trim($_POST['keterangan_kendala'] ?? '');
$keteranganUmum = trim($_POST['keterangan_umum'] ?? '');
$redirect = $_POST['redirect'] ?? base_url('status_verifikasi/cek_duplikat.php');
$basePath = base_url();
$satuanOptions = ['Kg', 'Ton', 'Unit', 'Ha', 'Liter', 'Paket', 'Batang', 'Ekor', 'Meter', 'M2', 'Kelompok Masyarakat'];

if (!is_string($redirect) || strpos($redirect, $basePath) !== 0) {
    $redirect = base_url('status_verifikasi/cek_duplikat.php');
}

if ($id <= 0) {
    die('ID data tidak valid.');
}

if ($provinsiId === '' || $kabupatenId === '' || $idSumber === '') {
    die('Data provinsi, kabupaten, dan sumber bantuan wajib diisi.');
}

if (!is_array($idJenisBantuan) || count($idJenisBantuan) === 0) {
    die('Jenis bantuan wajib dipilih minimal 1.');
}

if ($volume === '' || !is_numeric($volume) || (float)$volume <= 0) {
    die('Volume wajib diisi dengan angka lebih dari 0.');
}

if ($satuan === '' || !in_array($satuan, $satuanOptions, true)) {
    die('Satuan wajib dipilih.');
}

if ($statusVerifikasi === 1) {
    if ($tanggalSubmit === '') {
        die('Tanggal submit wajib diisi jika status verifikasi sudah.');
    }
    $keteranganKendala = null;
} else {
    $tanggalSubmit = null;
}

$idJenisValid = [];

foreach ($idJenisBantuan as $idJenis) {
    $idJenis = (int)$idJenis;
    if ($idJenis > 0) {
        $idJenisValid[] = $idJenis;
    }
}

$idJenisValid = array_values(array_unique($idJenisValid));

if (count($idJenisValid) === 0) {
    die('Jenis bantuan tidak valid.');
}

$placeholders = implode(',', array_fill(0, count($idJenisValid), '?'));
$stmtValidasiJenis = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM jenis_bantuan
    WHERE id_sumber = ?
      AND id_jenis_bantuan IN ($placeholders)
");
$stmtValidasiJenis->execute(array_merge([$idSumber], $idJenisValid));

if ((int)$stmtValidasiJenis->fetch()['total'] !== count($idJenisValid)) {
    die('Ada jenis bantuan yang tidak sesuai dengan sumber bantuan yang dipilih.');
}

$stmtCek = $pdo->prepare("
    SELECT id_status_verif
    FROM status_verifikasi
    WHERE id_status_verif = ?
    LIMIT 1
");
$stmtCek->execute([$id]);

if (!$stmtCek->fetch()) {
    die('Data tidak ditemukan.');
}

$hasRelationTable = tableExists($pdo, 'status_verifikasi_jenis_bantuan');
$directJenisColumn = null;

foreach (['id_jenis_bantuan', 'id_kenis_bantuan'] as $candidateColumn) {
    if (columnExists($pdo, 'status_verifikasi', $candidateColumn)) {
        $directJenisColumn = $candidateColumn;
        break;
    }
}

try {
    $pdo->beginTransaction();

    $setDirectJenis = $directJenisColumn !== null ? ", `{$directJenisColumn}` = :id_jenis_bantuan_direct" : '';

    $stmtUpdate = $pdo->prepare("
        UPDATE status_verifikasi
        SET
            provinsi_id = :provinsi_id,
            kabupaten_id = :kabupaten_id,
            id_sumber = :id_sumber,
            status_verifikasi = :status_verifikasi,
            tanggal_submit = :tanggal_submit,
            volume = :volume,
            satuan = :satuan,
            keterangan_kendala = :keterangan_kendala,
            keterangan_umum = :keterangan_umum,
            updated_at = NOW()
            {$setDirectJenis}
        WHERE id_status_verif = :id_status_verif
    ");

    $paramsUpdate = [
        'provinsi_id' => $provinsiId,
        'kabupaten_id' => $kabupatenId,
        'id_sumber' => $idSumber,
        'status_verifikasi' => $statusVerifikasi,
        'tanggal_submit' => $tanggalSubmit,
        'volume' => $volume,
        'satuan' => $satuan,
        'keterangan_kendala' => $keteranganKendala,
        'keterangan_umum' => $keteranganUmum,
        'id_status_verif' => $id,
    ];

    if ($directJenisColumn !== null) {
        $paramsUpdate['id_jenis_bantuan_direct'] = $idJenisValid[0];
    }

    $stmtUpdate->execute($paramsUpdate);

    if ($hasRelationTable) {
        $stmtDeleteRelasi = $pdo->prepare("
            DELETE FROM status_verifikasi_jenis_bantuan
            WHERE id_status_verif = ?
        ");
        $stmtDeleteRelasi->execute([$id]);

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

        foreach ($idJenisValid as $idJenis) {
            $stmtInsertRelasi->execute([
                'id_status_verif' => $id,
                'id_jenis_bantuan' => $idJenis,
            ]);
        }
    }

    $pdo->commit();

    header('Location: ' . $redirect);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Gagal update data duplikat: ' . $e->getMessage());
}
