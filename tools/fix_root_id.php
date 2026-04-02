<?php
require_once __DIR__ . '/../config/database.php';

function findRootId(PDO $pdo, int $idStatusVerif): int
{
    $currentId = $idStatusVerif;

    while (true) {
        $stmt = $pdo->prepare("
            SELECT id_status_verif, copied_from_id
            FROM status_verifikasi
            WHERE id_status_verif = ?
            LIMIT 1
        ");
        $stmt->execute([$currentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $currentId;
        }

        if (empty($row['copied_from_id'])) {
            return (int)$row['id_status_verif'];
        }

        $currentId = (int)$row['copied_from_id'];
    }
}

$stmt = $pdo->query("
    SELECT id_status_verif
    FROM status_verifikasi
    ORDER BY id_status_verif ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("
    UPDATE status_verifikasi
    SET root_id = ?
    WHERE id_status_verif = ?
");

foreach ($rows as $row) {
    $id = (int)$row['id_status_verif'];
    $rootId = findRootId($pdo, $id);
    $update->execute([$rootId, $id]);
}

echo "Selesai update root_id.\n";