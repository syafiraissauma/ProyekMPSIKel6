<?php
/**
 * Application Core Configuration
 */

// Set Zona Waktu Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// Root Path Direktori Sistem
define('ROOT_PATH', dirname(__DIR__));

// Deteksi Otomatis Base URL
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Dapatkan path subdirektori dari SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));
    
    // Cari posisi folder proyek KEL6MPSI
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

