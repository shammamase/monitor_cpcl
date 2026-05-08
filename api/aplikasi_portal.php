<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method tidak diizinkan.', null, 405);
}

function jsonResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$kategori = trim($_GET['kategori'] ?? '');
$tipe = trim($_GET['tipe'] ?? '');
$keyword = trim($_GET['q'] ?? '');

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT
                id_aplikasi AS id,
                nama_aplikasi AS name,
                kategori AS category,
                tipe AS type,
                url
            FROM aplikasi_portal
            WHERE id_aplikasi = :id
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $app = $stmt->fetch();

        if (!$app) {
            jsonResponse(false, 'Aplikasi tidak ditemukan.', null, 404);
        }

        jsonResponse(true, 'Detail aplikasi berhasil diambil.', $app);
    }

    $where = ['is_active = 1'];
    $params = [];

    if ($kategori !== '') {
        $where[] = 'kategori = :kategori';
        $params['kategori'] = $kategori;
    }

    if ($tipe !== '') {
        $where[] = 'tipe = :tipe';
        $params['tipe'] = $tipe;
    }

    if ($keyword !== '') {
        $where[] = '(nama_aplikasi LIKE :keyword OR kategori LIKE :keyword OR tipe LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }

    $sql = "
        SELECT
            id_aplikasi AS id,
            nama_aplikasi AS name,
            kategori AS category,
            tipe AS type,
            url
        FROM aplikasi_portal
        WHERE " . implode(' AND ', $where) . "
        ORDER BY kategori ASC, nama_aplikasi ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(true, 'Daftar aplikasi berhasil diambil.', $stmt->fetchAll());
} catch (Throwable $e) {
    jsonResponse(false, 'Gagal mengambil data aplikasi.', null, 500);
}
