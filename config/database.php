<?php
/**
 * Database Connection Configuration
 * Menggunakan PDO untuk keamanan dan prepared statement
 * Support Railway environment variables (production) dan XAMPP lokal
 */

// Railway menyediakan MYSQL_URL atau variabel terpisah
// Coba baca dari DATABASE_URL (Railway MySQL format) dulu
if (getenv('DATABASE_URL')) {
    $url = parse_url(getenv('DATABASE_URL'));
    define('DB_HOST', $url['host']);
    define('DB_PORT', isset($url['port']) ? (string)$url['port'] : '3306');
    define('DB_NAME', ltrim($url['path'], '/'));
    define('DB_USER', $url['user']);
    define('DB_PASS', isset($url['pass']) ? $url['pass'] : '');
} else {
    // Fallback ke env var individual Railway atau default XAMPP lokal
    define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
    define('DB_PORT', getenv('MYSQLPORT')     ?: '3306');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: 'kel6mpsi_inventaris');
    define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
}

function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Tampilan ramah jika database belum diimpor atau MySQL belum menyala
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Koneksi Database Gagal - SIM Inventaris</title>
                <style>
                    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f7f5; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
                    .card { background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 580px; width: 100%; border-top: 5px solid #2d5a43; }
                    h2 { margin-top: 0; color: #2d5a43; font-size: 22px; }
                    p { line-height: 1.6; color: #475569; }
                    .code { background: #f1f5f3; padding: 12px 16px; border-radius: 8px; font-family: monospace; font-size: 13px; color: #0f3e2b; margin: 15px 0; word-break: break-all; }
                    .btn { display: inline-block; background: #2d5a43; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 500; font-size: 14px; margin-top: 10px; }
                    .btn:hover { background: #234835; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>Koneksi Database Gagal</h2>
                    <p>Sistem tidak dapat terhubung ke database MySQL <strong><?= htmlspecialchars(DB_NAME) ?></strong>.</p>
                    <div class="code"><?= htmlspecialchars($e->getMessage()) ?></div>
                    <p><strong>Langkah Mengatasi:</strong></p>
                    <ol style="color: #475569; padding-left: 20px; line-height: 1.6;">
                        <li>Pastikan modul MySQL di <strong>XAMPP Control Panel</strong> berstatus <strong>Running</strong>.</li>
                        <li>Pastikan database <code>kel6mpsi_inventaris</code> sudah dibuat dan diimpor.</li>
                        <li>Anda juga dapat menjalankan installer otomatis melalui tombol di bawah.</li>
                    </ol>
                    <a href="setup_database.php" class="btn">Buka Installer Database Otomatis &rarr;</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $pdo;
}

