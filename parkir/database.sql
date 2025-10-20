-- Database: parkir
-- Sistem Parkir Kampus

CREATE DATABASE IF NOT EXISTS parkir;
USE parkir;

-- Tabel pengguna
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    no_telepon VARCHAR(20),
    role ENUM('mahasiswa', 'dosen', 'staff', 'admin') DEFAULT 'mahasiswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kendaraan
CREATE TABLE kendaraan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    no_plat VARCHAR(20) UNIQUE NOT NULL,
    jenis_kendaraan ENUM('motor', 'mobil') NOT NULL,
    merk VARCHAR(50),
    warna VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel area parkir
CREATE TABLE area_parkir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_area VARCHAR(50) NOT NULL,
    jenis_kendaraan ENUM('motor', 'mobil') NOT NULL,
    kapasitas INT NOT NULL,
    terisi INT DEFAULT 0,
    status ENUM('aktif', 'maintenance') DEFAULT 'aktif'
);

-- Tabel log parkir
CREATE TABLE log_parkir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    kendaraan_id INT,
    area_parkir_id INT,
    waktu_masuk TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_keluar TIMESTAMP NULL,
    status ENUM('parkir', 'keluar') DEFAULT 'parkir',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id),
    FOREIGN KEY (area_parkir_id) REFERENCES area_parkir(id)
);

-- Insert data area parkir default
INSERT INTO area_parkir (nama_area, jenis_kendaraan, kapasitas) VALUES
('Area Motor A', 'motor', 50),
('Area Motor B', 'motor', 50),
('Area Mobil A', 'mobil', 30),
('Area Mobil B', 'mobil', 20);

-- Insert admin default
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin@kampus.ac.id', 'admin');

ALTER TABLE users ADD COLUMN status ENUM('aktif', 'nonaktif') DEFAULT 'aktif';


