<?php
/**
 * Helper Functions & Utilities
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/session.php';

/**
 * Escape string untuk pencegahan XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Buat URL absolut aplikasi
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

/**
 * Buat URL untuk asset
 */
function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Format tanggal dan waktu ke bahasa Indonesia
 */
function formatDate(?string $datetime, string $format = 'd M Y, H:i'): string {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '-';

    $bulanIndo = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $d = date('d', $timestamp);
    $m = $bulanIndo[(int)date('m', $timestamp)] ?? date('M', $timestamp);
    $y = date('Y', $timestamp);
    $his = date('H:i', $timestamp);

    if ($format === 'short') {
        return "{$d} {$m} {$y}";
    }
    return "{$d} {$m} {$y}, {$his} WIB";
}

/**
 * Format angka ribuan
 */
function formatNumber($num): string {
    return number_format((float)$num, 0, ',', '.');
}

/**
 * Ambil nilai pengaturan dari database
 */
function getSetting(string $key, string $default = ''): string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // fallback jika tabel belum siap
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Badge status kondisi barang
 */
function getConditionBadge(string $condition): string {
    switch ($condition) {
        case 'Baik':
            return '<span class="badge badge-success"><span class="dot dot-success"></span>Baik</span>';
        case 'Rusak Ringan':
            return '<span class="badge badge-warning"><span class="dot dot-warning"></span>Rusak Ringan</span>';
        case 'Rusak Berat':
            return '<span class="badge badge-danger"><span class="dot dot-danger"></span>Rusak Berat</span>';
        default:
            return '<span class="badge badge-secondary">' . e($condition) . '</span>';
    }
}

/**
 * Badge status ketersediaan barang
 */
function getStatusBadge(string $status): string {
    switch ($status) {
        case 'Tersedia':
            return '<span class="badge badge-emerald">Tersedia</span>';
        case 'Dipinjam':
            return '<span class="badge badge-blue">Dipinjam</span>';
        case 'Habis':
            return '<span class="badge badge-danger">Habis</span>';
        case 'Dalam Perbaikan':
            return '<span class="badge badge-warning">Dalam Perbaikan</span>';
        case 'Afkir':
            return '<span class="badge badge-gray">Afkir</span>';
        default:
            return '<span class="badge badge-secondary">' . e($status) . '</span>';
    }
}

/**
 * Badge jenis transaksi
 */
function getTransactionTypeBadge(string $type): string {
    if ($type === 'Masuk') {
        return '<span class="badge badge-success"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg> Masuk</span>';
    } else {
        return '<span class="badge badge-warning"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg> Keluar</span>';
    }
}

/**
 * Badge role pengguna
 */
function getRoleBadge(string $role): string {
    switch ($role) {
        case 'admin':
            return '<span class="badge badge-admin">Admin</span>';
        case 'petugas':
            return '<span class="badge badge-petugas">Petugas Inventaris</span>';
        case 'peninjau':
            return '<span class="badge badge-peninjau">Peninjau</span>';
        default:
            return '<span class="badge badge-secondary">' . e($role) . '</span>';
    }
}

/**
 * Generate kode barang unik otomatis (Format: BRG-YYYY-XXXX)
 */
function generateItemCode(?string $prefix = null): string {
    $pdo = getDbConnection();
    if ($prefix === null) {
        $prefix = getSetting('item_code_prefix', 'BRG');
    }
    $year = date('Y');
    $likePattern = "{$prefix}-{$year}-%";

    $stmt = $pdo->prepare("SELECT item_code FROM items WHERE item_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$likePattern]);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode) {
        $parts = explode('-', $lastCode);
        $lastNumber = (int)end($parts);
        $nextNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '0001';
    }

    return "{$prefix}-{$year}-{$nextNumber}";
}

/**
 * Generate kode transaksi otomatis (Format: TRX-IN-YYYYMM-XXXX / TRX-OUT-YYYYMM-XXXX)
 */
function generateTransactionCode(string $type = 'Masuk'): string {
    $pdo = getDbConnection();
    $prefix = ($type === 'Masuk') ? 'TRX-IN' : 'TRX-OUT';
    $yearMonth = date('Ym');
    $likePattern = "{$prefix}-{$yearMonth}-%";

    $stmt = $pdo->prepare("SELECT transaction_code FROM item_transactions WHERE transaction_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$likePattern]);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode) {
        $parts = explode('-', $lastCode);
        $lastNumber = (int)end($parts);
        $nextNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '0001';
    }

    return "{$prefix}-{$yearMonth}-{$nextNumber}";
}

/**
 * Catat riwayat aktivitas perubahan barang
 */
function logActivity(
    string $actionType,
    ?int $itemId,
    string $itemCode,
    string $itemName,
    string $description,
    $oldValues = null,
    $newValues = null
): bool {
    try {
        $pdo = getDbConnection();
        $user = currentUser();
        $userId = $user['id'] ?? null;
        $userName = $user['full_name'] ?? ($user['username'] ?? 'Sistem');
        $userRole = $user['role_name'] ?? 'system';

        $oldJson = $oldValues !== null ? (is_string($oldValues) ? $oldValues : json_encode($oldValues, JSON_UNESCAPED_UNICODE)) : null;
        $newJson = $newValues !== null ? (is_string($newValues) ? $newValues : json_encode($newValues, JSON_UNESCAPED_UNICODE)) : null;

        $stmt = $pdo->prepare("
            INSERT INTO item_histories 
            (action_type, item_id, item_code, item_name, user_id, user_name, user_role, description, old_values, new_values, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $actionType,
            $itemId,
            $itemCode,
            $itemName,
            $userId,
            $userName,
            $userRole,
            $description,
            $oldJson,
            $newJson
        ]);
    } catch (Exception $e) {
        error_log("Gagal mencatat log riwayat: " . $e->getMessage());
        return false;
    }
}

/**
 * Render pagination HTML sederhana dan responsif
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl, array $queryParams = []): string {
    if ($totalPages <= 1) return '';

    $html = '<nav class="pagination-container" aria-label="Navigasi Halaman"><ul class="pagination">';

    // Tombol Previous
    if ($currentPage > 1) {
        $queryParams['page'] = $currentPage - 1;
        $prevUrl = $baseUrl . '?' . http_build_query($queryParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . e($prevUrl) . '">&larr; Sebelumnya</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&larr; Sebelumnya</span></li>';
    }

    // Nomor Halaman
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $queryParams['page'] = 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl . '?' . http_build_query($queryParams)) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $queryParams['page'] = $i;
        $url = $baseUrl . '?' . http_build_query($queryParams);
        $activeClass = ($i === $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="' . e($url) . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $queryParams['page'] = $totalPages;
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl . '?' . http_build_query($queryParams)) . '">' . $totalPages . '</a></li>';
    }

    // Tombol Next
    if ($currentPage < $totalPages) {
        $queryParams['page'] = $currentPage + 1;
        $nextUrl = $baseUrl . '?' . http_build_query($queryParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . e($nextUrl) . '">Selanjutnya &rarr;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Selanjutnya &rarr;</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

