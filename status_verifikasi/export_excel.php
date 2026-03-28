<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$keyword        = trim($_GET['keyword_sv'] ?? '');
$provinsi_id    = trim($_GET['provinsi_id'] ?? '');
$id_sumber      = trim($_GET['id_sumber'] ?? '');
$status_filter  = trim($_GET['status_filter'] ?? '');
$id_jenis       = trim($_GET['id_jenis_bantuan'] ?? '');
$tanggal_dari   = trim($_GET['tanggal_dari'] ?? '');
$tanggal_sampai = trim($_GET['tanggal_sampai'] ?? '');

/*
|--------------------------------------------------------------------------
| WHERE dinamis
|--------------------------------------------------------------------------
*/
$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(p.nama_poktan LIKE :keyword OR sb.nama_sumber LIKE :keyword)";
    $params['keyword'] = "%{$keyword}%";
}

if ($provinsi_id !== '') {
    $where[] = "p.provinsi_id = :provinsi_id";
    $params['provinsi_id'] = $provinsi_id;
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
            p.nama_poktan,
            pr.name AS provinsi,
            kb.name AS kabupaten,
            kc.name AS kecamatan,
            sb.nama_sumber,
            GROUP_CONCAT(DISTINCT jb.nama_jenis_bantuan ORDER BY jb.nama_jenis_bantuan ASC SEPARATOR ', ') AS jenis_bantuan
        FROM status_verifikasi sv
        LEFT JOIN poktan p ON sv.id_poktan = p.id_poktan
        LEFT JOIN provinsis pr ON p.provinsi_id = pr.id
        LEFT JOIN kabupatens kb ON p.kabupaten_id = kb.id
        LEFT JOIN kecamatans kc ON p.kecamatan_id = kc.id
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
            p.nama_poktan,
            pr.name,
            kb.name,
            kc.name,
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
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A1', 'LAPORAN STATUS VERIFIKASI');

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
    'B3' => 'Poktan',
    'C3' => 'Provinsi',
    'D3' => 'Kabupaten',
    'E3' => 'Kecamatan',
    'F3' => 'Sumber Bantuan',
    'G3' => 'Status',
    'H3' => 'Tanggal Submit',
    'I3' => 'Jenis Bantuan',
    'J3' => 'Keterangan Kendala',
    'K3' => 'Keterangan Umum',
    'L3' => 'Waktu Input',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A3:L3')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '0D6EFD'],
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
    $sheet->setCellValue('B' . $rowNumber, $row['nama_poktan']);
    $sheet->setCellValue('C' . $rowNumber, $row['provinsi']);
    $sheet->setCellValue('D' . $rowNumber, $row['kabupaten']);
    $sheet->setCellValue('E' . $rowNumber, $row['kecamatan']);
    $sheet->setCellValue('F' . $rowNumber, $row['nama_sumber']);
    $sheet->setCellValue('G' . $rowNumber, $statusText);
    $sheet->setCellValue('H' . $rowNumber, $tanggalSubmit);
    $sheet->setCellValue('I' . $rowNumber, $row['jenis_bantuan'] ?: '-');
    $sheet->setCellValue('J' . $rowNumber, $row['keterangan_kendala'] ?: '-');
    $sheet->setCellValue('K' . $rowNumber, $row['keterangan_umum'] ?: '-');
    $sheet->setCellValue('L' . $rowNumber, $waktuInput);

    $rowNumber++;
}

/*
|--------------------------------------------------------------------------
| Style isi tabel
|--------------------------------------------------------------------------
*/
$lastRow = $rowNumber - 1;

if ($lastRow >= 4) {
    $sheet->getStyle("A4:L{$lastRow}")->applyFromArray([
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
    'B' => 28,
    'C' => 18,
    'D' => 18,
    'E' => 18,
    'F' => 20,
    'G' => 12,
    'H' => 15,
    'I' => 35,
    'J' => 30,
    'K' => 30,
    'L' => 20,
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
$filename = 'status_verifikasi_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;