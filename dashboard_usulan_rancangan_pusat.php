<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

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
        'id_sumber' => $row['id_sumber'],
        'nama_sumber' => $row['nama_sumber'],
        'satuan' => $row['satuan'],
        'total_volume' => (float)$row['total_volume'],
    ];
}

function format_volume($value)
{
    return rtrim(rtrim(number_format((float)$value, 2, ',', '.'), '0'), ',');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usulan dan Rancangan Pusat - Monitoring CPCL</title>

    <link rel="icon" href="<?= base_url('assets/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .rekap-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .section-title {
            font-weight: 700;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .table thead th {
            text-align: center;
            white-space: normal;
        }

        .eselon-row td {
            background: #f8f9fa;
            font-weight: 700;
        }

        .ditjen-name {
            color: #146c43;
        }

        .sumber-name {
            color: #146c43;
            font-weight: 600;
            padding-left: 1.25rem;
        }

        .empty-cell {
            color: #adb5bd;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 13px;
            }

            .btn {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('index.php') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" height="36">
            <span>Monitoring CPCL BRMP</span>
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="section-title mb-1">Rekap Usulan Bantuan Pemerintah dan Rancangan Pusat</h3>
            <p class="text-muted mb-0">Data usulan yang sudah submit ke Ditjen Teknis, dikelompokkan per Eselon 1 dan sumber bantuan.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('index.php') ?>" class="btn btn-outline-secondary">Data Status Verifikasi</a>
            <a href="<?= base_url('dashboard_volume_sumber_bantuan_eselon.php') ?>" class="btn btn-outline-success">Volume per Sumber Es.1</a>
            <a href="<?= base_url('export_usulan_rancangan_pusat.php') ?>" class="btn btn-success">Export Excel</a>
        </div>
    </div>

    <div class="card rekap-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" width="70">No</th>
                            <th rowspan="2">Ditjen Teknis</th>
                            <th colspan="2">Usulan bantuan pemerintah yang sudah submit ke Dit Teknis</th>
                            <th colspan="2">Rancangan Pusat</th>
                            <th rowspan="2" width="130">Persentase</th>
                        </tr>
                        <tr>
                            <th width="140">volume</th>
                            <th width="110">satuan</th>
                            <th width="140">volume</th>
                            <th width="110">satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rekap)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($rekap as $group): ?>
                                <?php
                                    $detailRows = $group['rows'];
                                    $rowspan = count($detailRows) + 1;
                                ?>
                                <tr class="eselon-row">
                                    <td rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                                    <td>
                                        <a href="<?= base_url('index.php?' . http_build_query([
                                            'eselon_1' => $group['nama_eselon'],
                                            'status_filter' => '1',
                                        ])) ?>" class="ditjen-name text-decoration-none">
                                            <?= e($group['nama_eselon']) ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?= number_format($group['total_usulan'], 0, ',', '.') ?></td>
                                    <td>usulan</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>

                                <?php foreach ($detailRows as $row): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('index.php?' . http_build_query([
                                                'id_sumber' => $row['id_sumber'],
                                                'satuan' => $row['satuan'],
                                                'status_filter' => '1',
                                            ])) ?>" class="sumber-name text-decoration-none">
                                                <?= e($row['nama_sumber']) ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?= e(format_volume($row['total_volume'])) ?></td>
                                        <td><?= e($row['satuan']) ?></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
