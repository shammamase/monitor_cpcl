<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$id_jenis  = trim($_GET['id_jenis_bantuan'] ?? '');
$id_sumber = trim($_GET['id_sumber'] ?? '');
$status    = array_key_exists('status_verifikasi', $_GET) ? trim($_GET['status_verifikasi']) : '1';

$where = [
    'sv.is_active = 1',
    'sv.volume IS NOT NULL',
    'sv.volume > 0',
    'sv.satuan IS NOT NULL',
    "sv.satuan <> ''",
];
$params = [];

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
        jb.nama_jenis_bantuan,
        sb.nama_sumber,
        sv.satuan,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM jenis_bantuan jb
    INNER JOIN status_verifikasi_jenis_bantuan svjb ON jb.id_jenis_bantuan = svjb.id_jenis_bantuan
    INNER JOIN status_verifikasi sv ON svjb.id_status_verif = sv.id_status_verif
    LEFT JOIN sumber_bantuan sb ON jb.id_sumber = sb.id_sumber
    $whereSql
    GROUP BY jb.nama_jenis_bantuan, sb.nama_sumber, sv.satuan
    ORDER BY sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC, sv.satuan ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Volume Jenis');

$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'REKAP TOTAL VOLUME PER JENIS BANTUAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$headers = [
    'A3' => 'No',
    'B3' => 'Jenis Bantuan',
    'C3' => 'Sumber Bantuan',
    'D3' => 'Jumlah Data',
    'E3' => 'Total Volume',
    'F3' => 'Unit',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A3:F3')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$rowNumber = 4;
foreach ($data as $index => $row) {
    $sheet->setCellValue('A' . $rowNumber, $index + 1);
    $sheet->setCellValue('B' . $rowNumber, $row['nama_jenis_bantuan'] ?: '-');
    $sheet->setCellValue('C' . $rowNumber, $row['nama_sumber'] ?: '-');
    $sheet->setCellValue('D' . $rowNumber, (int)$row['jumlah_data']);
    $sheet->setCellValue('E' . $rowNumber, (float)$row['total_volume']);
    $sheet->setCellValue('F' . $rowNumber, $row['satuan'] ?: '-');
    $rowNumber++;
}

$lastRow = max(3, $rowNumber - 1);
$sheet->getStyle("A3:F{$lastRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
]);
$sheet->getStyle("D4:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

foreach (['A' => 6, 'B' => 36, 'C' => 26, 'D' => 14, 'E' => 16, 'F' => 12] as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->freezePane('A4');

$filename = 'rekap_volume_jenis_bantuan_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
