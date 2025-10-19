# PROJEK-UTS-SBD2025

> *Projek ini dibuat oleh: ANOMALI ANCAMAN*

![Status](https://img.shields.io/badge/status-alpha-orange) ![License](https://img.shields.io/badge/license-MIT-blue) ![Made With ❤](https://img.shields.io/badge/made%20with-HTML%2FCSS-green) ![Backend](https://img.shields.io/badge/backend-PHP-blue) ![Database](https://img.shields.io/badge/database-MySQL-yellow)

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
* *Admin memiliki akses khusus melalui dashboard yang berbeda dari pengguna biasa
* *Dashboard Admin*: Menu Kelola User, Kelola Area Parkir, Laporan dan Statistik, Log Aktivitas, Daftar Pendaftar Terbaru, dan Aktivitas Parkir Terbaru
* *Mengelola User*: Nama Lengkap, Username, Password, Email, No.Telepon, Role, dan Daftar User
* *Mengelola Area Parkir*: Tambah Area Parkir Baru, Memilih Jenis Kendaraan, Atur Kapasitas, Update dan Hapus
* *Monitoring Real-time*: Lihat semua kendaraan yang sedang parkir
* *Laporan Aktivitas*: Filter Tanggal Mulai dan Akhir, Isi Statistik Utama, Statistik per Area Parkir, Statistik per Role User, Aktivitas 7 Hari Terakhir, dan Jam Sibuk Hari Sekarang
* *Log Aktivitas Parkir*: Filter Cari User, Status, Jenis Kendaraan, serta Tanggal, Riwayat Aktivitas(Waktu, User, Role, Kendaraan, Area, Status, Durasi Parkir

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

3. *File Website*

   * Copy semua file PHP ke folder htdocs/parkir/ di direktori XAMPP
   * Pastikan struktur folder seperti ini:

     
     <p>htdocs/</p>
     <p>└── parkir/</p>
         <p>├── config.php</p>
         <p>├── index.php</p>
         <p>├── login.php</p>
         <p>├── register.php</p>
         <p>├── parkir_masuk.php</p>
         <p>├── parkir_keluar.php</p>
         <p>├── kendaraan.php</p>
         <p>├── logout.php</p>
         <p>├── admin/</p>
         <p>│   ├── admin-style.css</p>
         <p>│   ├── areas.php</p>
         <p>│   ├── edit_users.php</p>
            <p>│├── index.php</p>
             <p>│├── logs.php</p>
             <p>│├── reports.php</p>
             <p>│└── users.php</p>
         <p>│   </p>
         <p>├── style.css</p>
         <p>├── database.sql</p>
         <p>└── README.md</p>
     

4. *Konfigurasi Database (Opsional)*

   * Edit file config.php jika perlu mengubah setting database
   * Default: host=localhost, user=root, password=(kosong), database=parkir

5. *Akses Website*

   * Buka browser dan akses: http://localhost/parkir/
   * Login dengan akun admin default:

     * Username: admin
     * Password: admin123

---

## Penggunaan

### Untuk Pengguna Baru

1. Klik "Daftar di sini" di halaman login
2. Isi form registrasi dengan lengkap
3. Login dengan akun yang sudah dibuat
4. Daftarkan kendaraan di menu "Kendaraan Saya"
5. Gunakan menu "Parkir Masuk" untuk parkir
6. Gunakan menu "Parkir Keluar" untuk keluar parkir

### Untuk Admin

* Login dengan akun admin untuk mengelola sistem
* Akses dashboard admin di /admin/index.php
* Monitor seluruh aktivitas parkir secara real-time
* Kelola pengguna, kendaraan, area parkir, dan laporan

---

## 🧱 Struktur Database

### Tabel users

* Menyimpan data pengguna/user (mahasiswa, dosen, staff, admin)

### Tabel kendaraan

* Menyimpan data kendaraan yang terdaftar

### Tabel area_parkir

* Menyimpan data area parkir dan kapasitasnya

### Tabel log_parkir

* Menyimpan riwayat aktivitas parkir masuk/keluar

---

## Teknologi yang Digunakan

* *Backend*: PHP 
* *Database*: MySQL 
* *Frontend*: HTML dan CSS
* *Server*: Apache (XAMPP)

---

## Keamanan

* Password di-hash menggunakan MD5
* Session management untuk autentikasi
* Input validation dan sanitization
* SQL prepared statements untuk mencegah SQL injection

---

## 💡 Pengembangan Lanjutan

Untuk pengembangan lebih lanjut, Coming Soon:

1. ---
2. ---
3. ---
4. ---
5. ---

---

## Lisensi

Sistem ini dibuat untuk keperluan edukasi dan dapat digunakan secara bebas.

---

## Kontak

Untuk pertanyaan atau dukungan teknis, silakan hubungi administrator sistem atau anggota tim melalui GitHub masing-masing.

---

Terima kasih telah melihat projek ini — semoga bermanfaat dan menjadi catatan pembelajaran yang berguna! 
