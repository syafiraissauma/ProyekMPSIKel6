<?php
/**
 * Session & Authentication Management
 */

require_once __DIR__ . '/app.php';

// Konfigurasi Keamanan Session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Generate atau dapatkan CSRF Token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output field input CSRF tersembunyi
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verifikasi CSRF Token
 */
function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set pesan notifikasi flash (success, danger, warning, info)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Ambil dan hapus pesan flash
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Cek apakah user telah login
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user']);
}

/**
 * Dapatkan data user saat ini
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Dapatkan ID user saat ini
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Dapatkan role user saat ini (admin, petugas, peninjau)
 */
function currentRole(): string {
    return $_SESSION['user']['role_name'] ?? 'guest';
}

/**
 * Cek apakah role user saat ini cocok dengan daftar role yang diizinkan
 */
function hasRole($roles): bool {
    if (!isLoggedIn()) return false;
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array(currentRole(), $roles, true);
}

/**
 * Apakah pengguna memiliki hak menambah/mengubah data (Admin & Petugas)
 */
function canEdit(): bool {
    return hasRole(['admin', 'petugas']);
}

/**
 * Apakah pengguna adalah Administrator
 */
function isAdmin(): bool {
    return hasRole('admin');
}

/**
 * Wajibkan login untuk mengakses halaman
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Wajibkan role tertentu untuk mengakses halaman
 */
function requireRole($roles): void {
    requireAuth();
    if (!hasRole($roles)) {
        http_response_code(403);
        setFlash('danger', 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

