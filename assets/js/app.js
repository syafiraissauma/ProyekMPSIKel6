/**
 * Core Application JavaScript
 * SIM-INVENTARIS - Kelompok 6 MPSI
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Sidebar Toggle
  const toggleBtn = document.querySelector('.btn-toggle-sidebar');
  const sidebar = document.querySelector('.app-sidebar');
  const overlay = document.querySelector('.sidebar-overlay');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      if (overlay) overlay.classList.toggle('show');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }

  // 2. Auto Dismiss Flash Alerts after 6 seconds
  const alerts = document.querySelectorAll('.alert-auto-dismiss');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 400);
    }, 6000);
  });

  // 3. Render all QR Code containers on page
  renderQRCodes();
});

/**
 * Render semua elemen dengan class 'qrcode-container' menggunakan QRCode.js
 */
function renderQRCodes() {
  if (typeof QRCode === 'undefined') return;

  const containers = document.querySelectorAll('.qrcode-container');
  containers.forEach(function (el) {
    if (el.dataset.rendered === 'true') return;
    const text = el.dataset.text || '';
    const size = parseInt(el.dataset.size || '140', 10);
    if (!text) return;

    el.innerHTML = '';
    new QRCode(el, {
      text: text,
      width: size,
      height: size,
      colorDark: '#1b3829',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });
    el.dataset.rendered = 'true';
  });
}

/**
 * Synthesize Scanner Audio Beep (Web Audio API)
 */
function playBeep(freq = 800, duration = 120) {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();

    osc.type = 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + (duration / 1000));

    osc.connect(gain);
    gain.connect(audioCtx.destination);

    osc.start();
    osc.stop(audioCtx.currentTime + (duration / 1000));
  } catch (e) {
    // browser might require user interaction first
  }
}

/**
 * Download QR Code Badge as PNG Image
 */
function downloadBadgeAsImage(containerId, filename) {
  const container = document.getElementById(containerId);
  if (!container) return;

  // Cari elemen canvas di dalam container
  const canvas = container.querySelector('canvas');
  if (canvas) {
    const link = document.createElement('a');
    link.download = (filename || 'qrcode') + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
  } else {
    // Jika berupa img tag
    const img = container.querySelector('img');
    if (img && img.src) {
      const link = document.createElement('a');
      link.download = (filename || 'qrcode') + '.png';
      link.href = img.src;
      link.click();
    }
  }
}

/**
 * Buka Modal Dialog
 */
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

/**
 * Tutup Modal Dialog
 */
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

/**
 * Konfirmasi Hapus Data
 */
function confirmDelete(event, message = 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.') {
  if (!confirm(message)) {
    event.preventDefault();
    return false;
  }
  return true;
}

