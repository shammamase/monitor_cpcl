<?php

function base_url($path = '')
{
    $base = '/monitor_cpcl/';
    return $base . ltrim($path, '/');
}

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function cpcl_all_satuan_options(): array
{
    return ['Kg', 'Ton', 'Unit', 'Ha', 'Liter', 'Paket', 'Batang', 'Ekor', 'Meter', 'M2', 'Kelompok Masyarakat', 'Sertifikat'];
}

function cpcl_jenis_bantuan_satuan_table_exists(PDO $pdo): bool
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'jenis_bantuan_satuan'
    ");

    $exists = (int)$stmt->fetch()['total'] > 0;
    return $exists;
}

function cpcl_allowed_satuan_for_jenis_bantuan(PDO $pdo, array $jenisIds, ?array $allOptions = null): array
{
    $allOptions = $allOptions ?? cpcl_all_satuan_options();

    $jenisIds = array_values(array_unique(array_filter(array_map('intval', $jenisIds), static function ($id) {
        return $id > 0;
    })));

    if (empty($jenisIds) || !cpcl_jenis_bantuan_satuan_table_exists($pdo)) {
        return $allOptions;
    }

    $placeholders = implode(',', array_fill(0, count($jenisIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id_jenis_bantuan, satuan
        FROM jenis_bantuan_satuan
        WHERE id_jenis_bantuan IN ($placeholders)
        ORDER BY id_jenis_bantuan ASC, satuan ASC
    ");
    $stmt->execute($jenisIds);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        return $allOptions;
    }

    $mappedSatuanByJenis = [];

    foreach ($rows as $row) {
        $jenisId = (int)$row['id_jenis_bantuan'];
        $satuan = trim((string)$row['satuan']);

        if ($jenisId <= 0 || $satuan === '') {
            continue;
        }

        if (!isset($mappedSatuanByJenis[$jenisId])) {
            $mappedSatuanByJenis[$jenisId] = [];
        }

        $mappedSatuanByJenis[$jenisId][$satuan] = true;
    }

    if (empty($mappedSatuanByJenis)) {
        return $allOptions;
    }

    $requiredCount = count($mappedSatuanByJenis);
    $satuanCounts = [];

    foreach ($mappedSatuanByJenis as $satuanMap) {
        foreach (array_keys($satuanMap) as $satuan) {
            if (!isset($satuanCounts[$satuan])) {
                $satuanCounts[$satuan] = 0;
            }
            $satuanCounts[$satuan]++;
        }
    }

    $matchedOptions = [];

    foreach ($allOptions as $option) {
        if (($satuanCounts[$option] ?? 0) === $requiredCount) {
            $matchedOptions[] = $option;
        }
    }

    return $matchedOptions;
}

function cpcl_is_satuan_allowed_for_jenis_bantuan(PDO $pdo, array $jenisIds, string $satuan, ?array $allOptions = null): bool
{
    $satuan = trim($satuan);

    if ($satuan === '') {
        return false;
    }

    $allowedOptions = cpcl_allowed_satuan_for_jenis_bantuan($pdo, $jenisIds, $allOptions);
    return in_array($satuan, $allowedOptions, true);
}
