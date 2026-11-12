<?php
/**
 * Detail Barang & Manajemen QR Code
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pdo = getDbConnection();
$code = trim($_GET['code'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!empty($code)) {
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name, l.name as location_name, l.building, l.floor, u.full_name as creator_name
        FROM items i
        JOIN categories c ON i.category_id = c.id
        JOIN locations l ON i.location_id = l.id
        LEFT JOIN users u ON i.created_by = u.id
        WHERE i.item_code = ?
    ");
    $stmt->execute([$code]);
    $item = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name, l.name as location_name, l.building, l.floor, u.full_name as creator_name
        FROM items i
        JOIN categories c ON i.category_id = c.id
        JOIN locations l ON i.location_id = l.id
        LEFT JOIN users u ON i.created_by = u.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
} else {
    $item = null;
}

if (!$item) {
    setFlash('danger', 'Data barang tidak ditemukan dalam sistem inventaris.');
    header('Location: ' . url('items/index.php'));
    exit;
}

$pageTitle = 'Detail: ' . $item['name'];
$breadcrumbTitle = $item['item_code'];

// Ambil Riwayat Transaksi Barang Ini
$trxStmt = $pdo->prepare("
    SELECT t.*, u.full_name as officer_name 
    FROM item_transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.item_id = ?
    ORDER BY t.id DESC
    LIMIT 10
");
$trxStmt->execute([$item['id']]);
$transactions = $trxStmt->fetchAll();

// Ambil Riwayat Aktivitas Barang Ini
$historyStmt = $pdo->prepare("
    SELECT * FROM item_histories 
    WHERE item_id = ? OR item_code = ?
    ORDER BY id DESC
    LIMIT 10
");
$historyStmt->execute([$item['id'], $item['item_code']]);
$histories = $historyStmt->fetchAll();

// URL yang dikodekan ke dalam QR Code
$qrContent = url('items/detail.php?code=' . urlencode($item['item_code']));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
            <span class="table-code" style="font-size: 14px;"><?= e($item['item_code']) ?></span>
            <?= getStatusBadge($item['status']) ?>
            <?= getConditionBadge($item['item_condition']) ?>
        </div>
        <h1><?= e($item['name']) ?></h1>
        <p>Kategori: <strong><?= e($item['category_name']) ?></strong> &bull; Lokasi: <strong><?= e($item['location_name']) ?></strong></p>
    </div>
    <div class="page-actions">
        <a href="<?= url('items/index.php') ?>" class="btn btn-secondary">
            &larr; Kembali
        </a>
        <a href="<?= url('items/print_qr.php?code=' . urlencode($item['item_code'])) ?>" target="_blank" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Label QR</span>
        </a>
        <?php if (canEdit()): ?>
            <a href="<?= url('transactions/in.php?item_id=' . $item['id']) ?>" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                <span>Barang Masuk</span>
            </a>
            <a href="<?= url('transactions/out.php?item_id=' . $item['id']) ?>" class="btn btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                <span>Barang Keluar</span>
            </a>
            <a href="<?= url('items/edit.php?id=' . $item['id']) ?>" class="btn btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <span>Edit</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Kolom Kiri: Spesifikasi & Detail -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Informasi Lengkap Barang</h2>
            </div>
            <div class="card-body">
                <table class="table" style="margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td style="width: 180px; font-weight: 600; color: var(--color-text-muted);">Kode Barang</td>
                            <td><span class="table-code" style="font-size: 13.5px;"><?= e($item['item_code']) ?></span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Nama Barang</td>
                            <td><strong style="font-size: 15px;"><?= e($item['name']) ?></strong></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Kategori</td>
                            <td><?= e($item['category_name']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Lokasi Penyimpanan</td>
                            <td>
                                <div><strong><?= e($item['location_name']) ?></strong></div>
                                <?php if (!empty($item['building']) || !empty($item['floor'])): ?>
                                    <div style="font-size: 11.5px; color: var(--color-text-subtle);">
                                        <?= e($item['building'] ?: '') ?> <?= !empty($item['floor']) ? ' &bull; ' . e($item['floor']) : '' ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Jumlah / Stok Fisik</td>
                            <td>
                                <span style="font-size: 18px; font-weight: 700; color: var(--color-primary);"><?= formatNumber($item['quantity']) ?></span>
                                <span style="font-size: 13px; color: var(--color-text-muted); font-weight: 500;"> <?= e($item['unit']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Kondisi Fisik</td>
                            <td><?= getConditionBadge($item['item_condition']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Status Ketersediaan</td>
                            <td><?= getStatusBadge($item['status']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Tanggal Pendataan</td>
                            <td><?= formatDate($item['entry_date'], 'short') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Keterangan Tambahan</td>
                            <td style="line-height: 1.6;"><?= nl2br(e($item['description'] ?: 'Tidak ada keterangan tambahan.')) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-text-muted);">Petugas Pencatat</td>
                            <td><?= e($item['creator_name'] ?: 'Sistem') ?> &bull; <span style="font-size: 11.5px; color: var(--color-text-subtle);"><?= formatDate($item['created_at']) ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Transaksi Terakhir -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                    <span>Riwayat Mutasi Masuk & Keluar Barang Ini</span>
                </h2>
                <a href="<?= url('transactions/index.php?item_id=' . $item['id']) ?>" class="btn btn-secondary btn-sm">Lihat Semua Transaksi</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Stok Sblm / Ssdh</th>
                            <th>Pihak Terkait</th>
                            <th>Tanggal & Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--color-text-subtle); padding: 20px;">Belum ada mutasi transaksi untuk barang ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><span class="table-code" style="font-size: 11px;"><?= e($t['transaction_code']) ?></span></td>
                                    <td><?= getTransactionTypeBadge($t['transaction_type']) ?></td>
                                    <td><strong><?= formatNumber($t['quantity']) ?></strong> <?= e($item['unit']) ?></td>
                                    <td><?= formatNumber($t['stock_before']) ?> &rarr; <strong><?= formatNumber($t['stock_after']) ?></strong></td>
                                    <td><?= e($t['recipient_or_supplier'] ?: '-') ?></td>
                                    <td>
                                        <div style="font-size: 12px;"><?= formatDate($t['transaction_date'], 'short') ?></div>
                                        <div style="font-size: 11px; color: var(--color-text-subtle);"><?= e($t['officer_name'] ?? 'Petugas') ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Card QR Code & Label Badge -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Kartu QR Code Inventaris</span>
                </h2>
            </div>
            <div class="card-body" style="text-align: center;">
                <div class="qr-preview-box" id="qrBadgeCard" style="width: 100%; border: 1px dashed var(--color-accent-soft); padding: 18px 12px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.5px; text-transform: uppercase;">
                        <?= e(getSetting('institution_name', 'Kelompok 6 MPSI')) ?>
                    </div>
                    
                    <!-- Wadah Render QR Code -->
                    <div class="qr-code-canvas">
                        <div class="qrcode-container" id="detailQrContainer" data-text="<?= e($qrContent) ?>" data-size="160"></div>
                    </div>

                    <div>
                        <div class="table-code" style="font-size: 13px;"><?= e($item['item_code']) ?></div>
                        <div style="font-weight: 700; font-size: 13.5px; margin-top: 4px;"><?= e($item['name']) ?></div>
                        <div style="font-size: 11.5px; color: var(--color-text-muted); margin-top: 2px;">
                            <?= e($item['location_name']) ?> &bull; Kondisi: <?= e($item['item_condition']) ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 18px;">
                    <button type="button" class="btn btn-primary" onclick="downloadBadgeAsImage('detailQrContainer', 'QR_<?= e($item['item_code']) ?>')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Unduh QR Code (PNG)</span>
                    </button>
                    <a href="<?= url('items/print_qr.php?code=' . urlencode($item['item_code'])) ?>" target="_blank" class="btn btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Cetak Label Fisik (Print Tag)</span>
                    </a>
                </div>

                <div style="font-size: 11px; color: var(--color-text-subtle); margin-top: 14px; line-height: 1.4;">
                    Pindai menggunakan kamera smartphone atau barcode reader untuk membuka halaman barang ini secara langsung.
                </div>
            </div>
        </div>

        <!-- Log Audit Perubahan Terakhir -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Riwayat Aktivitas Barang</h2>
            </div>
            <div class="card-body" style="padding: 12px 16px;">
                <?php if (empty($histories)): ?>
                    <div style="font-size: 12px; color: var(--color-text-subtle); text-align: center;">Belum ada log aktivitas.</div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($histories as $h): ?>
                            <div style="font-size: 12px; border-bottom: 1px solid var(--color-border); padding-bottom: 8px;">
                                <div style="font-weight: 600; color: var(--color-text-main);"><?= e($h['description']) ?></div>
                                <div style="font-size: 11px; color: var(--color-text-subtle); margin-top: 2px;">
                                    <strong><?= e($h['user_name']) ?></strong> &bull; <?= formatDate($h['created_at']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

