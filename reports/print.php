<?php
/**
 * Cetak Laporan Fisik / Format PDF Print
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pdo = getDbConnection();
$user = currentUser();
$reportType = trim($_GET['report_type'] ?? 'inventory');
$categoryId = trim($_GET['category_id'] ?? '');
$locationId = trim($_GET['location_id'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$institutionName = getSetting('institution_name', 'Kelompok 6 MPSI');
$institutionAddress = getSetting('institution_address', 'Jl. Kampus Merdeka No. 6');
$institutionPhone = getSetting('institution_phone', '0812-3456-7890');
$institutionEmail = getSetting('institution_email', 'inventaris@kel6mpsi.ac.id');

// Fetch data
if ($reportType === 'inventory') {
    $reportTitle = 'LAPORAN REKAPITULASI STOK INVENTARIS BARANG';
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
    $data = $stmt->fetchAll();
} elseif ($reportType === 'transactions') {
    $reportTitle = 'LAPORAN MUTASI BARANG MASUK & KELUAR';
    $where = [];
    $params = [];
    if ($categoryId !== '') { $where[] = "i.category_id = ?"; $params[] = (int)$categoryId; }
    if ($startDate !== '') { $where[] = "DATE(t.transaction_date) >= ?"; $params[] = $startDate; }
    if ($endDate !== '') { $where[] = "DATE(t.transaction_date) <= ?"; $params[] = $endDate; }
    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT t.*, i.item_code, i.name as item_name, i.unit, c.name as category_name, u.full_name as officer_name 
        FROM item_transactions t
        JOIN items i ON t.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        {$whereSql}
        ORDER BY t.transaction_date DESC
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
} else {
    $reportTitle = 'LAPORAN REKAPITULASI FISIK BERDASARKAN KONDISI';
    $data = $pdo->query("
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
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($reportTitle) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/print.css') ?>?v=1.0">

    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f8faf9;
            margin: 0;
            padding: 24px;
            color: #0f172a;
        }

        .controls {
            max-width: 980px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            background-color: #2d5a43;
            color: #fff;
            padding: 9px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { background-color: #234835; }
        .btn-secondary { background-color: #64748b; }
        .btn-secondary:hover { background-color: #475569; }

        .print-paper {
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }

        /* Kop Surat */
        .kop-surat {
            border-bottom: 2.5px solid #2d5a43;
            padding-bottom: 14px;
            margin-bottom: 24px;
            text-align: center;
        }
        .kop-surat h1 {
            font-size: 18px;
            margin: 0;
            font-weight: 800;
            color: #2d5a43;
            letter-spacing: 0.5px;
        }
        .kop-surat h2 {
            font-size: 14px;
            margin: 4px 0;
            font-weight: 600;
            color: #334155;
        }
        .kop-surat p {
            margin: 0;
            font-size: 11px;
            color: #64748b;
        }

        .report-subject {
            text-align: center;
            margin-bottom: 24px;
        }
        .report-subject h3 {
            font-size: 15px;
            font-weight: 700;
            text-decoration: underline;
            margin: 0 0 6px;
        }
        .report-subject p {
            font-size: 11.5px;
            color: #475569;
            margin: 0;
        }

        /* Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 30px;
        }
        .report-table th, .report-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        .report-table th {
            background-color: #f1f5f3;
            color: #1e3d2f;
            font-weight: 700;
        }

        /* Tanda Tangan */
        .signature-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
            width: 240px;
            font-size: 12px;
        }
        .sig-space {
            height: 70px;
        }
        .sig-name {
            font-weight: 700;
            text-decoration: underline;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .controls { display: none !important; }
            .print-paper { box-shadow: none; padding: 0; }
            .kop-surat { border-bottom: 2px solid #000; }
            .kop-surat h1 { color: #000; }
            .report-table th, .report-table td { border-color: #333 !important; }
            .report-table th { background-color: #eee !important; color: #000 !important; }
        }
    </style>
</head>
<body>

<div class="controls">
    <button type="button" class="btn" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak Laporan / Simpan PDF</span>
    </button>
    <a href="javascript:window.close()" class="btn btn-secondary">Tutup Halaman</a>
</div>

<div class="print-paper">
    <!-- Kop Surat Resmi -->
    <div class="kop-surat">
        <h1><?= strtoupper(e($institutionName)) ?></h1>
        <h2>SISTEM INFORMASI MANAJEMEN INVENTARIS BARANG (SIM-INVENTARIS)</h2>
        <p><?= e($institutionAddress) ?> &bull; Telp: <?= e($institutionPhone) ?> &bull; Email: <?= e($institutionEmail) ?></p>
    </div>

    <!-- Judul Laporan -->
    <div class="report-subject">
        <h3><?= e($reportTitle) ?></h3>
        <p>
            Periode Data: <?= !empty($startDate) ? formatDate($startDate, 'short') : 'Awal Pendataan' ?> s/d <?= !empty($endDate) ? formatDate($endDate, 'short') : 'Sekarang' ?>
            &bull; Dicetak: <?= date('d M Y, H:i') ?> WIB
        </p>
    </div>

    <!-- Konten Tabel Laporan -->
    <?php if ($reportType === 'inventory'): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Jumlah</th>
                    <th>Kondisi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="8" style="text-align: center;">Tidak ada data barang.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $idx => $row): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= e($row['item_code']) ?></strong></td>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['category_name']) ?></td>
                            <td><?= e($row['location_name']) ?></td>
                            <td><?= formatNumber($row['quantity']) ?> <?= e($row['unit']) ?></td>
                            <td><?= e($row['item_condition']) ?></td>
                            <td><?= e($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php elseif ($reportType === 'transactions'): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Kode & Nama Barang</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Pihak / Supplier / Penerima</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="8" style="text-align: center;">Tidak ada mutasi transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $idx => $row): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= e($row['transaction_code']) ?></strong></td>
                            <td><?= formatDate($row['transaction_date']) ?></td>
                            <td><?= e($row['item_name']) ?> (<?= e($row['item_code']) ?>)</td>
                            <td><strong><?= e($row['transaction_type']) ?></strong></td>
                            <td><?= formatNumber($row['quantity']) ?> <?= e($row['unit']) ?></td>
                            <td><?= e($row['recipient_or_supplier'] ?: '-') ?></td>
                            <td><?= e($row['officer_name'] ?? 'Petugas') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Kategori Inventaris</th>
                    <th>Total Item</th>
                    <th>Total Fisik Unit</th>
                    <th>Kondisi Baik</th>
                    <th>Kondisi Rusak Ringan</th>
                    <th>Kondisi Rusak Berat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $idx => $row): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><strong><?= e($row['category_name']) ?></strong></td>
                        <td><?= formatNumber($row['total_items']) ?> Item</td>
                        <td><strong><?= formatNumber($row['total_units']) ?></strong> Unit</td>
                        <td><?= formatNumber($row['qty_baik']) ?> Unit</td>
                        <td><?= formatNumber($row['qty_rusak_ringan']) ?> Unit</td>
                        <td><?= formatNumber($row['qty_rusak_berat']) ?> Unit</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Tanda Tangan Penanggung Jawab -->
    <div class="signature-grid">
        <div class="sig-box">
            <div>Mengetahui,</div>
            <div>Kepala Bagian Umum & Logistik</div>
            <div class="sig-space"></div>
            <div class="sig-name">Ir. Hendra Kusuma, M.T.</div>
            <div>NIP. 19780512 200212 1 003</div>
        </div>

        <div class="sig-box">
            <div>Jakarta, <?= date('d F Y') ?></div>
            <div>Petugas Pengelola Inventaris</div>
            <div class="sig-space"></div>
            <div class="sig-name"><?= e($user['full_name']) ?></div>
            <div>Petugas Pelaksana Logistik</div>
        </div>
    </div>
</div>

</body>
</html>

