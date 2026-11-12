<?php
/**
 * Global Header Component
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

$appTitle = getSetting('app_title', 'Sistem Informasi Manajemen Inventaris Barang QR Code');
$appName = getSetting('app_name', 'SIM-INVENTARIS');
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . $appName : $appTitle;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core Stylesheets -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=1.0">
    <link rel="stylesheet" href="<?= asset('css/print.css') ?>?v=1.0" media="print">
    
    <!-- Core Local JavaScript Libraries -->
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script src="<?= asset('js/chart.umd.min.js') ?>"></script>
    <script src="<?= asset('js/app.js') ?>?v=1.0" defer></script>
</head>
<body>
<div class="app-wrapper">
    <div class="sidebar-overlay"></div>

