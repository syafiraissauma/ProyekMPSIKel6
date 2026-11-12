<?php
/**
 * Sidebar Navigation Component
 */
$currentUri = $_SERVER['SCRIPT_NAME'] ?? '';
$user = currentUser();
$roleName = currentRole();

function isMenuActive($pattern, $currentUri) {
    return (strpos($currentUri, $pattern) !== false) ? 'active' : '';
}
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="brand-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
                <line x1="7" y1="7" x2="7.01" y2="7"></line>
                <line x1="17" y1="7" x2="17.01" y2="7"></line>
                <line x1="7" y1="17" x2="7.01" y2="17"></line>
                <line x1="17" y1="17" x2="17.01" y2="17"></line>
            </svg>
        </div>
        <div class="brand-text">
            <h2><?= e(getSetting('app_name', 'SIM-INVENTARIS')) ?></h2>
            <p>Kelompok 6 MPSI</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <ul class="nav-list">
            <li>
                <a href="<?= url('index.php') ?>" class="nav-link <?= (basename($currentUri) === 'index.php' && strpos($currentUri, '/items/') === false && strpos($currentUri, '/categories/') === false && strpos($currentUri, '/locations/') === false && strpos($currentUri, '/transactions/') === false && strpos($currentUri, '/reports/') === false && strpos($currentUri, '/history/') === false && strpos($currentUri, '/users/') === false && strpos($currentUri, '/settings/') === false && strpos($currentUri, '/scan/') === false) ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= url('items/index.php') ?>" class="nav-link <?= isMenuActive('/items/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span>Data Barang</span>
                </a>
            </li>
            <li>
                <a href="<?= url('scan/index.php') ?>" class="nav-link <?= isMenuActive('/scan/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <span>Scan QR Code</span>
                </a>
            </li>
            <li>
                <a href="<?= url('transactions/index.php') ?>" class="nav-link <?= isMenuActive('/transactions/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                    <span>Barang Masuk & Keluar</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Master Data</div>
        <ul class="nav-list">
            <li>
                <a href="<?= url('categories/index.php') ?>" class="nav-link <?= isMenuActive('/categories/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    <span>Kategori</span>
                </a>
            </li>
            <li>
                <a href="<?= url('locations/index.php') ?>" class="nav-link <?= isMenuActive('/locations/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>Lokasi</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Laporan & Audit</div>
        <ul class="nav-list">
            <li>
                <a href="<?= url('reports/index.php') ?>" class="nav-link <?= isMenuActive('/reports/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Laporan</span>
                </a>
            </li>
            <li>
                <a href="<?= url('history/index.php') ?>" class="nav-link <?= isMenuActive('/history/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    <span>Riwayat Aktivitas</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Sistem</div>
        <ul class="nav-list">
            <?php if (isAdmin()): ?>
            <li>
                <a href="<?= url('users/index.php') ?>" class="nav-link <?= isMenuActive('/users/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Manajemen Pengguna</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="<?= url('settings/index.php') ?>" class="nav-link <?= isMenuActive('/settings/', $currentUri) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Pengaturan</span>
                </a>
            </li>
            <li>
                <a href="<?= url('auth/logout.php') ?>" class="nav-link" onclick="return confirm('Apakah Anda yakin ingin keluar dari aplikasi?');" style="color: #b91c1c;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Keluar</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= e($user['full_name'] ?? 'Pengguna') ?></div>
                <div class="user-role-label"><?= getRoleBadge($roleName) ?></div>
            </div>
        </div>
    </div>
</aside>

