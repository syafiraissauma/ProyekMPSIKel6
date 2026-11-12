<?php
/**
 * Hapus Data Barang
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireRole(['admin', 'petugas']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('items/index.php'));
    exit;
}

if (!verify_csrf_token()) {
    setFlash('danger', 'Validasi token keamanan gagal.');
    header('Location: ' . url('items/index.php'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$pdo = getDbConnection();

$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    setFlash('danger', 'Data barang tidak ditemukan.');
    header('Location: ' . url('items/index.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Catat log sebelum dihapus
    logActivity(
        'HAPUS_BARANG',
        null,
        $item['item_code'],
        $item['name'],
        "Menghapus barang '{$item['name']}' ({$item['item_code']}) dari sistem inventaris.",
        $item,
        null
    );

    // Hapus barang (item_transactions memiliki foreign key ON DELETE CASCADE)
    $deleteStmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $deleteStmt->execute([$id]);

    $pdo->commit();
    setFlash('success', "Barang '{$item['name']}' ({$item['item_code']}) berhasil dihapus dari sistem.");
} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('danger', 'Gagal menghapus data barang: ' . $e->getMessage());
}

header('Location: ' . url('items/index.php'));
exit;

