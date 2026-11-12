<?php
/**
 * Data Barang - Daftar Inventaris dengan Pencarian, Filter, dan Pagination
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Data Barang Inventaris';
$breadcrumbTitle = 'Data Barang';
$pdo = getDbConnection();

// Parameter Pencarian dan Filter
$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category_id'] ?? '');
$locationId = trim($_GET['location_id'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Bangun Query Berdasarkan Filter
$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "(i.item_code LIKE ? OR i.name LIKE ? OR i.description LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId !== '') {
    $conditions[] = "i.category_id = ?";
    $params[] = (int)$categoryId;
}

if ($locationId !== '') {
    $conditions[] = "i.location_id = ?";
    $params[] = (int)$locationId;
}

if ($condition !== '') {
    $conditions[] = "i.item_condition = ?";
    $params[] = $condition;
}

if ($status !== '') {
    $conditions[] = "i.status = ?";
    $params[] = $status;
}

$whereSql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Hitung Total Data untuk Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Query Data Barang dengan Limit & Offset
$sql = "
    SELECT i.*, c.name as category_name, l.name as location_name 
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.location_id = l.id
    {$whereSql}
    ORDER BY i.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Ambil Master Kategori & Lokasi untuk Dropdown Filter
$categoriesList = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$locationsList = $pdo->query("SELECT id, name FROM locations ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Data Barang Inventaris</h1>
        <p>Kelola seluruh katalog aset logistik dengan identifikasi kode unik dan QR Code</p>
    </div>
    <div class="page-actions">
        <?php if (canEdit()): ?>
            <a href="<?= url('items/create.php') ?>" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Tambah Barang Baru</span>
            </a>
        <?php endif; ?>
        <a href="<?= url('scan/index.php') ?>" class="btn btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
            <span>Pindai QR</span>
        </a>
    </div>
</div>

<!-- Filter dan Pencarian -->
<div class="filter-card">
    <form method="GET" action="" class="filter-grid">
        <div>
            <label class="form-label" for="search">Pencarian</label>
            <input type="text" id="search" name="search" class="form-control" placeholder="Cari kode atau nama barang..." value="<?= e($search) ?>">
        </div>

        <div>
            <label class="form-label" for="category_id">Kategori</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($categoriesList as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label" for="location_id">Lokasi</label>
            <select id="location_id" name="location_id" class="form-select">
                <option value="">-- Semua Lokasi --</option>
                <?php foreach ($locationsList as $loc): ?>
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

        <div>
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">-- Semua Status --</option>
                <option value="Tersedia" <?= $status === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                <option value="Dipinjam" <?= $status === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                <option value="Habis" <?= $status === 'Habis' ? 'selected' : '' ?>>Habis</option>
                <option value="Dalam Perbaikan" <?= $status === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                <option value="Afkir" <?= $status === 'Afkir' ? 'selected' : '' ?>>Afkir</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Filter</span>
            </button>
            <?php if ($search !== '' || $categoryId !== '' || $locationId !== '' || $condition !== '' || $status !== ''): ?>
                <a href="<?= url('items/index.php') ?>" class="btn btn-secondary" title="Reset Filter">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabel Data Barang -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Katalog Barang (Menampilkan <?= count($items) ?> dari <?= formatNumber($totalRows) ?> total barang)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 130px;">Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Lokasi Simpan</th>
                    <th>Jumlah</th>
                    <th>Kondisi</th>
                    <th>Status</th>
                    <th>Tgl Pendataan</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--color-text-subtle); padding: 32px;">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.5;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <div>Tidak ada data barang yang sesuai dengan kriteria filter.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <a href="<?= url('items/detail.php?code=' . urlencode($item['item_code'])) ?>" class="table-code">
                                    <?= e($item['item_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    <a href="<?= url('items/detail.php?code=' . urlencode($item['item_code'])) ?>" style="color: var(--color-text-main);">
                                        <?= e($item['name']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($item['description'])): ?>
                                    <div style="font-size: 11.5px; color: var(--color-text-subtle); max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= e($item['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['category_name']) ?></td>
                            <td><?= e($item['location_name']) ?></td>
                            <td><strong><?= formatNumber($item['quantity']) ?></strong> <span style="font-size: 11px; color: var(--color-text-subtle);"><?= e($item['unit']) ?></span></td>
                            <td><?= getConditionBadge($item['item_condition']) ?></td>
                            <td><?= getStatusBadge($item['status']) ?></td>
                            <td style="font-size: 12px; color: var(--color-text-muted);"><?= formatDate($item['entry_date'], 'short') ?></td>
                            <td>
                                <div class="table-actions" style="justify-content: center;">
                                    <a href="<?= url('items/detail.php?code=' . urlencode($item['item_code'])) ?>" class="btn btn-secondary btn-sm" title="Lihat Detail & QR">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="<?= url('items/print_qr.php?code=' . urlencode($item['item_code'])) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Cetak Label QR">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                    </a>
                                    <?php if (canEdit()): ?>
                                        <a href="<?= url('items/edit.php?id=' . $item['id']) ?>" class="btn btn-secondary btn-sm" title="Edit Data">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </a>
                                        <form method="POST" action="<?= url('items/delete.php') ?>" style="display:inline;" onsubmit="return confirmDelete(event, 'Yakin ingin menghapus barang <?= e($item['name']) ?> (<?= e($item['item_code']) ?>)?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, url('items/index.php'), [
    'search' => $search,
    'category_id' => $categoryId,
    'location_id' => $locationId,
    'condition' => $condition,
    'status' => $status
]) ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

