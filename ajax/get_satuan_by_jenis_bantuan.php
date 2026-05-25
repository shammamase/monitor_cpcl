<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

function renderOptions(array $options): void
{
    echo '<option value="">-- Pilih Satuan --</option>';

    foreach ($options as $option) {
        echo '<option value="' . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . '</option>';
    }
}

$jenisIdsRaw = $_GET['id_jenis_bantuan'] ?? [];

if (!is_array($jenisIdsRaw)) {
    $jenisIdsRaw = [$jenisIdsRaw];
}

$jenisIds = [];

foreach ($jenisIdsRaw as $id) {
    $id = (int)$id;
    if ($id > 0) {
        $jenisIds[] = $id;
    }
}

$jenisIds = array_values(array_unique($jenisIds));
$allSatuanOptions = cpcl_all_satuan_options();
$matchedOptions = cpcl_allowed_satuan_for_jenis_bantuan($pdo, $jenisIds, $allSatuanOptions);
renderOptions($matchedOptions);
