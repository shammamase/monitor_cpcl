<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$provinsi_id = trim($_GET['provinsi_id'] ?? '');
$id_jenis    = trim($_GET['id_jenis_bantuan'] ?? '');
$id_sumber   = trim($_GET['id_sumber'] ?? '');
$status      = array_key_exists('status_verifikasi', $_GET) ? trim($_GET['status_verifikasi']) : '1';

$where = [
    'sv.is_active = 1',
    'sv.volume IS NOT NULL',
    'sv.volume > 0',
];
$params = [];

if ($provinsi_id !== '') {
    $where[] = 'sv.provinsi_id = :provinsi_id';
    $params['provinsi_id'] = $provinsi_id;
}

if ($id_jenis !== '') {
    $where[] = 'jb.id_jenis_bantuan = :id_jenis_bantuan';
    $params['id_jenis_bantuan'] = $id_jenis;
}

if ($id_sumber !== '') {
    $where[] = 'jb.id_sumber = :id_sumber';
    $params['id_sumber'] = $id_sumber;
}

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
        jb.nama_jenis_bantuan,
        sb.nama_sumber,
        sv.satuan,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    INNER JOIN provinsis pr ON sv.provinsi_id = pr.id
    LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
    INNER JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
    INNER JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    $whereSql
    GROUP BY pr.name, kb.type, kb.name, jb.nama_jenis_bantuan, sb.nama_sumber, sv.satuan
    ORDER BY pr.name ASC, kb.name ASC, jb.nama_jenis_bantuan ASC, sv.satuan ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Volume Provinsi');

$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'REKAP TOTAL VOLUME PER PROVINSI DAN JENIS BANTUAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$headers = [
    'A3' => 'No',
    'B3' => 'Provinsi',
    'C3' => 'Kabupaten/Kota',
    'D3' => 'Jenis Bantuan',
    'E3' => 'Sumber Bantuan',
    'F3' => 'Jumlah Data',
    'G3' => 'Total Volume',
    'H3' => 'Unit',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A3:H3')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$rowNumber = 4;
foreach ($data as $index => $row) {
    $sheet->setCellValue('A' . $rowNumber, $index + 1);
    $sheet->setCellValue('B' . $rowNumber, $row['nama_provinsi'] ?: '-');
    $sheet->setCellValue('C' . $rowNumber, trim(($row['kabupaten_type'] ?? '') . ' ' . ($row['nama_kabupaten'] ?? '')) ?: '-');
    $sheet->setCellValue('D' . $rowNumber, $row['nama_jenis_bantuan'] ?: '-');
    $sheet->setCellValue('E' . $rowNumber, $row['nama_sumber'] ?: '-');
    $sheet->setCellValue('F' . $rowNumber, (int)$row['jumlah_data']);
    $sheet->setCellValue('G' . $rowNumber, (float)$row['total_volume']);
    $sheet->setCellValue('H' . $rowNumber, $row['satuan'] ?: '-');
    $rowNumber++;
}

$lastRow = max(3, $rowNumber - 1);
$sheet->getStyle("A3:H{$lastRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
]);
$sheet->getStyle("F4:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

foreach (['A' => 6, 'B' => 22, 'C' => 24, 'D' => 32, 'E' => 24, 'F' => 14, 'G' => 16, 'H' => 12] as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->freezePane('A4');

$filename = 'rekap_volume_provinsi_jenis_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
