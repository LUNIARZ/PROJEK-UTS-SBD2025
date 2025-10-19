<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pdo = getConnection();

// Ambil statistik
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_kendaraan = $pdo->query("SELECT COUNT(*) FROM kendaraan")->fetchColumn();
$total_parkir_aktif = $pdo->query("SELECT COUNT(*) FROM log_parkir WHERE status = 'parkir'")->fetchColumn();
$total_area = $pdo->query("SELECT COUNT(*) FROM area_parkir WHERE status = 'aktif'")->fetchColumn();

// Ambil data terbaru - PERBAIKAN: gunakan waktu_masuk вместо created_at
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
try {
    $recent_logs = $pdo->query("
        SELECT lp.*, u.nama_lengkap, k.no_plat 
        FROM log_parkir lp 
        LEFT JOIN users u ON lp.user_id = u.id 
        LEFT JOIN kendaraan k ON lp.kendaraan_id = k.id 
        ORDER BY lp.waktu_masuk DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_logs = [];
    error_log("Error fetching recent logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Parkir Kampus</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="../index.php" class="logo">🅿️ Parkir Kampus - Admin</a>
            <div class="menu-toggle" id="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-links" id="nav-links">
                <li><a href="../index.php">User Dashboard</a></li>
                <li><a href="index.php" class="active">Admin Dashboard</a></li>
                <li><a href="users.php">Kelola User</a></li>
                <li><a href="areas.php">Kelola Area</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="logs.php">Log Aktivitas</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>


    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Admin Dashboard</h1>
                <p class="card-subtitle">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>! (<?= htmlspecialchars($_SESSION['role']) ?>)</p>
            </div>

            <!-- Statistik -->
            <div class="grid grid-4 mb-3">
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_users ?></div>
                    <div class="status-label">Total User</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= $total_kendaraan ?></div>
                    <div class="status-label">Total Kendaraan</div>
                </div>
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_parkir_aktif ?></div>
                    <div class="status-label">Parkir Aktif</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= $total_area ?></div>
                    <div class="status-label">Area Parkir</div>
                </div>
            </div>

            <!-- Grid Menu Admin -->
            <h2 class="mb-2">Menu Admin</h2>
            <div class="grid grid-2 mb-3">
                <a href="users.php" class="btn btn-primary btn-full">
                    👥 Kelola User
                </a>
                <a href="areas.php" class="btn btn-success btn-full">
                    🅿️ Kelola Area Parkir
                </a>
                <a href="reports.php" class="btn btn-danger btn-full">
                    📊 Laporan & Statistik
                </a>
                <a href="logs.php" class="btn btn-secondary btn-full">
                    📝 Log Aktivitas
                </a>
            </div>

            <!-- User Terbaru dan Aktivitas -->
            <div class="grid grid-2">
                <div class="card">
                    <h3>User Terdaftar (5 Terbaru)</h3>
                    <?php if (!empty($recent_users)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                        <td>
                                            <span class="role-badge role-<?= $user['role'] ?>">
                                                <?= strtoupper($user['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center">Tidak ada user terdaftar.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Aktivitas Parkir Terbaru</h3>
                    <?php if (!empty($recent_logs)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Plat</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($log['no_plat']) ?></td>
                                        <td>
                                            <span style="color: <?= $log['status'] === 'parkir' ? '#28a745' : '#dc3545' ?>; font-weight: bold;">
                                                <?= strtoupper($log['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($log['waktu_masuk'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center">Tidak ada aktivitas parkir.</p>
                    <?php endif; ?>
                </div>
            </div>
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
        }, false);
    </script>
</body>
</html>
