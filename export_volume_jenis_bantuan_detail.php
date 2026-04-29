<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$id_jenis = trim($_GET['id_jenis_bantuan'] ?? '');
$satuan   = trim($_GET['satuan'] ?? '');
$status   = array_key_exists('status_verifikasi', $_GET) ? trim($_GET['status_verifikasi']) : '1';

if ($id_jenis === '' || $satuan === '') {
    die('Jenis bantuan dan unit wajib dipilih.');
}

$stmtJenis = $pdo->prepare("
    SELECT jb.nama_jenis_bantuan, sb.nama_sumber
    FROM jenis_bantuan jb
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    WHERE jb.id_jenis_bantuan = ?
");
$stmtJenis->execute([$id_jenis]);
$jenis = $stmtJenis->fetch(PDO::FETCH_ASSOC);

if (!$jenis) {
    die('Jenis bantuan tidak ditemukan.');
}

$where = [
    'sv.is_active = 1',
    'sv.volume IS NOT NULL',
    'sv.volume > 0',
    'sv.satuan = :satuan',
    'jb.id_jenis_bantuan = :id_jenis_bantuan',
];
$params = [
    'satuan' => $satuan,
    'id_jenis_bantuan' => $id_jenis,
];

if ($status !== '' && ($status === '0' || $status === '1')) {
    $where[] = 'sv.status_verifikasi = :status_verifikasi';
    $params['status_verifikasi'] = $status;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        pr.name AS nama_provinsi,
        kb.type AS kabupaten_type,
        kb.name AS nama_kabupaten,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    INNER JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
    INNER JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
    LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
    LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
    $whereSql
    GROUP BY pr.name, kb.type, kb.name
    ORDER BY pr.name ASC, kb.name ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Detail Daerah');

$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'DETAIL DAERAH PENGISI VOLUME');
$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A2', ($jenis['nama_jenis_bantuan'] ?: '-') . ' | Unit: ' . $satuan);
$sheet->getStyle('A1:A2')->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getStyle('A1')->getFont()->setSize(14);

$headers = [
    'A4' => 'No',
    'B4' => 'Provinsi',
    'C4' => 'Kabupaten/Kota',
    'D4' => 'Jumlah Data',
    'E4' => 'Total Volume',
    'F4' => 'Unit',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A4:F4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$rowNumber = 5;
foreach ($data as $index => $row) {
    $sheet->setCellValue('A' . $rowNumber, $index + 1);
    $sheet->setCellValue('B' . $rowNumber, $row['nama_provinsi'] ?: '-');
    $sheet->setCellValue('C' . $rowNumber, trim(($row['kabupaten_type'] ?? '') . ' ' . ($row['nama_kabupaten'] ?? '')) ?: '-');
    $sheet->setCellValue('D' . $rowNumber, (int)$row['jumlah_data']);
    $sheet->setCellValue('E' . $rowNumber, (float)$row['total_volume']);
    $sheet->setCellValue('F' . $rowNumber, $satuan);
    $rowNumber++;
}

$lastRow = max(4, $rowNumber - 1);
$sheet->getStyle("A4:F{$lastRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
]);
$sheet->getStyle("D5:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

foreach (['A' => 6, 'B' => 24, 'C' => 28, 'D' => 14, 'E' => 16, 'F' => 12] as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->freezePane('A5');

$filename = 'detail_daerah_volume_jenis_bantuan_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
