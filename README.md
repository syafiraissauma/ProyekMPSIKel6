# 📦 Sistem Informasi Manajemen Inventaris Barang Berbasis QR Code

Aplikasi web manajemen inventaris barang berbasis QR Code yang dibangun menggunakan PHP 8, MySQL, HTML, CSS, dan JavaScript. Dapat dijalankan secara lokal menggunakan XAMPP di Windows.

---

## 🚀 Fitur Utama

- **Dashboard** — Statistik inventaris, grafik kategori & kondisi barang, barang terbaru, riwayat aktivitas
- **Manajemen Data Barang** — CRUD lengkap dengan pencarian, filter, dan pagination
- **QR Code** — Generate otomatis, unduh PNG, cetak label QR Code
- **Scan QR Code** — Pemindai kamera berbasis browser + input kode manual
- **Barang Masuk & Keluar** — Transaksi stok dengan validasi (stok tidak bisa negatif)
- **Riwayat Aktivitas** — Audit trail lengkap dengan data sebelum & sesudah perubahan
- **Kategori & Lokasi** — Kelola kategori dan lokasi penyimpanan barang
- **Laporan** — Laporan inventaris, transaksi, dan kondisi barang dengan ekspor CSV & cetak
- **Manajemen Pengguna** — Kelola akun pengguna (khusus Admin)
- **Multi Role** — Admin, Petugas Inventaris, Peninjau

---

## 🛠️ Teknologi

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP 8.x |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Library | Chart.js, QRCode.js, Html5-QRCode |
| Server | XAMPP (Apache + MySQL) |

---

## 📋 Persyaratan Sistem

- XAMPP versi terbaru (PHP 8.x, MySQL/MariaDB)
- Browser modern (Chrome, Firefox, Edge)
- Koneksi kamera (untuk fitur scan QR Code)

---

## ⚡ Cara Menjalankan Aplikasi

### 1. Clone Repository

```bash
git clone https://github.com/USERNAME/REPO_NAME.git
cd REPO_NAME
```

Atau unduh ZIP dan ekstrak ke folder `C:\xampp\htdocs\KEL6MPSI\`.

### 2. Jalankan XAMPP

- Buka **XAMPP Control Panel**
- Start **Apache** dan **MySQL**

### 3. Import Database

**Via phpMyAdmin:**
1. Buka browser → `http://localhost/phpmyadmin`
2. Klik **New** → buat database baru bernama `kel6mpsi_inventaris`
3. Pilih database tersebut → klik tab **Import**
4. Klik **Browse** → pilih file `database.sql` dari folder proyek
5. Klik **Go** / **Import**

**Via Setup Otomatis (alternatif):**
1. Buka browser → `http://localhost/KEL6MPSI/setup_database.php`
2. Ikuti instruksi di halaman tersebut

### 4. Buka Aplikasi

```
http://localhost/KEL6MPSI/
```

---

## 👤 Akun Demo

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Petugas Inventaris | `petugas` | `petugas123` |
| Peninjau | `peninjau` | `peninjau123` |

---

## 📁 Struktur Folder

```
KEL6MPSI/
├── assets/
│   ├── css/
│   │   ├── style.css          # Stylesheet utama
│   │   └── print.css          # Stylesheet cetak
│   └── js/
│       ├── app.js             # Script utama aplikasi
│       ├── qrcode.min.js      # Library QR Code generator
│       ├── chart.umd.min.js   # Library Chart.js
│       └── html5-qrcode.min.js # Library QR Code scanner
├── auth/
│   ├── login.php              # Halaman login
│   ├── logout.php             # Proses logout
│   └── profile.php            # Edit profil pengguna
├── categories/
│   └── index.php              # Manajemen kategori
├── config/
│   ├── app.php                # Konfigurasi aplikasi
│   ├── database.php           # Koneksi database
│   ├── helpers.php            # Fungsi-fungsi helper
│   └── session.php            # Manajemen sesi & role
├── history/
│   └── index.php              # Riwayat aktivitas
├── includes/
│   ├── footer.php             # Footer layout
│   ├── header.php             # Header layout
│   ├── navbar.php             # Navbar atas
│   └── sidebar.php            # Sidebar navigasi
├── items/
│   ├── index.php              # Daftar barang
│   ├── create.php             # Tambah barang
│   ├── edit.php               # Edit barang
│   ├── delete.php             # Hapus barang
│   ├── detail.php             # Detail barang + QR Code
│   └── print_qr.php           # Cetak label QR Code
├── locations/
│   └── index.php              # Manajemen lokasi
├── reports/
│   ├── index.php              # Laporan inventaris
│   ├── export_csv.php         # Ekspor CSV
│   └── print.php              # Cetak laporan
├── scan/
│   ├── index.php              # Halaman scan QR Code
│   └── api_lookup.php         # API lookup barang
├── settings/
│   └── index.php              # Pengaturan sistem
├── transactions/
│   ├── index.php              # Daftar transaksi
│   ├── in.php                 # Barang masuk
│   └── out.php                # Barang keluar
├── users/
│   └── index.php              # Manajemen pengguna (Admin)
├── database.sql               # File SQL database
├── index.php                  # Dashboard utama
├── setup_database.php         # Installer database otomatis
└── .gitignore
```

---

## 🔐 Keamanan

- Password di-hash menggunakan **bcrypt** (`password_hash`)
- Semua query database menggunakan **PDO Prepared Statements**
- Proteksi **CSRF Token** pada seluruh form POST
- Validasi dan sanitasi input (mencegah XSS & SQL Injection)
- Pembatasan akses berbasis **role** (Admin / Petugas / Peninjau)
- Pengguna yang belum login tidak dapat mengakses halaman internal

---

## 📸 Role & Hak Akses

| Fitur | Admin | Petugas | Peninjau |
|-------|-------|---------|----------|
| Dashboard | ✅ | ✅ | ✅ |
| Lihat Data Barang | ✅ | ✅ | ✅ |
| Tambah/Edit/Hapus Barang | ✅ | ✅ | ❌ |
| Scan QR Code | ✅ | ✅ | ✅ |
| Barang Masuk/Keluar | ✅ | ✅ | ❌ |
| Kategori & Lokasi | ✅ | ✅ | ❌ |
| Laporan | ✅ | ✅ | ✅ |
| Riwayat Aktivitas | ✅ | ✅ | ✅ |
| Manajemen Pengguna | ✅ | ❌ | ❌ |
| Pengaturan Sistem | ✅ | ❌ | ❌ |

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan akademik — Kelompok 6 MPSI.

