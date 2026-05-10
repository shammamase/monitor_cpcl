<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$eselonList = [
    'Dirjen Hortikultura',
    'Dirjen PSP',
    'Dirjen Tanaman Pangan',
    'Dirjen LIP',
    'Dirjen Perkebunan',
    'Dirjen PKH',
];

$sql = "
    SELECT
        sb.id_sumber,
        sb.nama_sumber,
        CASE
            WHEN LOCATE(' - ', sb.nama_sumber) > 0
            THEN TRIM(SUBSTRING_INDEX(sb.nama_sumber, ' - ', -1))
            ELSE '-'
        END AS nama_eselon,
        sv.satuan,
        COUNT(DISTINCT sv.id_status_verif) AS jumlah_usulan,
        SUM(sv.volume) AS total_volume
    FROM status_verifikasi sv
    LEFT JOIN sumber_bantuan sb
        ON sv.id_sumber = sb.id_sumber
    WHERE sv.is_active = 1
      AND sv.status_verifikasi = 1
      AND sv.volume IS NOT NULL
      AND sv.volume > 0
      AND sv.satuan IS NOT NULL
      AND sv.satuan <> ''
    GROUP BY
        sb.id_sumber,
        sb.nama_sumber,
        sv.satuan
    ORDER BY nama_eselon ASC, sb.nama_sumber ASC, sv.satuan ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rekap = [];

foreach ($eselonList as $eselon) {
    $rekap[$eselon] = [
        'nama_eselon' => $eselon,
        'total_usulan' => 0,
        'rows' => [],
    ];
}

foreach ($rows as $row) {
    $namaEselon = $row['nama_eselon'] ?: '-';

    if (!isset($rekap[$namaEselon])) {
        $rekap[$namaEselon] = [
            'nama_eselon' => $namaEselon,
            'total_usulan' => 0,
            'rows' => [],
        ];
    }

    $rekap[$namaEselon]['total_usulan'] += (int)$row['jumlah_usulan'];
    $rekap[$namaEselon]['rows'][] = [
        'nama_sumber' => $row['nama_sumber'],
        'satuan' => $row['satuan'],
        'total_volume' => (float)$row['total_volume'],
    ];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Usulan Rancangan');

$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'REKAP USULAN BANTUAN PEMERINTAH DAN RANCANGAN PUSAT');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$sheet->mergeCells('A3:A4');
$sheet->mergeCells('B3:B4');
$sheet->mergeCells('C3:D3');
$sheet->mergeCells('E3:F3');
$sheet->mergeCells('G3:G4');

$sheet->setCellValue('A3', 'No');
$sheet->setCellValue('B3', 'Ditjen Teknis');
$sheet->setCellValue('C3', 'Usulan bantuan pemerintah yang sudah submit ke Dit Teknis');
$sheet->setCellValue('E3', 'Rancangan Pusat');
$sheet->setCellValue('G3', 'Persentase');
$sheet->setCellValue('C4', 'volume');
$sheet->setCellValue('D4', 'satuan');
$sheet->setCellValue('E4', 'volume');
$sheet->setCellValue('F4', 'satuan');

$sheet->getStyle('A3:G4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$rowNumber = 5;
$no = 1;

foreach ($rekap as $group) {
    $detailRows = $group['rows'];
    $startRow = $rowNumber;
    $rowspan = count($detailRows) + 1;

    $sheet->setCellValue('A' . $rowNumber, $no++);
    $sheet->setCellValue('B' . $rowNumber, $group['nama_eselon']);
    $sheet->setCellValue('C' . $rowNumber, (int)$group['total_usulan']);
    $sheet->setCellValue('D' . $rowNumber, 'usulan');
    $sheet->getStyle("A{$rowNumber}:G{$rowNumber}")->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
    ]);
    $rowNumber++;

    foreach ($detailRows as $row) {
        $sheet->setCellValue('B' . $rowNumber, $row['nama_sumber']);
        $sheet->setCellValue('C' . $rowNumber, (float)$row['total_volume']);
        $sheet->setCellValue('D' . $rowNumber, $row['satuan']);
        $rowNumber++;
    }

    if ($rowspan > 1) {
        $sheet->mergeCells("A{$startRow}:A" . ($rowNumber - 1));
        $sheet->getStyle("A{$startRow}:A" . ($rowNumber - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}

$lastRow = max(4, $rowNumber - 1);
$sheet->getStyle("A3:G{$lastRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
]);
$sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C5:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

foreach (['A' => 6, 'B' => 54, 'C' => 18, 'D' => 18, 'E' => 18, 'F' => 18, 'G' => 16] as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getRowDimension(3)->setRowHeight(34);
$sheet->getRowDimension(4)->setRowHeight(24);
$sheet->freezePane('A5');

$filename = 'rekap_usulan_rancangan_pusat_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
