<?php
/**
 * Dashboard Utama
 * Menampilkan ringkasan statistik, grafik, data barang terbaru, dan aktivitas terbaru.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

requireAuth();

$pageTitle = 'Dashboard Inventaris';
$breadcrumbTitle = 'Dashboard';
$pdo = getDbConnection();

// 1. Ambil Statistik Ringkasan
// Total seluruh barang (jumlah record item & total unit fisik)
$statsTotalItems = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$statsTotalUnits = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM items")->fetchColumn();

// Kondisi Barang
$statsKondisiBaik = $pdo->query("SELECT COUNT(*) FROM items WHERE item_condition = 'Baik'")->fetchColumn();
$statsKondisiRusakRingan = $pdo->query("SELECT COUNT(*) FROM items WHERE item_condition = 'Rusak Ringan'")->fetchColumn();
$statsKondisiRusakBerat = $pdo->query("SELECT COUNT(*) FROM items WHERE item_condition = 'Rusak Berat'")->fetchColumn();

// Total Kategori & Lokasi
$statsTotalKategori = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$statsTotalLokasi = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();

// Total Mutasi Transaksi
$statsTotalMasuk = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM item_transactions WHERE transaction_type = 'Masuk'")->fetchColumn();
$statsTotalKeluar = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM item_transactions WHERE transaction_type = 'Keluar'")->fetchColumn();

// 2. Data untuk Grafik Distribusi Kategori
$catQuery = $pdo->query("
    SELECT c.name, COUNT(i.id) as total_items, COALESCE(SUM(i.quantity), 0) as total_qty
    FROM categories c
    LEFT JOIN items i ON c.id = i.category_id
    GROUP BY c.id, c.name
    ORDER BY total_items DESC
");
$categoriesChart = $catQuery->fetchAll();
$catLabels = [];
$catItemCounts = [];
$catQtyCounts = [];
foreach ($categoriesChart as $cat) {
    $catLabels[] = $cat['name'];
    $catItemCounts[] = (int)$cat['total_items'];
    $catQtyCounts[] = (int)$cat['total_qty'];
}

// 3. Daftar Barang Terbaru (5 item terakhir)
$latestItemsStmt = $pdo->query("
    SELECT i.*, c.name as category_name, l.name as location_name 
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.location_id = l.id
    ORDER BY i.id DESC
    LIMIT 6
");
$latestItems = $latestItemsStmt->fetchAll();

// 4. Riwayat Aktivitas Terbaru (6 aktivitas terakhir)
$recentHistoryStmt = $pdo->query("
    SELECT * FROM item_histories 
    ORDER BY id DESC 
    LIMIT 6
");
$recentHistories = $recentHistoryStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Halaman -->
<div class="page-header">
    <div class="page-title">
        <h1>Dashboard Inventaris</h1>
        <p>Ringkasan status ketersediaan barang dan pergerakan aset logistik</p>
    </div>
    <div class="page-actions">
        <?php if (canEdit()): ?>
            <a href="<?= url('items/create.php') ?>" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Tambah Barang Baru</span>
            </a>
            <a href="<?= url('transactions/index.php') ?>" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                <span>Catat Transaksi</span>
            </a>
        <?php endif; ?>
        <a href="<?= url('reports/index.php') ?>" class="btn btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Laporan</span>
        </a>
    </div>
</div>

<!-- Kartu Metrik Utama -->
<div class="stats-grid">
    <!-- Total Barang -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Jenis Barang</span>
            <span class="stat-value"><?= formatNumber($statsTotalItems) ?></span>
            <span class="stat-subtext">Total <?= formatNumber($statsTotalUnits) ?> unit fisik tercatat</span>
        </div>
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </div>
    </div>

    <!-- Kondisi Baik -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Kondisi Baik</span>
            <span class="stat-value" style="color: var(--color-success);"><?= formatNumber($statsKondisiBaik) ?></span>
            <span class="stat-subtext">Siap digunakan secara normal</span>
        </div>
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
    </div>

    <!-- Kondisi Rusak Ringan -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Rusak Ringan</span>
            <span class="stat-value" style="color: var(--color-warning);"><?= formatNumber($statsKondisiRusakRingan) ?></span>
            <span class="stat-subtext">Membutuhkan perbaikan ringan</span>
        </div>
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
    </div>

    <!-- Kondisi Rusak Berat -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Rusak Berat / Afkir</span>
            <span class="stat-value" style="color: var(--color-danger);"><?= formatNumber($statsKondisiRusakBerat) ?></span>
            <span class="stat-subtext">Tidak layak pakai / siap afkir</span>
        </div>
        <div class="stat-icon danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
        </div>
    </div>

    <!-- Total Kategori -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Kategori Aset</span>
            <span class="stat-value"><?= formatNumber($statsTotalKategori) ?></span>
            <span class="stat-subtext">Pengelompokan barang</span>
        </div>
        <div class="stat-icon info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        </div>
    </div>

    <!-- Total Lokasi -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Titik Lokasi Simpan</span>
            <span class="stat-value"><?= formatNumber($statsTotalLokasi) ?></span>
            <span class="stat-subtext">Gudang & ruangan kantor</span>
        </div>
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
        </div>
    </div>
</div>

<!-- Section Grafik Visualisasi -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Grafik Distribusi Kategori -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>Distribusi Stok per Kategori</span>
            </h2>
            <span style="font-size: 12px; color: var(--color-text-subtle);">Data Berdasarkan Jumlah Fisik (Unit)</span>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafik Donut Kondisi Barang -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a10 10 0 0 1 10 10"></path></svg>
                <span>Kondisi Fisik Barang</span>
            </h2>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 210px; width: 100%;">
                <canvas id="conditionChart"></canvas>
            </div>
            <div style="display: flex; justify-content: space-around; margin-top: 15px; font-size: 11.5px; text-align: center;">
                <div>
                    <span style="display:block; font-weight:700; color: #1e7e48;"><?= $statsKondisiBaik ?></span>
                    <span style="color: var(--color-text-muted);">Baik</span>
                </div>
                <div>
                    <span style="display:block; font-weight:700; color: #b45309;"><?= $statsKondisiRusakRingan ?></span>
                    <span style="color: var(--color-text-muted);">Rusak Ringan</span>
                </div>
                <div>
                    <span style="display:block; font-weight:700; color: #b91c1c;"><?= $statsKondisiRusakBerat ?></span>
                    <span style="color: var(--color-text-muted);">Rusak Berat</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Data Barang Terbaru & Riwayat Aktivitas -->
<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 24px;">
    <!-- Tabel Barang Terbaru -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                <span>Daftar Barang Terbaru</span>
            </h2>
            <a href="<?= url('items/index.php') ?>" class="btn btn-secondary btn-sm">Lihat Semua &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Jumlah</th>
                        <th>Kondisi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($latestItems)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--color-text-subtle); padding: 24px;">Belum ada data barang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($latestItems as $item): ?>
                            <tr>
                                <td><span class="table-code"><?= e($item['item_code']) ?></span></td>
                                <td>
                                    <div style="font-weight: 600;"><?= e($item['name']) ?></div>
                                    <div style="font-size: 11.5px; color: var(--color-text-subtle);"><?= e($item['location_name']) ?></div>
                                </td>
                                <td><?= e($item['category_name']) ?></td>
                                <td><strong><?= formatNumber($item['quantity']) ?></strong> <span style="font-size: 11px; color: var(--color-text-subtle);"><?= e($item['unit']) ?></span></td>
                                <td><?= getConditionBadge($item['item_condition']) ?></td>
                                <td>
                                    <a href="<?= url('items/detail.php?code=' . urlencode($item['item_code'])) ?>" class="btn btn-secondary btn-sm" title="Lihat Detail & QR">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Aktivitas Terbaru -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                <span>Aktivitas Terbaru</span>
            </h2>
            <a href="<?= url('history/index.php') ?>" class="btn btn-secondary btn-sm">Semua Log &rarr;</a>
        </div>
        <div class="card-body" style="padding: 12px 16px;">
            <?php if (empty($recentHistories)): ?>
                <div style="text-align: center; color: var(--color-text-subtle); padding: 20px;">Belum ada riwayat aktivitas.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($recentHistories as $act): ?>
                        <div style="display: flex; gap: 12px; align-items: flex-start; padding-bottom: 12px; border-bottom: 1px solid var(--color-border);">
                            <div style="width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--color-bg-subtle); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px;">
                                <?php if (strpos($act['action_type'], 'MASUK') !== false): ?>
                                    <span style="color: var(--color-success); font-weight: bold;">+IN</span>
                                <?php elseif (strpos($act['action_type'], 'KELUAR') !== false): ?>
                                    <span style="color: var(--color-warning); font-weight: bold;">-OUT</span>
                                <?php else: ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 12.5px; font-weight: 600; color: var(--color-text-main); margin-bottom: 2px;">
                                    <?= e($act['item_name']) ?> <span class="table-code" style="font-size: 10px; padding: 1px 4px;"><?= e($act['item_code']) ?></span>
                                </div>
                                <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.35;"><?= e($act['description']) ?></div>
                                <div style="font-size: 11px; color: var(--color-text-subtle); margin-top: 4px;">
                                    <strong><?= e($act['user_name']) ?></strong> &bull; <?= formatDate($act['created_at']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inisialisasi Script Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart Kategori
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat && typeof Chart !== 'undefined') {
        new Chart(ctxCat, {
            type: 'bar',
            data: {
                labels: <?= json_encode($catLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Jumlah Stok Fisik (Unit)',
                    data: <?= json_encode($catQtyCounts) ?>,
                    backgroundColor: 'rgba(45, 90, 67, 0.75)',
                    hoverBackgroundColor: 'rgba(35, 72, 53, 0.9)',
                    borderColor: '#2d5a43',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { family: 'Inter', size: 11 } },
                        grid: { color: '#f1f5f3' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Chart Kondisi Donut
    const ctxCond = document.getElementById('conditionChart');
    if (ctxCond && typeof Chart !== 'undefined') {
        new Chart(ctxCond, {
            type: 'doughnut',
            data: {
                labels: ['Baik', 'Rusak Ringan', 'Rusak Berat'],
                datasets: [{
                    data: [<?= (int)$statsKondisiBaik ?>, <?= (int)$statsKondisiRusakRingan ?>, <?= (int)$statsKondisiRusakBerat ?>],
                    backgroundColor: ['#2d5a43', '#e09f3e', '#b91c1c'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'Inter', size: 11 } }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

