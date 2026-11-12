<?php
/**
 * API Lookup Barang Berdasarkan Kode / Hasil Scan QR
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$rawInput = trim($_GET['code'] ?? '');
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter kode barang kosong.']);
    exit;
}

// Jika input berupa URL penuh (misal: http://.../detail.php?code=BRG-2024-0001)
$code = $rawInput;
if (filter_var($rawInput, FILTER_VALIDATE_URL) || strpos($rawInput, 'code=') !== false) {
    $parsedUrl = parse_url($rawInput);
    if (!empty($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryVars);
        if (!empty($queryVars['code'])) {
            $code = $queryVars['code'];
        }
    }
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("
    SELECT i.*, c.name as category_name, l.name as location_name 
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.location_id = l.id
    WHERE i.item_code = ? OR i.name = ?
    LIMIT 1
");
$stmt->execute([$code, $code]);
$item = $stmt->fetch();

if ($item) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $item['id'],
            'item_code' => $item['item_code'],
            'name' => $item['name'],
            'category_name' => $item['category_name'],
            'location_name' => $item['location_name'],
            'quantity' => (int)$item['quantity'],
            'unit' => $item['unit'],
            'item_condition' => $item['item_condition'],
            'status' => $item['status'],
            'entry_date' => formatDate($item['entry_date'], 'short'),
            'detail_url' => url('items/detail.php?code=' . urlencode($item['item_code'])),
            'trx_in_url' => url('transactions/in.php?item_id=' . $item['id']),
            'trx_out_url' => url('transactions/out.php?item_id=' . $item['id'])
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "Barang dengan kode '{$code}' tidak ditemukan di database."
    ]);
}
exit;

