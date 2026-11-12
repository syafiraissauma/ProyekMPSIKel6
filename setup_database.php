<?php
/**
 * Automated Database Installer & Seeder Script
 * Mengimpor database.sql secara otomatis dengan 1-klik
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kel6mpsi_inventaris');
define('DB_USER', 'root');
define('DB_PASS', '');

if (!function_exists('e')) {
    function e(?string $string): string {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$message = '';
$status = 'idle';
$tablesCreated = [];

if (isset($_POST['install']) || php_sapi_name() === 'cli') {
    try {
        // Koneksi awal tanpa nama database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("File database.sql tidak ditemukan di: " . $sqlFile);
        }

        $sqlContent = file_get_contents($sqlFile);

        // Eksekusi SQL multi-query
        $pdo->exec($sqlContent);

        // Cek tabel yang telah dibuat
        $pdo->exec("USE `" . DB_NAME . "`");
        $stmt = $pdo->query("SHOW TABLES");
        $tablesCreated = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $status = 'success';
        $message = "Database '" . DB_NAME . "' dan seluruh data awal berhasil diinstal!";
    } catch (Exception $e) {
        $status = 'error';
        $message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer Database - SIM Inventaris</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f7f5;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .setup-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            max-width: 620px;
            width: 100%;
            padding: 36px 32px;
            border-top: 5px solid #2d5a43;
        }
        h1 { margin-top: 0; font-size: 22px; color: #2d5a43; }
        p { color: #64748b; font-size: 13.5px; line-height: 1.6; }
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin: 20px 0;
            line-height: 1.5;
        }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #2d5a43;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #234835; }
        .btn-outline {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #334155;
        }
        .btn-outline:hover { background: #f8fafc; }
        .table-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .table-item {
            background: #f1f5f3;
            padding: 6px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            color: #1e3d2f;
        }
        .account-box {
            background: #f8faf9;
            border: 1px solid #e2e8e5;
            border-radius: 6px;
            padding: 14px;
            margin-top: 20px;
            font-size: 12.5px;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <h1>Installer Database Otomatis</h1>
    <p>Aplikasi Sistem Informasi Manajemen Inventaris Barang Berbasis QR Code (Kelompok 6 MPSI).</p>

    <?php if ($status === 'success'): ?>
        <div class="alert alert-success">
            <strong>Berhasil!</strong> <?= e($message) ?>
            <div style="margin-top: 10px; font-weight: 600;">Tabel yang telah dibuat di database:</div>
            <div class="table-list">
                <?php foreach ($tablesCreated as $tbl): ?>
                    <div class="table-item">&bull; <?= e($tbl) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="account-box">
            <strong>Akun Bawaan Siap Digunakan:</strong>
            <ul style="margin: 6px 0 0; padding-left: 18px; line-height: 1.6;">
                <li><strong>Admin:</strong> <code>admin</code> / <code>admin123</code> (Akses Penuh)</li>
                <li><strong>Petugas:</strong> <code>petugas</code> / <code>petugas123</code> (Kelola Barang & Transaksi)</li>
                <li><strong>Peninjau:</strong> <code>peninjau</code> / <code>peninjau123</code> (Hanya Lihat Laporan & Barang)</li>
            </ul>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 10px;">
            <a href="auth/login.php" class="btn">Buka Halaman Login &rarr;</a>
            <a href="index.php" class="btn btn-outline">Menuju Dashboard</a>
        </div>

    <?php elseif ($status === 'error'): ?>
        <div class="alert alert-error">
            <strong>Terjadi Kesalahan:</strong><br>
            <?= e($message) ?>
        </div>
        <p>Pastikan MySQL di XAMPP Control Panel telah berjalan (Status: Running pada port 3306).</p>
        <form method="POST" action="">
            <button type="submit" name="install" class="btn">Coba Instal Ulang</button>
        </form>

    <?php else: ?>
        <div style="background: #f1f5f3; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin: 16px 0; font-size: 13px;">
            <strong>Konfigurasi Target:</strong>
            <ul style="margin: 6px 0 0; padding-left: 18px; line-height: 1.6;">
                <li>Host: <code><?= DB_HOST ?>:<?= DB_PORT ?></code></li>
                <li>Nama Database: <code><?= DB_NAME ?></code></li>
                <li>User: <code><?= DB_USER ?></code></li>
                <li>Sumber SQL: <code>database.sql</code></li>
            </ul>
        </div>

        <form method="POST" action="">
            <button type="submit" name="install" class="btn">
                Mulai Setup Database & Isi Data Awal
            </button>
            <a href="auth/login.php" class="btn btn-outline" style="margin-left: 8px;">Langsung ke Login</a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
