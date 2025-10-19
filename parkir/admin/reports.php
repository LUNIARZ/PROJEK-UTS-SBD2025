<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pdo = getConnection();

// Filter tanggal
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Statistik Utama
$stmt = $pdo->prepare("SELECT COUNT(*) FROM log_parkir WHERE DATE(waktu_masuk) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_parkir = $stmt->fetchColumn();

$parkir_aktif = $pdo->query("SELECT COUNT(*) FROM log_parkir WHERE status = 'parkir'")->fetchColumn();
$total_motor = $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE jenis_kendaraan = 'motor'")->fetchColumn();
$total_mobil = $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE jenis_kendaraan = 'mobil'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Statistik per Area
$area_stats = $pdo->prepare("
    SELECT ap.nama_area, ap.jenis_kendaraan, COUNT(lp.id) as total_parkir,
           SUM(CASE WHEN lp.status = 'parkir' THEN 1 ELSE 0 END) as aktif
    FROM area_parkir ap
    LEFT JOIN log_parkir lp ON ap.id = lp.area_parkir_id 
    AND DATE(lp.waktu_masuk) BETWEEN ? AND ?
    GROUP BY ap.id
");
$area_stats->execute([$start_date, $end_date]);
$area_stats = $area_stats->fetchAll(PDO::FETCH_ASSOC);

// Statistik per User Role
$role_stats = $pdo->prepare("
    SELECT u.role, COUNT(DISTINCT lp.id) as total_parkir,
           COUNT(DISTINCT k.id) as total_kendaraan
    FROM users u
    LEFT JOIN kendaraan k ON u.id = k.user_id
    LEFT JOIN log_parkir lp ON u.id = lp.user_id 
    AND DATE(lp.waktu_masuk) BETWEEN ? AND ?
    GROUP BY u.role
");
$role_stats->execute([$start_date, $end_date]);
$role_stats = $role_stats->fetchAll(PDO::FETCH_ASSOC);

// Aktivitas Harian (7 hari terakhir)
$daily_activity = $pdo->query("
    SELECT DATE(waktu_masuk) as tanggal, 
           COUNT(*) as total,
           SUM(CASE WHEN status = 'parkir' THEN 1 ELSE 0 END) as aktif
    FROM log_parkir 
    WHERE waktu_masuk >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(waktu_masuk)
    ORDER BY tanggal DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Jam Sibuk
$peak_hours = $pdo->query("
    SELECT HOUR(waktu_masuk) as jam, COUNT(*) as total
    FROM log_parkir 
    WHERE DATE(waktu_masuk) = CURDATE()
    GROUP BY HOUR(waktu_masuk)
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
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
                <li><a href="index.php">Admin Dashboard</a></li>
                <li><a href="users.php">Kelola User</a></li>
                <li><a href="areas.php">Kelola Area</a></li>
                <li><a href="reports.php" class="active">Laporan</a></li>
                <li><a href="logs.php">Log Aktivitas</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">📊 Laporan & Statistik</h1>
                <p class="card-subtitle">Analisis data sistem parkir kampus</p>
            </div>

            <!-- Filter Tanggal -->
            <div class="filter-form">
                <form method="GET" class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-input" value="<?= $start_date ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-input" value="<?= $end_date ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Statistik Utama -->
            <h3>Statistik Utama</h3>
            <div class="grid grid-4 mb-3">
                <div class="card stat-card status-available">
                    <div class="stat-number"><?= $total_parkir ?></div>
                    <div class="stat-label">Total Parkir</div>
                    <small>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></small>
                </div>
                <div class="card stat-card status-occupied">
                    <div class="stat-number"><?= $parkir_aktif ?></div>
                    <div class="stat-label">Sedang Parkir</div>
                </div>
                <div class="card stat-card status-available">
                    <div class="stat-number"><?= $total_motor ?></div>
                    <div class="stat-label">Total Motor</div>
                </div>
                <div class="card stat-card status-occupied">
                    <div class="stat-number"><?= $total_mobil ?></div>
                    <div class="stat-label">Total Mobil</div>
                </div>
            </div>

            <!-- Grid Laporan -->
            <div class="grid grid-2">
                <!-- Statistik per Area -->
                <div class="card">
                    <h3>Statistik per Area Parkir</h3>
                    <?php if (!empty($area_stats)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Area</th>
                                    <th>Jenis</th>
                                    <th>Total Parkir</th>
                                    <th>Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($area_stats as $area): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($area['nama_area']) ?></td>
                                        <td><?= ucfirst($area['jenis_kendaraan']) ?></td>
                                        <td><?= $area['total_parkir'] ?></td>
                                        <td>
                                            <span style="color: <?= $area['aktif'] > 0 ? '#28a745' : '#666' ?>; font-weight: bold;">
                                                <?= $area['aktif'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center">Tidak ada data area parkir.</p>
                    <?php endif; ?>
                </div>

                <!-- Statistik per Role -->
                <div class="card">
                    <h3>Statistik per Role User</h3>
                    <?php if (!empty($role_stats)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Total Parkir</th>
                                    <th>Total Kendaraan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($role_stats as $role): ?>
                                    <tr>
                                        <td>
                                            <span class="role-badge role-<?= $role['role'] ?>">
                                                <?= strtoupper($role['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= $role['total_parkir'] ?></td>
                                        <td><?= $role['total_kendaraan'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center">Tidak ada data role user.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aktivitas Harian -->
            <div class="card mt-3">
                <h3>Aktivitas 7 Hari Terakhir</h3>
                <?php if (!empty($daily_activity)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Total Parkir</th>
                                <th>Masih Parkir</th>
                                <th>Tingkat Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_activity as $day): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($day['tanggal'])) ?></td>
                                    <td><?= $day['total'] ?></td>
                                    <td><?= $day['aktif'] ?></td>
                                    <td>
                                        <?php
                                        $percentage = $day['total'] > 0 ? ($day['aktif'] / $day['total']) * 100 : 0;
                                        $color = $percentage > 70 ? '#28a745' : ($percentage > 30 ? '#ffc107' : '#dc3545');
                                        ?>
                                        <div style="background: #e9ecef; height: 8px; border-radius: 4px; width: 100px; display: inline-block; margin-right: 10px;">
                                            <div style="background: <?= $color ?>; height: 100%; width: <?= $percentage ?>%; border-radius: 4px;"></div>
                                        </div>
                                        <?= number_format($percentage, 1) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">Tidak ada aktivitas dalam 7 hari terakhir.</p>
                <?php endif; ?>
            </div>

            <!-- Jam Sibuk -->
            <div class="card mt-3">
                <h3>Jam Sibuk Hari Ini</h3>
                <?php if (!empty($peak_hours)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Total Parkir</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_today = array_sum(array_column($peak_hours, 'total'));
                            foreach ($peak_hours as $hour): 
                                $percentage = $total_today > 0 ? ($hour['total'] / $total_today) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?= $hour['jam'] . ':00 - ' . ($hour['jam'] + 1) . ':00' ?></td>
                                    <td><?= $hour['total'] ?></td>
                                    <td>
                                        <div style="background: #e9ecef; height: 8px; border-radius: 4px; width: 100px; display: inline-block; margin-right: 10px;">
                                            <div style="background: #667eea; height: 100%; width: <?= $percentage ?>%; border-radius: 4px;"></div>
                                        </div>
                                        <?= number_format($percentage, 1) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">Tidak ada data jam sibuk hari ini.</p>
                <?php endif; ?>
            </div>

            <div class="mt-3">
                <a href="index.php" class="btn btn-secondary">Kembali ke Dashboard</a>
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
</script>
</body>
</html>