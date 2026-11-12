<?php
/**
 * Logout Handler
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

if (isLoggedIn()) {
    $user = currentUser();
    // Catat riwayat logout opsional atau unset session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

// Mulai sesi baru untuk flash message
session_start();
setFlash('info', 'Anda telah berhasil keluar dari sistem inventaris.');
header('Location: ' . BASE_URL . '/auth/login.php');
exit;

