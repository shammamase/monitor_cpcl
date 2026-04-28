<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$sql = "SELECT
            sv.id_status_verif,
            sv.volume,
            sv.satuan,
            pr.name AS provinsi,
            kb.type AS kabupaten_type,
            kb.name AS kabupaten,
            sb.nama_sumber,
            GROUP_CONCAT(DISTINCT jb.nama_jenis_bantuan ORDER BY jb.nama_jenis_bantuan ASC SEPARATOR ', ') AS jenis_bantuan
        FROM status_verifikasi sv
        LEFT JOIN provinsis pr ON sv.provinsi_id = pr.id
        LEFT JOIN kabupatens kb ON sv.kabupaten_id = kb.id
        LEFT JOIN sumber_bantuan sb ON sv.id_sumber = sb.id_sumber
        LEFT JOIN status_verifikasi_jenis_bantuan svjb ON sv.id_status_verif = svjb.id_status_verif
        LEFT JOIN jenis_bantuan jb ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
        WHERE sv.status_verifikasi = 1
          AND (sv.volume IS NULL OR sv.satuan IS NULL OR sv.satuan = '')
          AND sv.is_active = 1
        GROUP BY
            sv.id_status_verif,
            sv.volume,
            sv.satuan,
            pr.name,
            kb.type,
            kb.name,
            sb.nama_sumber
        ORDER BY pr.name ASC, kb.name ASC, sv.id_status_verif ASC";

$data = $pdo->query($sql)->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Volume');

$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'TEMPLATE UPDATE VOLUME DAN UNIT STATUS VERIFIKASI');
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
]);

$sheet->setCellValue('A2', 'Isi hanya kolom volume dan unit. Jangan ubah id_status_verif.');
$sheet->mergeCells('A2:G2');

$headers = [
    'A4' => 'id_status_verif',
    'B4' => 'nama_provinsi',
    'C4' => 'nama_kabupaten',
    'D4' => 'sumber_bantuan',
    'E4' => 'jenis_bantuan',
    'F4' => 'volume',
    'G4' => 'unit',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A4:G4')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '198754'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

$rowNumber = 5;

foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNumber, $row['id_status_verif']);
    $sheet->setCellValue('B' . $rowNumber, $row['provinsi']);
    $sheet->setCellValue('C' . $rowNumber, trim(($row['kabupaten_type'] ?? '') . ' ' . ($row['kabupaten'] ?? '')));
    $sheet->setCellValue('D' . $rowNumber, $row['nama_sumber']);
    $sheet->setCellValue('E' . $rowNumber, $row['jenis_bantuan'] ?: '-');
    $sheet->setCellValue('F' . $rowNumber, $row['volume'] !== null && $row['volume'] !== '' ? rtrim(rtrim(number_format((float)$row['volume'], 2, ',', '.'), '0'), ',') : '');
    $sheet->setCellValue('G' . $rowNumber, $row['satuan'] ?? '');

    $rowNumber++;
}

$lastRow = max(4, $rowNumber - 1);
$sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
    'alignment' => [
        'vertical' => Alignment::VERTICAL_TOP,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

$widths = [
    'A' => 18,
    'B' => 24,
    'C' => 24,
    'D' => 35,
    'E' => 45,
    'F' => 16,
    'G' => 16,
];

foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

$sheet->freezePane('A5');

$filename = 'template_update_volume_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
