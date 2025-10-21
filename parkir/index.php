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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="header">
        <nav class="nav">
            <div class="nav-left">
                <a href="index.php" class="logo">
                    <span style="font-size: 2rem;">🅿️</span>
                    <span>Parkir Kampus</span>
                </a>
            </div>
        
            <!-- Mobile Menu Toggle -->
            <div class="menu-toggle" id="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <!-- Navigation Links -->
            <ul class="nav-links" id="nav-links">
                <li><a href="index.php" class="active">📊 Dashboard</a></li>
                <li><a href="parkir_masuk.php">🚗 Parkir Masuk</a></li>
                <li><a href="parkir_keluar.php">🚪 Parkir Keluar</a></li>
                <li><a href="kendaraan.php">ℹ️ Info Kendaraan</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/">⚙️ Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Welcome Card -->
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>! 👋</h1>
                <p class="card-subtitle">Sistem Parkir Kampus - Gratis untuk Masyarakat Politeknik Negeri Sambas</p>
            </div>

            <!-- Statistik Parkir -->
            <div class="grid grid-4 mb-3">
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_motor - $terisi_motor ?></div>
                    <div class="status-label">🏍️ Motor Tersedia</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= $terisi_motor ?></div>
                    <div class="status-label">🏍️ Motor Terisi</div>
                </div>
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_mobil - $terisi_mobil ?></div>
                    <div class="status-label">🚗 Mobil Tersedia</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= $terisi_mobil ?></div>
                    <div class="status-label">🚗 Mobil Terisi</div>
                </div>
            </div>

            <!-- Area Parkir -->
            <h2 class="mb-2" style="font-size: 1.75rem; font-weight: 700; color: var(--text-primary);">📍 Status Area Parkir</h2>
            <div class="grid grid-2 mb-3">
                <?php foreach ($areas as $area): ?>
                    <div class="card" style="margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <div style="font-size: 2rem;">
                                <?= $area['jenis_kendaraan'] === 'motor' ? '🏍️' : '🚗' ?>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;"><?= htmlspecialchars($area['nama_area']) ?></h3>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">Area <?= ucfirst($area['jenis_kendaraan']) ?></p>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <span style="font-weight: 600; color: var(--text-primary);">Kapasitas:</span>
                            <span style="font-size: 1.1rem; font-weight: 700; color: var(--primary-color);">
                                <?= $area['terisi'] ?>/<?= $area['kapasitas'] ?>
                            </span>
                        </div>

                        <div class="progress-container">
                            <div class="progress-bar <?= $area['terisi'] >= $area['kapasitas'] ? 'progress-full' : 'progress-available' ?>" 
                                 style="width: <?= ($area['terisi'] / $area['kapasitas']) * 100 ?>%;"></div>
                        </div>

                        <p class="text-center" style="margin: 0;">
                            <?php if ($area['terisi'] >= $area['kapasitas']): ?>
                                <span style="color: #dc3545; font-weight: 700; font-size: 1.1rem;">🔴 PENUH</span>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: 700; font-size: 1.1rem;">🟢 TERSEDIA</span>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Aksi Cepat -->
            <h2 class="mb-2" style="font-size: 1.75rem; font-weight: 700; color: var(--text-primary);">⚡ Aksi Cepat</h2>
            <div class="grid grid-2 mb-3">
                <a href="parkir_masuk.php" class="btn btn-success btn-full">
                    <span style="font-size: 1.25rem;">🚗</span>
                    <span>Parkir Masuk</span>
                </a>
                <a href="parkir_keluar.php" class="btn btn-danger btn-full">
                    <span style="font-size: 1.25rem;">🚪</span>
                    <span>Parkir Keluar</span>
                </a>
            </div>

            <!-- Log Parkir -->
            <?php if (!empty($recent_logs)): ?>
                <h2 class="mb-2" style="font-size: 1.75rem; font-weight: 700; color: var(--text-primary);">🚗 Kendaraan Sedang Parkir</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>👤 Nama</th>
                                <th>🔢 No. Plat</th>
                                <th>🚗 Jenis</th>
                                <th>📍 Area</th>
                                <th>⏰ Waktu Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                                    <td style="font-family: monospace; font-weight: 700; color: var(--primary-color);"><?= htmlspecialchars($log['no_plat']) ?></td>
                                    <td>
                                        <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                            <?= $log['jenis_kendaraan'] === 'motor' ? '🏍️' : '🚗' ?>
                                            <?= ucfirst($log['jenis_kendaraan']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['nama_area']) ?></td>
                                    <td style="font-weight: 500;"><?= date('d/m/Y H:i', strtotime($log['waktu_masuk'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🅿️</div>
                    <h3 style="color: var(--text-secondary); margin: 0;">Belum ada kendaraan yang parkir</h3>
                    <p style="color: var(--text-muted); margin: 0.5rem 0 0 0;">Area parkir masih kosong</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tombol Mobile Menu 
        document.getElementById('mobile-menu').addEventListener('click', function() {
            const navLinks = document.getElementById('nav-links');
            navLinks.classList.toggle('active');
            
            // Animasi menu
            this.classList.toggle('active');
        });

        document.addEventListener('click', function(event) {
            const nav = document.querySelector('.nav');
            const menu = document.getElementById('mobile-menu');
            const navLinks = document.getElementById('nav-links');
            
            if (!nav.contains(event.target) && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                menu.classList.remove('active');
            }
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.classList.contains('loading')) {
                    this.classList.add('loading');
                    setTimeout(() => {
                        this.classList.remove('loading');
                    }, 1000);
                }
            });
        });

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card, .status-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>

    <style>
        .menu-toggle.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        
        .menu-toggle.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }

        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    </style>
</body>
</html>
