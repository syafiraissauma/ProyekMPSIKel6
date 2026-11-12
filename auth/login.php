<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Jika sudah login, redirect langsung ke Dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$usernameInput = '';

// Quick Demo Login Handler (untuk mempermudah dosen/penguji/user)
if (isset($_GET['quick_demo']) && in_array($_GET['quick_demo'], ['admin', 'petugas', 'peninjau'])) {
    $demoRole = $_GET['quick_demo'];
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.display_name as role_display 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.username = ? AND u.status = 'active'
    ");
    $stmt->execute([$demoRole]);
    $demoUser = $stmt->fetch();

    if ($demoUser) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $demoUser['id'];
        $_SESSION['user'] = [
            'id' => $demoUser['id'],
            'username' => $demoUser['username'],
            'full_name' => $demoUser['full_name'],
            'email' => $demoUser['email'],
            'role_name' => $demoUser['role_name'],
            'role_display' => $demoUser['role_display']
        ];
        setFlash('success', "Selamat datang, {$demoUser['full_name']}! Anda masuk sebagai {$demoUser['role_display']}.");
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Proses Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Validasi sesi keamanan (CSRF) gagal. Silakan coba kembali.';
    } else {
        $usernameInput = trim($_POST['username'] ?? '');
        $passwordInput = $_POST['password'] ?? '';

        if (empty($usernameInput) || empty($passwordInput)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("
                SELECT u.*, r.name as role_name, r.display_name as role_display 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$usernameInput, $usernameInput]);
            $user = $stmt->fetch();

            if ($user && password_verify($passwordInput, $user['password'])) {
                // Login Berhasil
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role_name' => $user['role_name'],
                    'role_display' => $user['role_display']
                ];

                setFlash('success', "Selamat datang kembali, {$user['full_name']}!");
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            } else {
                $error = 'Username atau password yang Anda masukkan tidak sesuai.';
            }
        }
    }
}

$flash = getFlash();
$appName = getSetting('app_name', 'SIM-INVENTARIS');
$appTitle = getSetting('app_title', 'Sistem Informasi Manajemen Inventaris Barang');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - <?= e($appName) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=1.0">
</head>
<body style="background-color: #f4f7f5;">
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
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
            <h1><?= e($appName) ?></h1>
            <p><?= e($appTitle) ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label class="form-label" for="username">Username atau Email</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: admin" value="<?= e($usernameInput) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Masuk ke Sistem</span>
                </button>
            </div>
        </form>

        <div class="auth-demo-box">
            <strong>Informasi Akun Demo (1-Click Login):</strong>
            <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span><strong>Admin:</strong> <code>admin</code> / <code>admin123</code></span>
                    <a href="login.php?quick_demo=admin" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 11px;">Masuk Admin</a>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span><strong>Petugas:</strong> <code>petugas</code> / <code>petugas123</code></span>
                    <a href="login.php?quick_demo=petugas" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 11px;">Masuk Petugas</a>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span><strong>Peninjau:</strong> <code>peninjau</code> / <code>peninjau123</code></span>
                    <a href="login.php?quick_demo=peninjau" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 11px;">Masuk Peninjau</a>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: center; font-size: 11.5px; color: var(--color-text-subtle);">
            &copy; <?= date('Y') ?> Kelompok 6 MPSI &bull; Berbasis QR Code
        </div>
    </div>
</div>
</body>
</html>

