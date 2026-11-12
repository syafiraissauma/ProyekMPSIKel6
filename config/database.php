<?php
/**
 * Database Connection Configuration
 * Menggunakan PDO untuk keamanan dan prepared statement
 * Support Railway environment variables (production) dan XAMPP lokal
 */

// Dukungan Environment Variables untuk Vercel, Railway, Cloud MySQL, dan XAMPP lokal
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($dbUrl) {
    $url = parse_url($dbUrl);
    define('DB_HOST', $url['host'] ?? 'localhost');
    define('DB_PORT', isset($url['port']) ? (string)$url['port'] : '3306');
    define('DB_NAME', isset($url['path']) ? ltrim($url['path'], '/') : 'kel6mpsi_inventaris');
    define('DB_USER', $url['user'] ?? 'root');
    define('DB_PASS', $url['pass'] ?? '');
} else {
    // Fallback ke env var individual (DB_* atau MYSQL*) atau default XAMPP lokal
    define('DB_HOST', getenv('DB_HOST')     ?: getenv('MYSQLHOST')     ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT')     ?: getenv('MYSQLPORT')     ?: '3306');
    define('DB_NAME', getenv('DB_NAME')     ?: getenv('DB_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'kel6mpsi_inventaris');
    define('DB_USER', getenv('DB_USER')     ?: getenv('DB_USERNAME') ?: getenv('MYSQLUSER')     ?: 'root');
    define('DB_PASS', getenv('DB_PASSWORD') ?: getenv('DB_PASS')     ?: getenv('MYSQLPASSWORD') ?: '');
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
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') && DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
        } catch (PDOException $e) {
            // Fallback otomatis ke SQLite jika MySQL tidak tersedia (misal di Vercel tanpa cloud DB)
            $sqliteFile = dirname(__DIR__) . '/database.sqlite';
            if (file_exists($sqliteFile) || is_dir('/tmp') || getenv('VERCEL')) {
                try {
                    $targetSqlite = $sqliteFile;
                    // Pada Vercel (read-only filesystem), salin ke /tmp agar dapat membaca & menulis
                    if (getenv('VERCEL') || !@is_writable($sqliteFile)) {
                        $tmpSqlite = '/tmp/kel6mpsi_database.sqlite';
                        if (!file_exists($tmpSqlite) && file_exists($sqliteFile)) {
                            @copy($sqliteFile, $tmpSqlite);
                        }
                        if (file_exists($tmpSqlite)) {
                            $targetSqlite = $tmpSqlite;
                        }
                    }

                    if (file_exists($targetSqlite)) {
                        $sqlitePdo = new PDO('sqlite:' . $targetSqlite, null, null, [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                        ]);

                        // Daftarkan fungsi helper MySQL agar kompatibel dengan SQLite
                        $sqlitePdo->sqliteCreateFunction('NOW', function() {
                            return date('Y-m-d H:i:s');
                        });
                        $sqlitePdo->sqliteCreateFunction('CURDATE', function() {
                            return date('Y-m-d');
                        });
                        $sqlitePdo->sqliteCreateFunction('CONCAT', function(...$args) {
                            return implode('', $args);
                        });

                        if (!defined('DB_DRIVER')) define('DB_DRIVER', 'sqlite');
                        $pdo = $sqlitePdo;
                        return $pdo;
                    }
                } catch (Exception $sqliteEx) {
                    // Fallback SQLite gagal, tampilkan error MySQL asli
                }
            }

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

