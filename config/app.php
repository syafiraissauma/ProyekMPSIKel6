<?php
/**
 * Application Core Configuration
 */

// Set Zona Waktu Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// Root Path Direktori Sistem
define('ROOT_PATH', dirname(__DIR__));

// Deteksi Otomatis Base URL
// Di Railway: dokumen root adalah /var/www/html langsung (bukan subdirektori)
function getBaseUrl(): string {
    // Jika ada RAILWAY_STATIC_URL atau Railway env, gunakan HTTPS langsung
    if (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) {
        $protocol = 'https://';
        $host = $_SERVER['HTTP_HOST'] ?? (getenv('RAILWAY_PUBLIC_DOMAIN') ?: 'localhost');
        return rtrim($protocol . $host, '/');
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    ) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Dapatkan path subdirektori dari SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));

    // Cari posisi folder proyek KEL6MPSI (lokal)
    $projectFolder = 'KEL6MPSI';
    $pos = stripos($dir, '/' . $projectFolder);
    if ($pos !== false) {
        $subPath = substr($dir, 0, $pos + strlen($projectFolder) + 1);
    } else {
        $subPath = rtrim($dir, '/');
    }

    return rtrim($protocol . $host . $subPath, '/');
}

define('BASE_URL', getBaseUrl());

