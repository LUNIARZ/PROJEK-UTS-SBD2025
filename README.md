# PROJEK-UTS-SBD2025

> *Projek ini dibuat oleh: ANOMALI ANCAMAN*

![Status](https://img.shields.io/badge/status-alpha-orange) ![License](https://img.shields.io/badge/license-MIT-blue) ![Made With ❤](https://img.shields.io/badge/made%20with-HTML%2FCSS%2FJS-green) ![Backend](https://img.shields.io/badge/backend-PHP-blue) ![Database](https://img.shields.io/badge/database-MySQL-yellow)

---

## Tentang Projek

*PROJEK-UTS-SBD2025* adalah projek tugas UTS untuk mata kuliah *Sistem Basis Data II* tahun 2025. Projek ini berupa *Sistem Parkir Kampus* berbasis web menggunakan PHP dan MySQL yang dirancang untuk membantu manajemen parkir di area kampus.

---

## Sistem Parkir Kampus

Sistem manajemen parkir kampus gratis berbasis web menggunakan PHP dan MySQL.

### Fitur Utama

* *Autentikasi Pengguna*: Login dan registrasi untuk mahasiswa, dosen, staff, dan admin
* *Manajemen Kendaraan*: Daftarkan dan kelola kendaraan pribadi
* *Parkir Masuk/Keluar*: Sistem check-in dan check-out kendaraan
* *Monitoring Real-time*: Pantau ketersediaan area parkir secara langsung
* *Area Parkir Terpisah*: Area khusus untuk motor dan mobil
* *Log Aktivitas*: Riwayat parkir lengkap dengan durasi
* *Interface Responsif*: Desain simple tetapi mobile-friendly
* *Panel Admin Lengkap*: Kontrol penuh terhadap seluruh fitur sistem

---

## Anomali Kontributor

* *FIRRIZQI*
* *URAY ZAINUL MUTTAQIN*
* *DANI*
* *ABIYAN SATRIAJI*

> Tim mengembangkan projek ini dengan semangat belajar, eksperimen, dan praktik terbaik dalam kolaborasi.

---

## Peran Admin

Sebagai *Administrator*, Anda memiliki kendali penuh terhadap sistem parkir. Berikut adalah fitur-fitur tambahan khusus admin:

### 🔧 Fitur Admin

* *Manajemen Pengguna*: Tambah, ubah, atau hapus akun pengguna (mahasiswa, dosen, staff dan admin)
* *Mengelola User*: Nama Lengkap, Username, Password, Email, No.Telepon dan Role
* *Kontrol Area Parkir*: Tambah Area Parkir Baru, Memilih Jenis Kendaraan, Atur Kapasitas, Update dan Hapus
* *Monitoring Real-time*: Lihat semua kendaraan yang sedang parkir
* *Laporan Aktivitas*: Unduh dan lihat riwayat parkir dalam bentuk tabel dan grafik
* *Manajemen Kendaraan*: Edit dan hapus kendaraan pengguna
* *Reset Sistem*: Kosongkan data parkir untuk periode baru
* *Pengaturan Sistem*: Atur batas waktu parkir, kapasitas maksimal, dan konfigurasi lainnya

> Admin memiliki akses khusus melalui dashboard yang berbeda dari pengguna biasa, lengkap dengan visualisasi data dan notifikasi sistem.

---

## 🛠 Instalasi

### Persyaratan

* XAMPP (Apache + MySQL + PHP)
* Web browser modern

### Langkah Instalasi

1. *Download dan Install XAMPP*

   * Download XAMPP dari [https://www.apachefriends.org/](https://www.apachefriends.org/)
   * Install dan jalankan Apache + MySQL

2. *Setup Database*

   * Buka phpMyAdmin (http://localhost/phpmyadmin)
   * Import file database.sql untuk membuat database dan tabel
   * Database akan dibuat dengan nama parkir

3. *Setup File Website*

   * Copy semua file PHP ke folder htdocs/parkir/ di direktori XAMPP
   * Pastikan struktur folder seperti ini:

     
     htdocs/
     └── parkir/
         ├── config.php
         ├── index.php
         ├── login.php
         ├── register.php
         ├── parkir_masuk.php
         ├── parkir_keluar.php
         ├── kendaraan.php
         ├── logout.php
         ├── admin/
         │   ├── dashboard.php
         │   ├── pengguna.php
         │   ├── area_parkir.php
         │   └── laporan.php
         ├── style.css
         ├── database.sql
         └── README.md
     

4. *Konfigurasi Database (Opsional)*

   * Edit file config.php jika perlu mengubah setting database
   * Default: host=localhost, user=root, password=(kosong), database=parkir

5. *Akses Website*

   * Buka browser dan akses: http://localhost/parkir/
   * Login dengan akun admin default:

     * Username: admin
     * Password: admin123

---

## 💻 Penggunaan

### Untuk Pengguna Baru

1. Klik "Daftar di sini" di halaman login
2. Isi form registrasi dengan lengkap
3. Login dengan akun yang sudah dibuat
4. Daftarkan kendaraan di menu "Kendaraan Saya"
5. Gunakan menu "Parkir Masuk" untuk parkir
6. Gunakan menu "Parkir Keluar" untuk keluar parkir

### Untuk Admin

* Login dengan akun admin untuk mengelola sistem
* Akses dashboard admin di /admin/dashboard.php
* Monitor seluruh aktivitas parkir secara real-time
* Kelola pengguna, kendaraan, area parkir, dan laporan

---

## 🧱 Struktur Database

### Tabel users

* Menyimpan data pengguna (mahasiswa, dosen, staff, admin)

### Tabel kendaraan

* Menyimpan data kendaraan yang terdaftar

### Tabel area_parkir

* Menyimpan data area parkir dan kapasitasnya

### Tabel log_parkir

* Menyimpan riwayat aktivitas parkir masuk/keluar

---

## ⚙ Teknologi yang Digunakan

* *Backend*: PHP 7.4+
* *Database*: MySQL 5.7+
* *Frontend*: HTML5, CSS3, JavaScript
* *Server*: Apache (XAMPP)

---

## 🔒 Keamanan

* Password di-hash menggunakan MD5
* Session management untuk autentikasi
* Input validation dan sanitization
* SQL prepared statements untuk mencegah SQL injection

---

## 💡 Pengembangan Lanjutan

Untuk pengembangan lebih lanjut, Anda dapat:

1. Menambahkan fitur notifikasi email
2. Implementasi QR code untuk tiket parkir
3. Integrasi dengan sistem pembayaran
4. Mobile app development
5. Reporting dan analytics

---

## 📄 Lisensi

Sistem ini dibuat untuk keperluan edukasi dan dapat digunakan secara bebas.

---

## 📬 Kontak

Untuk pertanyaan atau dukungan teknis, silakan hubungi administrator sistem atau anggota tim melalui GitHub masing-masing.

---

Terima kasih telah melihat projek ini — semoga bermanfaat dan menjadi catatan pembelajaran yang berguna! 🎉
