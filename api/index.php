<?php
/**
 * Vercel Serverless Function Entrypoint / Router
 * Melayani seluruh request PHP di Vercel agar tidak terunduh sebagai file statis
 */

// Error handling & logging
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Base directory (root proyek)
$rootDir = dirname(__DIR__);

// Pastikan session path writable di serverless environment (Vercel hanya mengizinkan write di /tmp)
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL') || !is_writable(session_save_path())) {
        @session_save_path('/tmp');
    }
}

// Ambil path request dari URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = urldecode($path ?? '/');

// Routing logika
if ($path === '' || $path === '/') {
    $targetFile = $rootDir . '/index.php';
} else {
    $candidate = $rootDir . $path;
    if (is_dir($candidate)) {
        $candidate = rtrim($candidate, '/') . '/index.php';
    } elseif (!file_exists($candidate) && file_exists($candidate . '.php')) {
        $candidate = $candidate . '.php';
    }
    $targetFile = $candidate;
}

// Validasi keamanan: pastikan file berada di dalam root proyek
$realTarget = realpath($targetFile);
$realRoot = realpath($rootDir);

if ($realTarget && strpos($realTarget, $realRoot) === 0 && file_exists($realTarget) && pathinfo($realTarget, PATHINFO_EXTENSION) === 'php') {
    // Set environment variable global agar script target bekerja normal
    $_SERVER['SCRIPT_FILENAME'] = $realTarget;
    $relPath = str_replace($realRoot, '', $realTarget);
    $_SERVER['SCRIPT_NAME'] = str_replace('\\', '/', $relPath);
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

    // Pindah working directory ke folder file target agar require relatif tetap berjalan
    chdir(dirname($realTarget));

    require $realTarget;
} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 Not Found</title></head>';
    echo '<body style="font-family: sans-serif; text-align: center; padding: 50px;">';
    echo '<h2>404 - Halaman Tidak Ditemukan</h2>';
    echo '<p>Path yang diminta: <code>' . htmlspecialchars($path) . '</code></p>';
    echo '<p><a href="/">Kembali ke Halaman Utama</a></p>';
    echo '</body></html>';
}
