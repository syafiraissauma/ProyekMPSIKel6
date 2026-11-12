<?php
/**
 * Halaman Pemindaian QR Code (Kamera Perangkat & Alternatif Manual)
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

$pageTitle = 'Pindai QR Code Barang';
$breadcrumbTitle = 'Pindai QR';

// Jika pengguna langsung mengirim form manual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manualCode = strtoupper(trim($_POST['manual_code'] ?? ''));
    if (!empty($manualCode)) {
        header('Location: ' . url('items/detail.php?code=' . urlencode($manualCode)));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Include HTML5-QRCode Scanner Library -->
<script src="<?= asset('js/html5-qrcode.min.js') ?>"></script>

<div class="page-header">
    <div class="page-title">
        <h1>Pemindaian QR Code Inventaris</h1>
        <p>Arahkan kamera ke QR Code barang atau masukkan kode barang secara manual</p>
    </div>
</div>

<div class="scanner-container">
    <!-- Kolom 1: Pemindai Kamera -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                <span>Pemindai Kamera Perangkat</span>
            </h2>
            <div style="display: flex; gap: 6px;">
                <button type="button" id="btnToggleCamera" class="btn btn-primary btn-sm">Mulai Kamera</button>
            </div>
        </div>
        <div class="card-body">
            <!-- Pilihan Kamera -->
            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="cameraSelect">Pilih Perangkat Kamera:</label>
                <select id="cameraSelect" class="form-select">
                    <option value="">Mencari kamera...</option>
                </select>
            </div>

            <!-- Area Video Kamera -->
            <div class="scanner-viewfinder" id="scannerViewport">
                <div id="reader"></div>
                <div id="scannerPlaceholder" style="text-align: center; color: #94a3b8; padding: 40px 20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.7;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <div style="font-size: 14px; font-weight: 600;">Kamera Belum Aktif</div>
                    <div style="font-size: 12px; margin-top: 4px;">Klik tombol "Mulai Kamera" di atas untuk memulai pemindaian otomatis.</div>
                </div>
            </div>

            <div style="margin-top: 14px; font-size: 11.5px; color: var(--color-text-subtle); display: flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Pastikan izin akses kamera telah diizinkan di browser Anda (Google Chrome / Edge / Firefox).</span>
            </div>
        </div>
    </div>

    <!-- Kolom 2: Alternatif Input Manual & Hasil Scan -->
    <div>
        <!-- Input Manual -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="6" y1="8" x2="6.01" y2="8"></line><line x1="10" y1="8" x2="10.01" y2="8"></line><line x1="14" y1="8" x2="14.01" y2="8"></line><line x1="18" y1="8" x2="18.01" y2="8"></line><line x1="6" y1="12" x2="6.01" y2="12"></line><line x1="18" y1="12" x2="18.01" y2="12"></line><line x1="8" y1="16" x2="16" y2="16"></line></svg>
                    <span>Alternatif: Input Kode Barang Manual</span>
                </h2>
            </div>
            <div class="card-body">
                <p style="font-size: 12.5px; margin-bottom: 14px;">Jika kamera tidak tersedia atau tidak berfungsi, masukkan kode barang secara manual:</p>

                <form id="manualSearchForm" onsubmit="handleManualSearch(event)">
                    <div class="form-group">
                        <label class="form-label" for="manual_code">Kode Barang</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="manual_code" name="manual_code" class="form-control" placeholder="Contoh: BRG-2024-0001" required style="font-family: monospace; font-weight: 600;">
                            <button type="submit" class="btn btn-primary" style="flex-shrink: 0;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <span>Cari</span>
                            </button>
                        </div>
                    </div>
                </form>

                <div style="margin-top: 10px; font-size: 11px; color: var(--color-text-subtle);">
                    Contoh kode cepat untuk diuji: 
                    <a href="javascript:void(0)" onclick="quickFill('BRG-2024-0001')" style="text-decoration: underline;">BRG-2024-0001</a>, 
                    <a href="javascript:void(0)" onclick="quickFill('BRG-2024-0003')" style="text-decoration: underline;">BRG-2024-0003</a>, 
                    <a href="javascript:void(0)" onclick="quickFill('BRG-2024-0005')" style="text-decoration: underline;">BRG-2024-0005</a>
                </div>
            </div>
        </div>

        <!-- Kartu Hasil Pemindaian (Akan Muncul Setelah QR Berhasil Dipindai) -->
        <div class="card" id="scanResultCard" style="display: none; border-left: 4px solid var(--color-primary);">
            <div class="card-header" style="background-color: var(--color-primary-light);">
                <h2 class="card-title" style="color: var(--color-primary-dark);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Barang Berhasil Ditemukan!</span>
                </h2>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <span class="table-code" id="resultItemCode" style="font-size: 14px;">-</span>
                        <h3 id="resultItemName" style="font-size: 16px; margin-top: 6px;">-</h3>
                    </div>
                    <span id="resultConditionBadge" class="badge badge-success">-</span>
                </div>

                <div style="font-size: 12.5px; display: flex; flex-direction: column; gap: 6px; padding: 10px; background: #fafcfb; border-radius: var(--radius-sm); border: 1px solid var(--color-border); margin-bottom: 16px;">
                    <div><strong>Kategori:</strong> <span id="resultCategory">-</span></div>
                    <div><strong>Lokasi:</strong> <span id="resultLocation">-</span></div>
                    <div><strong>Stok Tersedia:</strong> <span id="resultStock" style="font-weight: 700; color: var(--color-primary);">-</span></div>
                    <div><strong>Status:</strong> <span id="resultStatus">-</span></div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="#" id="resultDetailBtn" class="btn btn-primary" style="width: 100%;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>Lihat Detail Lengkap</span>
                    </a>
                    <?php if (canEdit()): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <a href="#" id="resultInBtn" class="btn btn-secondary btn-sm" style="color: var(--color-success); border-color: #a7f3d0;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                                <span>+ Barang Masuk</span>
                            </a>
                            <a href="#" id="resultOutBtn" class="btn btn-secondary btn-sm" style="color: var(--color-warning); border-color: #fde68a;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                                <span>- Barang Keluar</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="scanErrorBox" class="alert alert-danger" style="display: none; margin-top: 14px;"></div>
    </div>
</div>

<script>
let html5QrCode = null;
let isScanning = false;
let cameras = [];

document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi daftar kamera perangkat
    if (typeof Html5Qrcode !== 'undefined') {
        Html5Qrcode.getCameras().then(devices => {
            const select = document.getElementById('cameraSelect');
            select.innerHTML = '';
            if (devices && devices.length) {
                cameras = devices;
                devices.forEach((device, i) => {
                    const opt = document.createElement('option');
                    opt.value = device.id;
                    opt.text = device.label || `Kamera ${i + 1}`;
                    select.appendChild(opt);
                });
            } else {
                select.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
            }
        }).catch(err => {
            console.warn('Gagal memuat kamera: ', err);
            document.getElementById('cameraSelect').innerHTML = '<option value="">Kamera tidak didukung / diblokir</option>';
        });
    }

    document.getElementById('btnToggleCamera').addEventListener('click', toggleCamera);
});

function toggleCamera() {
    if (isScanning) {
        stopCamera();
    } else {
        startCamera();
    }
}

function startCamera() {
    const select = document.getElementById('cameraSelect');
    const cameraId = select.value;
    const placeholder = document.getElementById('scannerPlaceholder');
    const btn = document.getElementById('btnToggleCamera');

    if (!cameraId && cameras.length === 0) {
        alert('Kamera tidak ditemukan pada perangkat ini. Silakan gunakan alternatif input kode barang manual.');
        return;
    }

    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 240, height: 240 } };

    html5QrCode.start(
        cameraId || { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanFailure
    ).then(() => {
        isScanning = true;
        placeholder.style.display = 'none';
        btn.textContent = 'Hentikan Kamera';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-danger');
    }).catch(err => {
        alert('Tidak dapat mengaktifkan kamera: ' + err);
        console.error(err);
    });
}

function stopCamera() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            document.getElementById('scannerPlaceholder').style.display = 'block';
            const btn = document.getElementById('btnToggleCamera');
            btn.textContent = 'Mulai Kamera';
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-primary');
        }).catch(err => console.error(err));
    }
}

function onScanSuccess(decodedText, decodedResult) {
    playBeep(900, 150);
    lookupItem(decodedText);
}

function onScanFailure(error) {
    // scanning frame without QR - normal
}

function quickFill(code) {
    document.getElementById('manual_code').value = code;
    handleManualSearch(new Event('submit'));
}

function handleManualSearch(e) {
    e.preventDefault();
    const code = document.getElementById('manual_code').value.trim();
    if (code) {
        lookupItem(code);
    }
}

function lookupItem(query) {
    const errBox = document.getElementById('scanErrorBox');
    const resultCard = document.getElementById('scanResultCard');
    errBox.style.display = 'none';

    fetch('<?= url("scan/api_lookup.php") ?>?code=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const item = res.data;
                document.getElementById('resultItemCode').textContent = item.item_code;
                document.getElementById('resultItemName').textContent = item.name;
                document.getElementById('resultCategory').textContent = item.category_name;
                document.getElementById('resultLocation').textContent = item.location_name;
                document.getElementById('resultStock').textContent = `${item.quantity.toLocaleString('id-ID')} ${item.unit}`;
                document.getElementById('resultStatus').textContent = item.status;
                document.getElementById('resultConditionBadge').textContent = item.item_condition;
                
                // Set Link
                document.getElementById('resultDetailBtn').href = item.detail_url;
                const inBtn = document.getElementById('resultInBtn');
                if (inBtn) inBtn.href = item.trx_in_url;
                const outBtn = document.getElementById('resultOutBtn');
                if (outBtn) outBtn.href = item.trx_out_url;

                resultCard.style.display = 'block';
                resultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                resultCard.style.display = 'none';
                errBox.textContent = res.message || 'Barang tidak ditemukan di database.';
                errBox.style.display = 'block';
            }
        })
        .catch(err => {
            resultCard.style.display = 'none';
            errBox.textContent = 'Gagal menghubungi server: ' + err.message;
            errBox.style.display = 'block';
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

