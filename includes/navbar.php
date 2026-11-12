<?php
/**
 * Top Navbar Component
 */
$user = currentUser();
$flash = getFlash();
$breadcrumbTitle = $breadcrumbTitle ?? ($pageTitle ?? 'Dashboard');
?>
<div class="app-main">
    <header class="app-navbar">
        <div class="navbar-left">
            <button type="button" class="btn-toggle-sidebar" aria-label="Toggle Sidebar">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="page-breadcrumb">
                <span class="breadcrumb-root">Inventaris</span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= e($breadcrumbTitle) ?></span>
            </div>
        </div>

        <div class="navbar-right">
            <a href="<?= url('scan/index.php') ?>" class="btn-nav-scan">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span>Pindai QR</span>
            </a>
            <a href="<?= url('auth/profile.php') ?>" class="btn btn-secondary btn-sm" title="Profil & Keamanan">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span><?= e(explode(' ', $user['full_name'] ?? 'Profil')[0]) ?></span>
            </a>
        </div>
    </header>

    <main class="app-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-auto-dismiss">
                <div style="flex-shrink:0;">
                    <?php if ($flash['type'] === 'success'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php elseif ($flash['type'] === 'danger'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?php elseif ($flash['type'] === 'warning'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <?= e($flash['message']) ?>
                </div>
            </div>
        <?php endif; ?>

