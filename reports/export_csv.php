<?php
/**
 * Ekspor Laporan ke Format CSV / Excel
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pdo = getDbConnection();
$reportType = trim($_GET['report_type'] ?? 'inventory');
$categoryId = trim($_GET['category_id'] ?? '');
$locationId = trim($_GET['location_id'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$filename = "Laporan_Inventaris_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream
$output = fopen('php://output', 'w');

// Tambahkan BOM UTF-8 agar Excel membuka dengan encoding yang benar
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($reportType === 'inventory') {
    // Header Kolom
    fputcsv($output, ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Lokasi', 'Jumlah', 'Satuan', 'Kondisi', 'Status', 'Tanggal Pendataan', 'Keterangan']);

    $where = [];
    $params = [];
    if ($categoryId !== '') { $where[] = "i.category_id = ?"; $params[] = (int)$categoryId; }
    if ($locationId !== '') { $where[] = "i.location_id = ?"; $params[] = (int)$locationId; }
    if ($condition !== '') { $where[] = "i.item_condition = ?"; $params[] = $condition; }
    if ($startDate !== '') { $where[] = "i.entry_date >= ?"; $params[] = $startDate; }
    if ($endDate !== '') { $where[] = "i.entry_date <= ?"; $params[] = $endDate; }
    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name, l.name as location_name 
        FROM items i
        JOIN categories c ON i.category_id = c.id
        JOIN locations l ON i.location_id = l.id
        {$whereSql}
        ORDER BY i.item_code ASC
    ");
    $stmt->execute($params);

    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['item_code'],
            $row['name'],
            $row['category_name'],
            $row['location_name'],
            $row['quantity'],
            $row['unit'],
            $row['item_condition'],
            $row['status'],
            $row['entry_date'],
            $row['description']
        ]);
    }
} elseif ($reportType === 'transactions') {
    fputcsv($output, ['No', 'No. Transaksi', 'Tanggal Transaksi', 'Kode Barang', 'Nama Barang', 'Jenis Mutasi', 'Jumlah', 'Satuan', 'Stok Sebelum', 'Stok Sesudah', 'Pihak Terkait', 'Petugas', 'Keterangan']);

    $where = [];
    $params = [];
    if ($categoryId !== '') { $where[] = "i.category_id = ?"; $params[] = (int)$categoryId; }
    if ($startDate !== '') { $where[] = "DATE(t.transaction_date) >= ?"; $params[] = $startDate; }
    if ($endDate !== '') { $where[] = "DATE(t.transaction_date) <= ?"; $params[] = $endDate; }
    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT t.*, i.item_code, i.name as item_name, i.unit, u.full_name as officer_name 
        FROM item_transactions t
        JOIN items i ON t.item_id = i.id
        LEFT JOIN users u ON t.user_id = u.id
        {$whereSql}
        ORDER BY t.transaction_date DESC
    ");
    $stmt->execute($params);

    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['transaction_code'],
            $row['transaction_date'],
            $row['item_code'],
            $row['item_name'],
            $row['transaction_type'],
            $row['quantity'],
            $row['unit'],
            $row['stock_before'],
            $row['stock_after'],
            $row['recipient_or_supplier'],
            $row['officer_name'],
            $row['notes']
        ]);
    }
} else {
    fputcsv($output, ['No', 'Kategori', 'Total Jenis Item', 'Total Fisik Unit', 'Kondisi Baik', 'Kondisi Rusak Ringan', 'Kondisi Rusak Berat']);

    $stmt = $pdo->query("
        SELECT 
            c.name as category_name,
            COUNT(i.id) as total_items,
            COALESCE(SUM(i.quantity), 0) as total_units,
            COALESCE(SUM(CASE WHEN i.item_condition = 'Baik' THEN i.quantity ELSE 0 END), 0) as qty_baik,
            COALESCE(SUM(CASE WHEN i.item_condition = 'Rusak Ringan' THEN i.quantity ELSE 0 END), 0) as qty_rusak_ringan,
            COALESCE(SUM(CASE WHEN i.item_condition = 'Rusak Berat' THEN i.quantity ELSE 0 END), 0) as qty_rusak_berat
        FROM categories c
        LEFT JOIN items i ON c.id = i.category_id
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ");

    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['category_name'],
            $row['total_items'],
            $row['total_units'],
            $row['qty_baik'],
            $row['qty_rusak_ringan'],
            $row['qty_rusak_berat']
        ]);
    }
}

fclose($output);
exit;

