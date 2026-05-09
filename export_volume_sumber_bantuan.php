<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

if ($id_sumber !== '') {
    $where[] = 'sv.id_sumber = :id_sumber';
    $params['id_sumber'] = $id_sumber;
}

if ($status !== '' && ($status === '0' || $status === '1')) {
    $where[] = 'sv.status_verifikasi = :status_verifikasi';
    $params['status_verifikasi'] = $status;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        sb.nama_sumber,
        COALESCE(jb.nama_jenis_bantuan, '-') AS nama_jenis_bantuan,
        sv.satuan,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_data,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    LEFT JOIN sumber_bantuan sb
        ON sv.id_sumber = sb.id_sumber
    LEFT JOIN status_verifikasi_jenis_bantuan svjb
        ON sv.id_status_verif = svjb.id_status_verif
    LEFT JOIN jenis_bantuan jb
        ON svjb.id_jenis_bantuan = jb.id_jenis_bantuan
    $whereSql
    GROUP BY sb.nama_sumber, jb.nama_jenis_bantuan, sv.satuan
    ORDER BY sb.nama_sumber ASC, jb.nama_jenis_bantuan ASC, sv.satuan ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalVolumeSql = "
    SELECT
        sv.satuan,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    $whereSql
    GROUP BY sv.satuan
    ORDER BY sv.satuan ASC
";

$stmtTotalVolume = $pdo->prepare($totalVolumeSql);
foreach ($params as $key => $value) {
    $stmtTotalVolume->bindValue(':' . $key, $value, PDO::PARAM_STR);
}

$stmtTotalVolume->execute();
$totalVolumeRows = $stmtTotalVolume->fetchAll(PDO::FETCH_ASSOC);
$totalVolumeByUnit = [];
$displayGroups = [];

foreach ($totalVolumeRows as $totalRow) {
    if (!empty($totalRow['satuan'])) {
        $totalVolumeByUnit[$totalRow['satuan']] = (float)$totalRow['total_volume'];
    }
}

foreach ($data as &$row) {
    $row['total_volume'] = (float)$row['total_volume'];
    $groupKey = (string)($row['nama_sumber'] ?? '-');

    if (!isset($displayGroups[$groupKey])) {
        $displayGroups[$groupKey] = [
            'nama_sumber' => $row['nama_sumber'],
            'rows' => [],
        ];
    }

    $displayGroups[$groupKey]['rows'][] = [
        'nama_jenis_bantuan' => $row['nama_jenis_bantuan'],
        'satuan' => $row['satuan'],
        'total_volume' => $row['total_volume'],
    ];
}
unset($row);

$displayGroups = array_values($displayGroups);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Volume Sumber');

$sheet->mergeCells('A1:E1');
$sheet->setCellValue('A1', 'REKAP TOTAL VOLUME PER SUMBER BANTUAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$headers = [
    'A3' => 'No',
    'B3' => 'Sumber Bantuan',
    'C3' => 'Jenis Bantuan',
    'D3' => 'Total Volume',
    'E3' => 'Satuan',
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$sheet->getStyle('A3:E3')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$rowNumber = 4;
foreach ($displayGroups as $index => $group) {
    $startRow = $rowNumber;
    $rowspan = count($group['rows']);

    foreach ($group['rows'] as $rowIndex => $row) {
        if ($rowIndex === 0) {
            $sheet->setCellValue('A' . $rowNumber, $index + 1);
            $sheet->setCellValue('B' . $rowNumber, $group['nama_sumber'] ?: '-');
        }

        $sheet->setCellValue('C' . $rowNumber, $row['nama_jenis_bantuan'] ?: '-');
        $sheet->setCellValue('D' . $rowNumber, (float)$row['total_volume']);
        $sheet->setCellValue('E' . $rowNumber, $row['satuan'] ?: '-');
        $rowNumber++;
    }

    if ($rowspan > 1) {
        $sheet->mergeCells("A{$startRow}:A" . ($rowNumber - 1));
        $sheet->mergeCells("B{$startRow}:B" . ($rowNumber - 1));
        $sheet->getStyle("A{$startRow}:A" . ($rowNumber - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("B{$startRow}:B" . ($rowNumber - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}

foreach ($totalVolumeByUnit as $satuan => $totalVolume) {
    $sheet->mergeCells("A{$rowNumber}:C{$rowNumber}");
    $sheet->setCellValue('A' . $rowNumber, 'Total Keseluruhan');
    $sheet->setCellValue('D' . $rowNumber, (float)$totalVolume);
    $sheet->setCellValue('E' . $rowNumber, $satuan);
    $sheet->getStyle("A{$rowNumber}:E{$rowNumber}")->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
    ]);
    $sheet->getStyle('A' . $rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $rowNumber++;
}

$lastRow = max(3, $rowNumber - 1);
$sheet->getStyle("A3:E{$lastRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
]);
$sheet->getStyle("D4:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

foreach (['A' => 6, 'B' => 46, 'C' => 38, 'D' => 16, 'E' => 12] as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->freezePane('A4');

$filename = 'rekap_volume_sumber_bantuan_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
