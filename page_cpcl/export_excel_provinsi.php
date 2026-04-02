<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$provinsi_id    = (int)($userLogin['provinsi_id'] ?? 0);
$kabupaten_id   = trim($_GET['kabupaten_id'] ?? '');
$id_sumber      = trim($_GET['id_sumber'] ?? '');
$status_filter  = trim($_GET['status_filter'] ?? '');
$id_jenis       = trim($_GET['id_jenis_bantuan'] ?? '');
$tanggal_dari   = trim($_GET['tanggal_dari'] ?? '');
$tanggal_sampai = trim($_GET['tanggal_sampai'] ?? '');

if ($provinsi_id <= 0) {
    die('Provinsi user tidak valid.');
}

/*
|--------------------------------------------------------------------------
| WHERE dinamis
|--------------------------------------------------------------------------
*/
$where = [];
$params = [];

$where[] = "sv.provinsi_id = :provinsi_id";
$params['provinsi_id'] = $provinsi_id;

$where[] = "sv.is_active = 1";

if ($kabupaten_id !== '') {
    $where[] = "sv.kabupaten_id = :kabupaten_id";
    $params['kabupaten_id'] = $kabupaten_id;
}

if ($id_sumber !== '') {
    $where[] = "sv.id_sumber = :id_sumber";
    $params['id_sumber'] = $id_sumber;
}

if ($status_filter !== '' && ($status_filter === '0' || $status_filter === '1')) {
    $where[] = "sv.status_verifikasi = :status_filter";
    $params['status_filter'] = $status_filter;
}

if ($id_jenis !== '') {
    $where[] = "EXISTS (
        SELECT 1
        FROM status_verifikasi_jenis_bantuan svjb2
        WHERE svjb2.id_status_verif = sv.id_status_verif
          AND svjb2.id_jenis_bantuan = :id_jenis_bantuan
    )";
    $params['id_jenis_bantuan'] = $id_jenis;
}

if ($tanggal_dari !== '') {
    $where[] = "DATE(sv.created_at) >= :tanggal_dari";
    $params['tanggal_dari'] = $tanggal_dari;
}

if ($tanggal_sampai !== '') {
    $where[] = "DATE(sv.created_at) <= :tanggal_sampai";
    $params['tanggal_sampai'] = $tanggal_sampai;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Ambil data export
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
            sv.id_status_verif,
            sv.status_verifikasi,
            sv.tanggal_submit,
            sv.keterangan_kendala,
            sv.keterangan_umum,
            sv.created_at,
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
        $whereSql
        GROUP BY
            sv.id_status_verif,
            sv.status_verifikasi,
            sv.tanggal_submit,
            sv.keterangan_kendala,
            sv.keterangan_umum,
            sv.created_at,
            pr.name,
            kb.name,
            sb.nama_sumber
        ORDER BY sv.id_status_verif DESC";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}

$stmt->execute();
$data = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Buat Spreadsheet
|--------------------------------------------------------------------------
*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Status Verifikasi');

/*
|--------------------------------------------------------------------------
| Judul
|--------------------------------------------------------------------------
*/
$sheet->mergeCells('A1:I1');
$sheet->setCellValue('A1', 'LAPORAN STATUS VERIFIKASI PROVINSI - ' . strtoupper($userLogin['nama_provinsi'] ?? ''));

$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
]);

/*
|--------------------------------------------------------------------------
| Header tabel
|--------------------------------------------------------------------------
*/
$headers = [
    'A3' => 'No',
    'B3' => 'Provinsi',
    'C3' => 'Kabupaten',
    'D3' => 'Sumber Bantuan',
    'E3' => 'Status Submit Es.1',
    'F3' => 'Tanggal Submit',
    'G3' => 'Jenis Bantuan',
    'H3' => 'Keterangan',
    // 'I3' => 'Keterangan Umum',
    'I3' => 'Waktu Input',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A3:I3')->applyFromArray([
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

/*
|--------------------------------------------------------------------------
| Isi data
|--------------------------------------------------------------------------
*/
$rowNumber = 4;
$no = 1;

foreach ($data as $row) {
    $statusText = ((int)$row['status_verifikasi'] === 1) ? 'Sudah' : 'Belum';
    $tanggalSubmit = !empty($row['tanggal_submit']) ? date('d-m-Y', strtotime($row['tanggal_submit'])) : '-';
    $waktuInput = !empty($row['created_at']) ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-';

    $sheet->setCellValue('A' . $rowNumber, $no++);
    $sheet->setCellValue('B' . $rowNumber, $row['provinsi']);
    $sheet->setCellValue('C' . $rowNumber, $row['kabupaten_type'] . ' ' . $row['kabupaten']);
    $sheet->setCellValue('D' . $rowNumber, $row['nama_sumber']);
    $sheet->setCellValue('E' . $rowNumber, $statusText);
    $sheet->setCellValue('F' . $rowNumber, $tanggalSubmit);
    $sheet->setCellValue('G' . $rowNumber, $row['jenis_bantuan'] ?: '-');
    $sheet->setCellValue('H' . $rowNumber, $row['keterangan_kendala'] ?: '-');
    //$sheet->setCellValue('I' . $rowNumber, $row['keterangan_umum'] ?: '-');
    $sheet->setCellValue('I' . $rowNumber, $waktuInput);

    $rowNumber++;
}

/*
|--------------------------------------------------------------------------
| Style isi tabel
|--------------------------------------------------------------------------
*/
$lastRow = $rowNumber - 1;

if ($lastRow >= 4) {
    $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
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
}

/*
|--------------------------------------------------------------------------
| Lebar kolom
|--------------------------------------------------------------------------
*/
$widths = [
    'A' => 6,
    'B' => 18,
    'C' => 18,
    'D' => 20,
    'E' => 20,
    'F' => 15,
    'G' => 35,
    'H' => 30,
    'I' => 30,
    // 'J' => 20,
];

foreach ($widths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

/*
|--------------------------------------------------------------------------
| Freeze pane
|--------------------------------------------------------------------------
*/
$sheet->freezePane('A4');

/*
|--------------------------------------------------------------------------
| Output file
|--------------------------------------------------------------------------
*/
$filename = 'status_verifikasi_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', strtolower($userLogin['nama_provinsi'] ?? 'provinsi')) . '_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;