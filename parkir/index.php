<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getConnection();

// Ambil data area parkir
$stmt = $pdo->query("SELECT * FROM area_parkir WHERE status = 'aktif'");
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total statistik
$total_motor = 0;
$total_mobil = 0;
$terisi_motor = 0;
$terisi_mobil = 0;

foreach ($areas as $area) {
    if ($area['jenis_kendaraan'] === 'motor') {
        $total_motor += $area['kapasitas'];
        $terisi_motor += $area['terisi'];
    } else {
        $total_mobil += $area['kapasitas'];
        $terisi_mobil += $area['terisi'];
    }
}

// Ambil log parkir terbaru
$stmt = $pdo->prepare("
    SELECT lp.*, u.nama_lengkap, k.no_plat, k.jenis_kendaraan, ap.nama_area 
    FROM log_parkir lp
    JOIN users u ON lp.user_id = u.id
    JOIN kendaraan k ON lp.kendaraan_id = k.id
    JOIN area_parkir ap ON lp.area_parkir_id = ap.id
    WHERE lp.status = 'parkir'
    ORDER BY lp.waktu_masuk DESC
    LIMIT 10
");
$stmt->execute();
$recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Parkir Kampus - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="header">
        <nav class="nav">
            <div class="nav-left">
                <a href="index.php" class="logo">🅿️ Parkir Kampus</a>
            </div>
        
            <!-- Mobile Menu Toggle -->
            <div class="menu-toggle" id="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
                <!-- Navigation Links -->
                <ul class="nav-links" id="nav-links">
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="parkir_masuk.php">Parkir Masuk</a></li>
                    <li><a href="parkir_keluar.php">Parkir Keluar</a></li>
                    <li><a href="kendaraan.php">Informasi Kendaraan</a></li>
                        <?php if (isAdmin()): ?>
                    <li><a href="admin/">Admin</a></li>
                        <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!</h1>
                <p class="card-subtitle">Sistem Parkir Kampus - Gratis untuk Masyarakat Politeknik Negeri Sambas</p>
            </div>

                <!-- Statistik Parkir -->
                <div class="grid grid-4 mb-3">
                    <div class="status-card status-available">
                        <div class="status-number"><?= $total_motor - $terisi_motor ?></div>
                        <div class="status-label">Motor Tersedia</div>
                    </div>
                     <div class="status-card status-occupied">
                        <div class="status-number"><?= $terisi_motor ?></div>
                        <div class="status-label">Motor Terisi</div>
                    </div>
                    <div class="status-card status-available">
                        <div class="status-number"><?= $total_mobil - $terisi_mobil ?></div>
                        <div class="status-label">Mobil Tersedia</div>
                    </div>
                    <div class="status-card status-occupied">
                        <div class="status-number"><?= $terisi_mobil ?></div>
                        <div class="status-label">Mobil Terisi</div>
                    </div>
                </div>

                <!-- Area Parkir -->
                <h2 class="mb-2">Status Area Parkir</h2>
                    <div class="grid grid-2 mb-3">
                        <?php foreach ($areas as $area): ?>
                            <div class="card">
                                <h3><?= htmlspecialchars($area['nama_area']) ?></h3>
                                    <p>Jenis: <?= ucfirst($area['jenis_kendaraan']) ?></p>
                                    <p>Kapasitas: <?= $area['terisi'] ?>/<?= $area['kapasitas'] ?></p>
                                        <div style="background: #e9ecef; height: 10px; border-radius: 5px; margin: 10px 0;">
                                            <div style="background: <?= $area['terisi'] >= $area['kapasitas'] ? '#dc3545' : '#28a745' ?>; 
                                                height: 100%; 
                                                    width: <?= ($area['terisi'] / $area['kapasitas']) * 100 ?>%; 
                                                        border-radius: 5px;">
                                            </div>
                                        </div>

                                    <p class="text-center">
                                        <?php if ($area['terisi'] >= $area['kapasitas']): ?>
                                             <span style="color: #dc3545; font-weight: bold;">PENUH</span>
                                        <?php else: ?>
                                            <span style="color: #28a745; font-weight: bold;">TERSEDIA</span>
                                        <?php endif; ?>
                                    </p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <!-- Aksi Cepat -->
                    <h2 class="mb-2">Aksi Cepat</h2>
                        <div class="grid grid-2 mb-3">
                            <a href="parkir_masuk.php" class="btn btn-success btn-full">
                                🚗 Parkir Masuk
                            </a>
                            <a href="parkir_keluar.php" class="btn btn-danger btn-full">
                            🚪 Parkir Keluar
                            </a>
                        </div>

                <!-- Log Parkir Terbaru -->
                    <?php if (!empty($recent_logs)): ?>
                        <h2 class="mb-2">Kendaraan yang Sedang Parkir</h2>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>No. Plat</th>
                                            <th>Jenis</th>
                                            <th>Area</th>
                                            <th>Waktu Masuk</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($recent_logs as $log): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                                                <td><?= htmlspecialchars($log['no_plat']) ?></td>
                                                <td><?= ucfirst($log['jenis_kendaraan']) ?></td>
                                                <td><?= htmlspecialchars($log['nama_area']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($log['waktu_masuk'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                // Mobile Menu Toggle
                    document.getElementById('mobile-menu').addEventListener('click', function() {
                        document.getElementById('nav-links').classList.toggle('active');
                    });

                // Close menu when clicking outside
                    document.addEventListener('click', function(event) {
                        const nav = document.querySelector('.nav');
                            const menu = document.getElementById('mobile-menu');
                                const navLinks = document.getElementById('nav-links');
            
                        if (!nav.contains(event.target) && navLinks.classList.contains('active')) {
                            navLinks.classList.remove('active');
                            }
                    });

                // Prevent zoom on double tap (iOS)
                    let lastTouchEnd = 0;
                        document.addEventListener('touchend', function (event) {
                            const now = (new Date()).getTime();
                        if (now - lastTouchEnd <= 300) {
                            event.preventDefault();
                                }
                            lastTouchEnd = now;
                                }, 
                        false);
            </script>
</body>
</html>