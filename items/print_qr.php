<?php
/**
 * Cetak Label QR Code Fisik / Sticker Tag
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pdo = getDbConnection();
$code = trim($_GET['code'] ?? '');

$stmt = $pdo->prepare("
    SELECT i.*, c.name as category_name, l.name as location_name, l.building, l.floor
    FROM items i
    JOIN categories c ON i.category_id = c.id
    JOIN locations l ON i.location_id = l.id
    WHERE i.item_code = ?
");
$stmt->execute([$code]);
$item = $stmt->fetch();

if (!$item) {
    die("Barang dengan kode {$code} tidak ditemukan.");
}

$qrContent = url('items/detail.php?code=' . urlencode($item['item_code']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label QR - <?= e($item['item_code']) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script src="<?= asset('js/app.js') ?>"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f1f5f3;
            margin: 0;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .controls {
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
        }

        .btn {
            background-color: #2d5a43;
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { background-color: #234835; }
        .btn-secondary { background-color: #64748b; }
        .btn-secondary:hover { background-color: #475569; }

        .label-card {
            width: 340px;
            background: #ffffff;
            border: 2px solid #2d5a43;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .label-header {
            border-bottom: 1.5px solid #2d5a43;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .label-header h3 {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            color: #2d5a43;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .label-header p {
            margin: 2px 0 0;
            font-size: 10px;
            color: #64748b;
        }

        .qr-box {
            display: inline-flex;
            padding: 8px;
            background: #fff;
            border: 1px solid #e2e8e5;
            border-radius: 8px;
            margin: 6px 0 10px;
        }

        .code-badge {
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            background: #eaf2ed;
            color: #1b3829;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .item-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .label-meta {
            border-top: 1px solid #e2e8e5;
            padding-top: 8px;
            text-align: left;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }

        @media print {
            body {
                background: transparent;
                padding: 0;
            }
            .controls {
                display: none !important;
            }
            .label-card {
                border: 1.5px solid #000;
                box-shadow: none;
                margin: 0;
                page-break-inside: avoid;
            }
            .label-header {
                border-bottom: 1.5px solid #000;
            }
            .code-badge {
                border: 1px solid #000;
                background: transparent;
                color: #000;
            }
        }
    </style>
</head>
<body>

<div class="controls">
    <button type="button" class="btn" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak Label Sekarang</span>
    </button>
    <a href="javascript:window.close()" class="btn btn-secondary">Tutup</a>
</div>

<div class="label-card">
    <div class="label-header">
        <h3><?= e(getSetting('institution_name', 'Kelompok 6 MPSI')) ?></h3>
        <p>Label Aset Inventaris Barang Terdaftar</p>
    </div>

    <div class="qr-box">
        <div class="qrcode-container" data-text="<?= e($qrContent) ?>" data-size="160"></div>
    </div>

    <div>
        <div class="code-badge"><?= e($item['item_code']) ?></div>
        <div class="item-name"><?= e($item['name']) ?></div>
    </div>

    <div class="label-meta">
        <div><strong>Kategori:</strong> <?= e($item['category_name']) ?></div>
        <div><strong>Lokasi:</strong> <?= e($item['location_name']) ?></div>
        <div><strong>Kondisi:</strong> <?= e($item['item_condition']) ?> &bull; <strong>Tgl:</strong> <?= formatDate($item['entry_date'], 'short') ?></div>
    </div>
</div>

</body>
</html>

