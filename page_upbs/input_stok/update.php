<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/functions.php';

function redirectEditWithError(int $id_laporan_stok, string $message): void
{
    $_SESSION['error_message_upbs'] = $message;
    $_SESSION['old_input_edit_stok'] = $_POST;
    header('Location: ' . base_url('page_upbs/input_stok/edit.php?id=' . $id_laporan_stok));
    exit;
}

function parseAngkaIndonesia(?string $value): ?float
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);

    if (!is_numeric($value)) {
        return null;
    }

    return (float)$value;
}

$id_satker          = (int)($userUpbs['id_satker'] ?? 0);
$id_laporan_stok    = (int)($_POST['id_laporan_stok'] ?? 0);
$id_upbs            = trim($_POST['id_upbs'] ?? '');
$tanggal_laporan    = trim($_POST['tanggal_laporan'] ?? '');
$periode_text       = trim($_POST['periode_text'] ?? '');
$nama_petugas_input = trim($_POST['nama_petugas_input'] ?? '');
$keterangan         = trim($_POST['keterangan'] ?? '');
$detailRows         = $_POST['detail'] ?? [];

if ($id_satker <= 0 || $id_laporan_stok <= 0) {
    $_SESSION['error_message_upbs'] = 'Permintaan update tidak valid.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}

if ($id_upbs === '' || $tanggal_laporan === '') {
    redirectEditWithError($id_laporan_stok, 'UPBS dan tanggal laporan wajib diisi.');
}

if (!is_array($detailRows) || empty($detailRows)) {
    redirectEditWithError($id_laporan_stok, 'Minimal harus ada 1 baris detail stok.');
}

/*
|--------------------------------------------------------------------------
| Pastikan laporan milik satker user
|--------------------------------------------------------------------------
*/
$stmtHeader = $pdo->prepare("
    SELECT
        l.id_laporan_stok
    FROM laporan_stok_upbs l
    INNER JOIN upbs u ON l.id_upbs = u.id_upbs
    INNER JOIN satker s ON u.id_satker = s.id_satker
    WHERE l.id_laporan_stok = ?
      AND s.id_satker = ?
    LIMIT 1
");
$stmtHeader->execute([$id_laporan_stok, $id_satker]);
$currentHeader = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if (!$currentHeader) {
    $_SESSION['error_message_upbs'] = 'Laporan stok tidak ditemukan atau bukan milik satker Anda.';
    header('Location: ' . base_url('page_upbs/input_stok/index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Validasi UPBS milik satker user
|--------------------------------------------------------------------------
*/
$stmtUpbs = $pdo->prepare("
    SELECT id_upbs
    FROM upbs
    WHERE id_upbs = ?
      AND id_satker = ?
      AND is_active = 1
    LIMIT 1
");
$stmtUpbs->execute([$id_upbs, $id_satker]);
$upbs = $stmtUpbs->fetch(PDO::FETCH_ASSOC);

if (!$upbs) {
    redirectEditWithError($id_laporan_stok, 'UPBS tidak valid atau bukan milik satker Anda.');
}

/*
|--------------------------------------------------------------------------
| Prepared statements validasi master
|--------------------------------------------------------------------------
*/
$stmtKomoditas = $pdo->prepare("
    SELECT id_komoditas, kategori_komoditas
    FROM komoditas
    WHERE id_komoditas = ?
      AND is_active = 1
    LIMIT 1
");

$stmtVarietas = $pdo->prepare("
    SELECT id_varietas, id_komoditas
    FROM varietas
    WHERE id_varietas = ?
      AND is_active = 1
    LIMIT 1
");

$stmtKelasBenih = $pdo->prepare("
    SELECT id_kelas_benih
    FROM kelas_benih
    WHERE id_kelas_benih = ?
      AND is_active = 1
    LIMIT 1
");

$stmtBenihSumber = $pdo->prepare("
    SELECT id_benih_sumber, kategori_komoditas
    FROM benih_sumber
    WHERE id_benih_sumber = ?
      AND is_active = 1
    LIMIT 1
");

$stmtSatuan = $pdo->prepare("
    SELECT id_satuan
    FROM satuan
    WHERE id_satuan = ?
    LIMIT 1
");

/*
|--------------------------------------------------------------------------
| Normalisasi & validasi detail
|--------------------------------------------------------------------------
*/
$normalizedDetails = [];
$detailRows = array_values($detailRows);

foreach ($detailRows as $index => $row) {
    $barisKe = $index + 1;

    $id_komoditas    = trim($row['id_komoditas'] ?? '');
    $id_varietas     = trim($row['id_varietas'] ?? '');
    $id_kelas_benih  = trim($row['id_kelas_benih'] ?? '');
    $id_benih_sumber = trim($row['id_benih_sumber'] ?? '');
    $stok_raw        = trim($row['stok_tersedia'] ?? '');
    $id_satuan_stok  = trim($row['id_satuan_stok'] ?? '');
    $harga_raw       = trim($row['harga'] ?? '');
    $harga_keterangan = trim($row['harga_keterangan'] ?? '');

    $isEmptyRow =
        $id_komoditas === '' &&
        $id_varietas === '' &&
        $id_kelas_benih === '' &&
        $id_benih_sumber === '' &&
        $stok_raw === '' &&
        $id_satuan_stok === '' &&
        $harga_raw === '' &&
        $harga_keterangan === '';

    if ($isEmptyRow) {
        continue;
    }

    if ($id_komoditas === '') {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: komoditas wajib dipilih.");
    }

    $stmtKomoditas->execute([$id_komoditas]);
    $komoditas = $stmtKomoditas->fetch(PDO::FETCH_ASSOC);

    if (!$komoditas) {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: komoditas tidak valid.");
    }

    $kategoriKomoditas = $komoditas['kategori_komoditas'];

    if ($id_varietas === '') {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: varietas/galur wajib dipilih.");
    }

    $stmtVarietas->execute([$id_varietas]);
    $varietas = $stmtVarietas->fetch(PDO::FETCH_ASSOC);

    if (!$varietas || (int)$varietas['id_komoditas'] !== (int)$id_komoditas) {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: varietas/galur tidak sesuai dengan komoditas.");
    }

    if ($kategoriKomoditas === 'ternak') {
        $id_kelas_benih = null;
    } else {
        if ($id_kelas_benih !== '') {
            $stmtKelasBenih->execute([$id_kelas_benih]);
            $kelasBenih = $stmtKelasBenih->fetch(PDO::FETCH_ASSOC);

            if (!$kelasBenih) {
                redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: kelas benih tidak valid.");
            }
        } else {
            $id_kelas_benih = null;
        }
    }

    if ($id_benih_sumber !== '') {
        $stmtBenihSumber->execute([$id_benih_sumber]);
        $benihSumber = $stmtBenihSumber->fetch(PDO::FETCH_ASSOC);

        if (!$benihSumber) {
            redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: benih sumber tidak valid.");
        }

        if (!empty($benihSumber['kategori_komoditas']) && $benihSumber['kategori_komoditas'] !== $kategoriKomoditas) {
            redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: benih sumber tidak sesuai dengan kategori komoditas.");
        }
    } else {
        $id_benih_sumber = null;
    }

    $stokValue = parseAngkaIndonesia($stok_raw);
    if ($stokValue === null || $stokValue <= 0) {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: stok tersedia wajib diisi dan harus lebih dari 0.");
    }

    if ($id_satuan_stok === '') {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: satuan wajib dipilih.");
    }

    $stmtSatuan->execute([$id_satuan_stok]);
    $satuan = $stmtSatuan->fetch(PDO::FETCH_ASSOC);

    if (!$satuan) {
        redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: satuan tidak valid.");
    }

    $hargaValue = null;
    if ($harga_raw !== '') {
        $hargaValue = parseAngkaIndonesia($harga_raw);
        if ($hargaValue === null || $hargaValue < 0) {
            redirectEditWithError($id_laporan_stok, "Baris ke-{$barisKe}: harga harus berupa angka yang valid.");
        }
    }

    $normalizedDetails[] = [
        'id_komoditas'     => (int)$id_komoditas,
        'id_varietas'      => (int)$id_varietas,
        'id_kelas_benih'   => $id_kelas_benih !== null ? (int)$id_kelas_benih : null,
        'id_benih_sumber'  => $id_benih_sumber !== null ? (int)$id_benih_sumber : null,
        'stok_tersedia'    => $stokValue,
        'id_satuan_stok'   => (int)$id_satuan_stok,
        'harga'            => $hargaValue,
        'harga_keterangan' => ($harga_keterangan !== '' ? $harga_keterangan : null),
    ];
}

if (empty($normalizedDetails)) {
    redirectEditWithError($id_laporan_stok, 'Minimal harus ada 1 baris detail stok yang valid.');
}

/*
|--------------------------------------------------------------------------
| Update header + replace detail
|--------------------------------------------------------------------------
*/
try {
    $pdo->beginTransaction();

    $stmtUpdateHeader = $pdo->prepare("
        UPDATE laporan_stok_upbs
        SET
            id_upbs = :id_upbs,
            tanggal_laporan = :tanggal_laporan,
            periode_text = :periode_text,
            nama_petugas_input = :nama_petugas_input,
            keterangan = :keterangan,
            updated_at = NOW()
        WHERE id_laporan_stok = :id_laporan_stok
    ");
    $stmtUpdateHeader->execute([
        'id_upbs'            => $id_upbs,
        'tanggal_laporan'    => $tanggal_laporan,
        'periode_text'       => ($periode_text !== '' ? $periode_text : null),
        'nama_petugas_input' => ($nama_petugas_input !== '' ? $nama_petugas_input : null),
        'keterangan'         => ($keterangan !== '' ? $keterangan : null),
        'id_laporan_stok'    => $id_laporan_stok,
    ]);

    $stmtDeleteDetail = $pdo->prepare("
        DELETE FROM laporan_stok_upbs_detail
        WHERE id_laporan_stok = ?
    ");
    $stmtDeleteDetail->execute([$id_laporan_stok]);

    $stmtInsertDetail = $pdo->prepare("
        INSERT INTO laporan_stok_upbs_detail
        (
            id_laporan_stok,
            id_komoditas,
            id_varietas,
            id_kelas_benih,
            id_benih_sumber,
            expired_date,
            stok_tersedia,
            id_satuan_stok,
            harga_min,
            harga_max,
            id_satuan_harga,
            harga_keterangan,
            urutan_tampil,
            created_at,
            updated_at
        )
        VALUES
        (
            :id_laporan_stok,
            :id_komoditas,
            :id_varietas,
            :id_kelas_benih,
            :id_benih_sumber,
            :expired_date,
            :stok_tersedia,
            :id_satuan_stok,
            :harga_min,
            :harga_max,
            :id_satuan_harga,
            :harga_keterangan,
            :urutan_tampil,
            NOW(),
            NOW()
        )
    ");

    foreach ($normalizedDetails as $i => $detail) {
        $stmtInsertDetail->execute([
            'id_laporan_stok'  => $id_laporan_stok,
            'id_komoditas'     => $detail['id_komoditas'],
            'id_varietas'      => $detail['id_varietas'],
            'id_kelas_benih'   => $detail['id_kelas_benih'],
            'id_benih_sumber'  => $detail['id_benih_sumber'],
            'expired_date'     => null,
            'stok_tersedia'    => $detail['stok_tersedia'],
            'id_satuan_stok'   => $detail['id_satuan_stok'],
            'harga_min'        => $detail['harga'],
            'harga_max'        => $detail['harga'],
            'id_satuan_harga'  => $detail['id_satuan_stok'],
            'harga_keterangan' => $detail['harga_keterangan'],
            'urutan_tampil'    => $i + 1,
        ]);
    }

    $pdo->commit();

    unset($_SESSION['old_input_edit_stok']);
    $_SESSION['success_message_upbs'] = 'Laporan stok berhasil diperbarui.';
    header('Location: ' . base_url('page_upbs/input_stok/detail.php?id=' . $id_laporan_stok));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirectEditWithError($id_laporan_stok, 'Gagal memperbarui laporan stok: ' . $e->getMessage());
}