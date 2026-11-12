<?php
/**
 * Riwayat Perubahan Barang & Audit Trail
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Riwayat Aktivitas & Perubahan';
$breadcrumbTitle = 'Riwayat Aktivitas';
$pdo = getDbConnection();

// Parameter Filter
$actionType = trim($_GET['action_type'] ?? '');
$search = trim($_GET['search'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];

if ($actionType !== '') {
    $conditions[] = "h.action_type = ?";
    $params[] = $actionType;
}

if ($search !== '') {
    $conditions[] = "(h.item_code LIKE ? OR h.item_name LIKE ? OR h.user_name LIKE ? OR h.description LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($startDate !== '') {
    $conditions[] = "DATE(h.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate !== '') {
    $conditions[] = "DATE(h.created_at) <= ?";
    $params[] = $endDate;
}

$whereSql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Hitung total untuk pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM item_histories h {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Query riwayat aktivitas
$sql = "
    SELECT h.* 
    FROM item_histories h
    {$whereSql}
    ORDER BY h.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$histories = $stmt->fetchAll();

// Daftar tipe aksi untuk dropdown
$actionTypes = [
    'TAMBAH_BARANG' => 'Registrasi Barang Baru',
    'UBAH_BARANG' => 'Perubahan Data Barang',
    'UBAH_KONDISI' => 'Perubahan Kondisi Fisik',
    'UBAH_LOKASI' => 'Pemindahan Lokasi',
    'UBAH_STATUS' => 'Perubahan Status',
    'BARANG_MASUK' => 'Mutasi Barang Masuk (+IN)',
    'BARANG_KELUAR' => 'Mutasi Barang Keluar (-OUT)',
    'HAPUS_BARANG' => 'Penghapusan Barang'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1>Riwayat Aktivitas & Perubahan Inventaris</h1>
        <p>Jejak audit menyeluruh mencakup sebelum dan sesudah setiap perubahan data aset</p>
    </div>
</div>

<!-- Filter Riwayat -->
<div class="filter-card">
    <form method="GET" action="" class="filter-grid">
        <div>
            <label class="form-label" for="search">Pencarian</label>
            <input type="text" id="search" name="search" class="form-control" placeholder="Nama barang, kode, petugas..." value="<?= e($search) ?>">
        </div>

        <div>
            <label class="form-label" for="action_type">Jenis Aktivitas</label>
            <select id="action_type" name="action_type" class="form-select">
                <option value="">-- Semua Jenis Aktivitas --</option>
                <?php foreach ($actionTypes as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $actionType === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

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
                <span>Filter</span>
            </button>
            <?php if ($actionType !== '' || $search !== '' || $startDate !== '' || $endDate !== ''): ?>
                <a href="<?= url('history/index.php') ?>" class="btn btn-secondary" title="Reset Filter">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabel Riwayat -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Jejak Audit Log (Menampilkan <?= count($histories) ?> dari <?= formatNumber($totalRows) ?> aktivitas)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 150px;">Waktu Aktivitas</th>
                    <th>Aksi</th>
                    <th>Barang Terkait</th>
                    <th>Deskripsi Perubahan</th>
                    <th>Pengguna / Petugas</th>
                    <th style="width: 100px; text-align: center;">Detail Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($histories)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--color-text-subtle); padding: 32px;">Belum ada riwayat aktivitas yang sesuai dengan filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($histories as $h): ?>
                        <tr>
                            <td style="font-size: 12px; color: var(--color-text-muted); white-space: nowrap;"><?= formatDate($h['created_at']) ?></td>
                            <td>
                                <?php if (strpos($h['action_type'], 'MASUK') !== false): ?>
                                    <span class="badge badge-success">+ Masuk</span>
                                <?php elseif (strpos($h['action_type'], 'KELUAR') !== false): ?>
                                    <span class="badge badge-warning">- Keluar</span>
                                <?php elseif ($h['action_type'] === 'TAMBAH_BARANG'): ?>
                                    <span class="badge badge-emerald">Tambah</span>
                                <?php elseif ($h['action_type'] === 'HAPUS_BARANG'): ?>
                                    <span class="badge badge-danger">Hapus</span>
                                <?php elseif ($h['action_type'] === 'UBAH_KONDISI'): ?>
                                    <span class="badge badge-warning">Kondisi</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?= e($h['action_type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><strong><?= e($h['item_name']) ?></strong></div>
                                <span class="table-code" style="font-size: 11px;"><?= e($h['item_code']) ?></span>
                            </td>
                            <td style="font-size: 12.5px; line-height: 1.4;"><?= e($h['description']) ?></td>
                            <td>
                                <div style="font-weight: 600; font-size: 12.5px;"><?= e($h['user_name']) ?></div>
                                <div style="font-size: 11px;"><?= getRoleBadge($h['user_role']) ?></div>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($h['old_values']) || !empty($h['new_values'])): ?>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick='showDiffModal(<?= json_encode($h, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        Lihat Diff
                                    </button>
                                <?php else: ?>
                                    <span style="color: var(--color-text-subtle); font-size: 11px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $totalPages, url('history/index.php'), [
    'action_type' => $actionType,
    'search' => $search,
    'start_date' => $startDate,
    'end_date' => $endDate
]) ?>

<!-- Modal Perbandingan Nilai Sebelum vs Sesudah -->
<div id="diffModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 650px;">
        <div class="modal-header">
            <h3 class="modal-title" id="diffModalTitle">Detail Perubahan Data (Sebelum & Sesudah)</h3>
            <button type="button" class="btn-close-modal" onclick="closeModal('diffModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="diffDescription" style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;"></div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Sebelum -->
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 12px;">
                    <div style="font-size: 12px; font-weight: 700; color: #991b1b; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        <span>Data Sebelum (Old Values)</span>
                    </div>
                    <pre id="diffOldValues" style="font-family: monospace; font-size: 11.5px; white-space: pre-wrap; word-break: break-all; margin: 0; color: #7f1d1d;"></pre>
                </div>

                <!-- Sesudah -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 12px;">
                    <div style="font-size: 12px; font-weight: 700; color: #166534; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        <span>Data Sesudah (New Values)</span>
                    </div>
                    <pre id="diffNewValues" style="font-family: monospace; font-size: 11.5px; white-space: pre-wrap; word-break: break-all; margin: 0; color: #14532d;"></pre>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('diffModal')">Tutup</button>
        </div>
    </div>
</div>

<script>
function showDiffModal(log) {
    document.getElementById('diffModalTitle').textContent = `Audit Log: ${log.item_code} - ${log.action_type}`;
    document.getElementById('diffDescription').textContent = `${log.description} (Dilakukan oleh: ${log.user_name} pada ${log.created_at})`;
    
    // Format JSON
    let oldText = 'Tidak ada data sebelum (Registrasi Baru)';
    let newText = 'Tidak ada data sesudah (Data Dihapus)';

    if (log.old_values) {
        try {
            const obj = JSON.parse(log.old_values);
            oldText = JSON.stringify(obj, null, 2);
        } catch (e) {
            oldText = log.old_values;
        }
    }

    if (log.new_values) {
        try {
            const obj = JSON.parse(log.new_values);
            newText = JSON.stringify(obj, null, 2);
        } catch (e) {
            newText = log.new_values;
        }
    }

    document.getElementById('diffOldValues').textContent = oldText;
    document.getElementById('diffNewValues').textContent = newText;
    openModal('diffModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

