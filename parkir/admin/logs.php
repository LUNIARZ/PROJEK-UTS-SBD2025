<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pdo = getConnection();

// Filter
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_jenis = $_GET['jenis'] ?? '';

// Query dasar dengan filter
$sql = "
    SELECT lp.*, u.nama_lengkap, u.username, u.role, k.no_plat, k.jenis_kendaraan, 
           k.merk, ap.nama_area, ap.jenis_kendaraan as area_jenis
    FROM log_parkir lp
    JOIN users u ON lp.user_id = u.id
    JOIN kendaraan k ON lp.kendaraan_id = k.id
    JOIN area_parkir ap ON lp.area_parkir_id = ap.id
    WHERE 1=1
";

$params = [];

if (!empty($filter_user)) {
    $sql .= " AND (u.nama_lengkap LIKE ? OR u.username LIKE ?)";
    $params[] = "%$filter_user%";
    $params[] = "%$filter_user%";
}

if (!empty($filter_status)) {
    $sql .= " AND lp.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_date)) {
    $sql .= " AND DATE(lp.waktu_masuk) = ?";
    $params[] = $filter_date;
}

if (!empty($filter_jenis)) {
    $sql .= " AND k.jenis_kendaraan = ?";
    $params[] = $filter_jenis;
}

$sql .= " ORDER BY lp.waktu_masuk DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_logs = count($logs);
$parkir_aktif = array_filter($logs, fn($log) => $log['status'] === 'parkir');
$total_aktif = count($parkir_aktif);
$motor_logs = array_filter($logs, fn($log) => $log['jenis_kendaraan'] === 'motor');
$mobil_logs = array_filter($logs, fn($log) => $log['jenis_kendaraan'] === 'mobil');
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
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="logs.php" class="active">Log Aktivitas</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">📝 Log Aktivitas Parkir</h1>
                <p class="card-subtitle">Riwayat lengkap aktivitas parkir sistem</p>
            </div>

            <!-- Statistik Cepat -->
            <div class="grid grid-4 mb-3">
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_logs ?></div>
                    <div class="status-label">Total Log</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= $total_aktif ?></div>
                    <div class="status-label">Parkir Aktif</div>
                </div>
                <div class="status-card status-available">
                    <div class="status-number"><?= count($motor_logs) ?></div>
                    <div class="status-label">Motor</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= count($mobil_logs) ?></div>
                    <div class="status-label">Mobil</div>
                </div>
            </div>

            <!-- Filter -->
            <div class="filter-form">
                <form method="GET" class="grid grid-5">
                    <div class="form-group">
                        <label class="form-label">Cari User</label>
                        <input type="text" name="user" class="form-input" placeholder="Nama atau username..." value="<?= htmlspecialchars($filter_user) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="parkir" <?= $filter_status === 'parkir' ? 'selected' : '' ?>>Parkir</option>
                            <option value="keluar" <?= $filter_status === 'keluar' ? 'selected' : '' ?>>Keluar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kendaraan</label>
                        <select name="jenis" class="form-select">
                            <option value="">Semua Jenis</option>
                            <option value="motor" <?= $filter_jenis === 'motor' ? 'selected' : '' ?>>Motor</option>
                            <option value="mobil" <?= $filter_jenis === 'mobil' ? 'selected' : '' ?>>Mobil</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-input" value="<?= htmlspecialchars($filter_date) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="logs.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Log -->
            <h3>Riwayat Aktivitas (<?= $total_logs ?> log)</h3>
            
            <?php if (empty($logs)): ?>
                <div class="alert alert-warning">
                    Tidak ada data log yang ditemukan.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table log-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Kendaraan</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong>Masuk:</strong> <?= date('d/m/Y H:i', strtotime($log['waktu_masuk'])) ?><br>
                                        <?php if ($log['waktu_keluar']): ?>
                                            <strong>Keluar:</strong> <?= date('d/m/Y H:i', strtotime($log['waktu_keluar'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['nama_lengkap']) ?><br>
                                        <small style="color: #666;">@<?= htmlspecialchars($log['username']) ?></small>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?= $log['role'] ?>">
                                            <?= strtoupper($log['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['no_plat']) ?></strong><br>
                                        <?= ucfirst($log['jenis_kendaraan']) ?> • <?= htmlspecialchars($log['merk']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['nama_area']) ?><br>
                                        <small><?= ucfirst($log['area_jenis']) ?></small>
                                    </td>
                                    <td>
                                        <span class="<?= $log['status'] === 'parkir' ? 'status-parkir' : 'status-keluar' ?>">
                                            <?= strtoupper($log['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($log['waktu_keluar']) {
                                            $durasi = strtotime($log['waktu_keluar']) - strtotime($log['waktu_masuk']);
                                            $jam = floor($durasi / 3600);
                                            $menit = floor(($durasi % 3600) / 60);
                                            echo $jam . 'j ' . $menit . 'm';
                                        } else {
                                            $durasi = time() - strtotime($log['waktu_masuk']);
                                            $jam = floor($durasi / 3600);
                                            $menit = floor(($durasi % 3600) / 60);
                                            echo '<span style="color: #28a745;">' . $jam . 'j ' . $menit . 'm</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Info -->
                <div style="text-align: center; margin-top: 1rem; color: #666;">
                    Menampilkan <?= min(200, $total_logs) ?> log terbaru
                    <?php if ($total_logs > 200): ?>
                        <br><small>(Gunakan filter untuk melihat log lebih spesifik)</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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