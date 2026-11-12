<?php
/**
 * Laporan Inventaris Barang
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Laporan Inventaris';
$breadcrumbTitle = 'Laporan';
$pdo = getDbConnection();

// Parameter Tipe Laporan & Filter
$reportType = trim($_GET['report_type'] ?? 'inventory'); // inventory, transactions, condition
$categoryId = trim($_GET['category_id'] ?? '');
$locationId = trim($_GET['location_id'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

// Filter Master
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name ASC")->fetchAll();

// 1. LAPORAN STOK INVENTARIS
if ($reportType === 'inventory') {
    $where = [];
    $params = [];

    if ($categoryId !== '') {
        $where[] = "i.category_id = ?";
        $params[] = (int)$categoryId;
    }
    if ($locationId !== '') {
        $where[] = "i.location_id = ?";
        $params[] = (int)$locationId;
    }
    if ($condition !== '') {
        $where[] = "i.item_condition = ?";
        $params[] = $condition;
    }
    if ($startDate !== '') {
        $where[] = "i.entry_date >= ?";
        $params[] = $startDate;
    }
    if ($endDate !== '') {
        $where[] = "i.entry_date <= ?";
        $params[] = $endDate;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "
        SELECT i.*, c.name as category_name, l.name as location_name 
        FROM items i
        JOIN categories c ON i.category_id = c.id
        JOIN locations l ON i.location_id = l.id
        {$whereSql}
        ORDER BY i.item_code ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

    // Rekapitulasi Data
    $totalItems = count($reportData);
    $totalUnits = array_sum(array_column($reportData, 'quantity'));
    $baikUnits = 0; $rusakRinganUnits = 0; $rusakBeratUnits = 0;
    foreach ($reportData as $row) {
        if ($row['item_condition'] === 'Baik') $baikUnits += $row['quantity'];
        elseif ($row['item_condition'] === 'Rusak Ringan') $rusakRinganUnits += $row['quantity'];
        elseif ($row['item_condition'] === 'Rusak Berat') $rusakBeratUnits += $row['quantity'];
    }
}

// 2. LAPORAN TRANSAKSI MASUK & KELUAR
elseif ($reportType === 'transactions') {
    $where = [];
    $params = [];

    if ($categoryId !== '') {
        $where[] = "i.category_id = ?";
        $params[] = (int)$categoryId;
    }
    if ($startDate !== '') {
        $where[] = "DATE(t.transaction_date) >= ?";
        $params[] = $startDate;
    }
    if ($endDate !== '') {
        $where[] = "DATE(t.transaction_date) <= ?";
        $params[] = $endDate;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "
        SELECT t.*, i.item_code, i.name as item_name, i.unit, c.name as category_name, u.full_name as officer_name
        FROM item_transactions t
        JOIN items i ON t.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        {$whereSql}
        ORDER BY t.transaction_date DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

    // Rekap Masuk & Keluar
    $totalTrx = count($reportData);
    $totalMasukUnits = 0;
    $totalKeluarUnits = 0;
    foreach ($reportData as $row) {
        if ($row['transaction_type'] === 'Masuk') $totalMasukUnits += $row['quantity'];
        if ($row['transaction_type'] === 'Keluar') $totalKeluarUnits += $row['quantity'];
    }
}

// 3. LAPORAN KONDISI BARANG
else {
    $sql = "
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
    ";
    $reportData = $pdo->query($sql)->fetchAll();
}

// Buat URL query string untuk tombol cetak dan ekspor
$queryParams = $_GET;
$queryString = http_build_query($queryParams);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Laporan Inventaris Barang</h1>
        <p>Rekapitulasi data aset, pergerakan transaksi, dan kondisi fisik inventaris</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('reports/print.php?' . $queryString) ?>" target="_blank" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Laporan</span>
        </a>
        <a href="<?= url('reports/export_csv.php?' . $queryString) ?>" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Ekspor CSV / Excel</span>
        </a>
    </div>
</div>

<!-- Tab Pemilih Jenis Laporan -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--color-border); padding-bottom: 12px; flex-wrap: wrap;">
    <a href="<?= url('reports/index.php?report_type=inventory') ?>" class="btn <?= $reportType === 'inventory' ? 'btn-primary' : 'btn-secondary' ?>">
        <span>Laporan Stok Barang</span>
    </a>
    <a href="<?= url('reports/index.php?report_type=transactions') ?>" class="btn <?= $reportType === 'transactions' ? 'btn-primary' : 'btn-secondary' ?>">
        <span>Laporan Barang Masuk & Keluar</span>
    </a>
    <a href="<?= url('reports/index.php?report_type=condition') ?>" class="btn <?= $reportType === 'condition' ? 'btn-primary' : 'btn-secondary' ?>">
        <span>Laporan Rekapitulasi Kondisi</span>
    </a>
</div>

<!-- Filter Laporan -->
<div class="filter-card">
    <form method="GET" action="" class="filter-grid">
        <input type="hidden" name="report_type" value="<?= e($reportType) ?>">

        <div>
            <label class="form-label" for="category_id">Kategori</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($reportType === 'inventory'): ?>
            <div>
                <label class="form-label" for="location_id">Lokasi</label>
                <select id="location_id" name="location_id" class="form-select">
                    <option value="">-- Semua Lokasi --</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['id'] ?>" <?= $locationId == $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label" for="condition">Kondisi</label>
                <select id="condition" name="condition" class="form-select">
                    <option value="">-- Semua Kondisi --</option>
                    <option value="Baik" <?= $condition === 'Baik' ? 'selected' : '' ?>>Baik</option>
                    <option value="Rusak Ringan" <?= $condition === 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                    <option value="Rusak Berat" <?= $condition === 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                </select>
            </div>
        <?php endif; ?>

        <div>
            <label class="form-label" for="start_date">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>

        <div>
            <label class="form-label" for="end_date">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Terapkan</span>
            </button>
            <a href="<?= url('reports/index.php?report_type=' . $reportType) ?>" class="btn btn-secondary" title="Reset">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </a>
        </div>
    </form>
</div>

<!-- Ringkasan Rekapitulasi Berdasarkan Jenis Laporan -->
<?php if ($reportType === 'inventory'): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Jenis Barang</span>
                <span class="stat-value"><?= formatNumber($totalItems) ?></span>
                <span class="stat-subtext">Record aset terdata</span>
            </div>
            <div class="stat-icon primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Jumlah Fisik</span>
                <span class="stat-value"><?= formatNumber($totalUnits) ?></span>
                <span class="stat-subtext">Unit keseluruhan</span>
            </div>
            <div class="stat-icon info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line><line x1="18" y1="20" x2="18" y2="10"></line></svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Kondisi Baik</span>
                <span class="stat-value" style="color: var(--color-success);"><?= formatNumber($baikUnits) ?></span>
                <span class="stat-subtext">Siap operasi</span>
            </div>
            <div class="stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Kondisi Rusak</span>
                <span class="stat-value" style="color: var(--color-danger);"><?= formatNumber($rusakRinganUnits + $rusakBeratUnits) ?></span>
                <span class="stat-subtext">Ringan: <?= $rusakRinganUnits ?> | Berat: <?= $rusakBeratUnits ?></span>
            </div>
            <div class="stat-icon danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
        </div>
    </div>

    <!-- Tabel Laporan Stok -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Tabel Laporan Stok Inventaris</h2>
            <span style="font-size: 12px; color: var(--color-text-subtle);">Dicetak pada: <?= date('d M Y, H:i') ?> WIB</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Jumlah</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                        <th>Tgl Pendataan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--color-text-subtle); padding: 32px;">Tidak ada data yang memenuhi kriteria filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="table-code"><?= e($r['item_code']) ?></span></td>
                                <td><strong><?= e($r['name']) ?></strong></td>
                                <td><?= e($r['category_name']) ?></td>
                                <td><?= e($r['location_name']) ?></td>
                                <td><strong><?= formatNumber($r['quantity']) ?></strong> <?= e($r['unit']) ?></td>
                                <td><?= getConditionBadge($r['item_condition']) ?></td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                                <td><?= formatDate($r['entry_date'], 'short') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportType === 'transactions'): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Transaksi</span>
                <span class="stat-value"><?= formatNumber($totalTrx) ?></span>
                <span class="stat-subtext">Catatan mutasi</span>
            </div>
            <div class="stat-icon info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Masuk (+IN)</span>
                <span class="stat-value" style="color: var(--color-success);">+<?= formatNumber($totalMasukUnits) ?></span>
                <span class="stat-subtext">Unit ditambahkan</span>
            </div>
            <div class="stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Keluar (-OUT)</span>
                <span class="stat-value" style="color: var(--color-warning);">-<?= formatNumber($totalKeluarUnits) ?></span>
                <span class="stat-subtext">Unit didistribusikan</span>
            </div>
            <div class="stat-icon warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
            </div>
        </div>
    </div>

    <!-- Tabel Laporan Transaksi -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Tabel Laporan Mutasi Barang</h2>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>No. Transaksi</th>
                        <th>Waktu</th>
                        <th>Kode & Nama Barang</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Stok Sblm &rarr; Ssdh</th>
                        <th>Pihak Terkait</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--color-text-subtle); padding: 32px;">Tidak ada transaksi yang tercatat.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="table-code" style="font-size: 11px;"><?= e($r['transaction_code']) ?></span></td>
                                <td><?= formatDate($r['transaction_date']) ?></td>
                                <td><strong><?= e($r['item_name']) ?></strong> <span class="table-code" style="font-size: 10px;"><?= e($r['item_code']) ?></span></td>
                                <td><?= getTransactionTypeBadge($r['transaction_type']) ?></td>
                                <td><strong><?= formatNumber($r['quantity']) ?></strong> <?= e($r['unit']) ?></td>
                                <td><?= formatNumber($r['stock_before']) ?> &rarr; <strong><?= formatNumber($r['stock_after']) ?></strong></td>
                                <td><?= e($r['recipient_or_supplier'] ?: '-') ?></td>
                                <td><?= e($r['officer_name'] ?? 'Petugas') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- Tabel Rekapitulasi Kondisi -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rekapitulasi Fisik Berdasarkan Kondisi Tiap Kategori</h2>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama Kategori</th>
                        <th>Jumlah Item</th>
                        <th>Total Saldo Fisik</th>
                        <th>Kondisi Baik</th>
                        <th>Kondisi Rusak Ringan</th>
                        <th>Kondisi Rusak Berat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $idx => $r): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= e($r['category_name']) ?></strong></td>
                            <td><span class="badge badge-emerald"><?= formatNumber($r['total_items']) ?> Item</span></td>
                            <td><strong><?= formatNumber($r['total_units']) ?></strong> Unit</td>
                            <td><span style="color: var(--color-success); font-weight: 700;"><?= formatNumber($r['qty_baik']) ?></span> Unit</td>
                            <td><span style="color: var(--color-warning); font-weight: 700;"><?= formatNumber($r['qty_rusak_ringan']) ?></span> Unit</td>
                            <td><span style="color: var(--color-danger); font-weight: 700;"><?= formatNumber($r['qty_rusak_berat']) ?></span> Unit</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

