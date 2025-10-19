<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getConnection();
$error = '';
$success = '';

// Jika admin, ambil semua kendaraan yang parkir. Jika user biasa, hanya miliknya
if (isAdmin()) {
    $stmt = $pdo->prepare("
        SELECT lp.*, k.no_plat, k.jenis_kendaraan, k.merk, ap.nama_area, lp.waktu_masuk, u.nama_lengkap
        FROM log_parkir lp
        JOIN kendaraan k ON lp.kendaraan_id = k.id
        JOIN area_parkir ap ON lp.area_parkir_id = ap.id
        JOIN users u ON lp.user_id = u.id
        WHERE lp.status = 'parkir'
        ORDER BY lp.waktu_masuk DESC
    ");
    $stmt->execute();
    $kendaraan_parkir = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT lp.*, k.no_plat, k.jenis_kendaraan, k.merk, ap.nama_area, lp.waktu_masuk
        FROM log_parkir lp
        JOIN kendaraan k ON lp.kendaraan_id = k.id
        JOIN area_parkir ap ON lp.area_parkir_id = ap.id
        WHERE lp.user_id = ? AND lp.status = 'parkir'
        ORDER BY lp.waktu_masuk DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $kendaraan_parkir = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id = $_POST['log_id'] ?? '';
    
    if (empty($log_id)) {
        $error = 'Mohon pilih kendaraan yang akan keluar!';
    } else {
        // Jika admin, tidak perlu cek user_id. Jika user biasa, cek kepemilikan
        if (isAdmin()) {
            $stmt = $pdo->prepare("SELECT * FROM log_parkir WHERE id = ? AND status = 'parkir'");
            $stmt->execute([$log_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM log_parkir WHERE id = ? AND user_id = ? AND status = 'parkir'");
            $stmt->execute([$log_id, $_SESSION['user_id']]);
        }
        
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$log) {
            $error = 'Data parkir tidak ditemukan!';
        } else {
            // Update log parkir dan area parkir
            $pdo->beginTransaction();
            try {
                // Update log parkir
                $stmt = $pdo->prepare("UPDATE log_parkir SET waktu_keluar = NOW(), status = 'keluar' WHERE id = ?");
                $stmt->execute([$log_id]);
                
                // Update jumlah terisi area parkir
                $stmt = $pdo->prepare("UPDATE area_parkir SET terisi = terisi - 1 WHERE id = ?");
                $stmt->execute([$log['area_parkir_id']]);
                
                $pdo->commit();
                $success = 'Kendaraan berhasil keluar dari parkir!';
                
                // Refresh data
                if (isAdmin()) {
                    $stmt = $pdo->prepare("
                        SELECT lp.*, k.no_plat, k.jenis_kendaraan, k.merk, ap.nama_area, lp.waktu_masuk, u.nama_lengkap
                        FROM log_parkir lp
                        JOIN kendaraan k ON lp.kendaraan_id = k.id
                        JOIN area_parkir ap ON lp.area_parkir_id = ap.id
                        JOIN users u ON lp.user_id = u.id
                        WHERE lp.status = 'parkir'
                        ORDER BY lp.waktu_masuk DESC
                    ");
                    $stmt->execute();
                } else {
                    $stmt = $pdo->prepare("
                        SELECT lp.*, k.no_plat, k.jenis_kendaraan, k.merk, ap.nama_area, lp.waktu_masuk
                        FROM log_parkir lp
                        JOIN kendaraan k ON lp.kendaraan_id = k.id
                        JOIN area_parkir ap ON lp.area_parkir_id = ap.id
                        WHERE lp.user_id = ? AND lp.status = 'parkir'
                        ORDER BY lp.waktu_masuk DESC
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                $kendaraan_parkir = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $pdo->rollback();
                $error = 'Gagal memproses keluar parkir: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parkir Keluar - Sistem Parkir Kampus</title>
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
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="parkir_masuk.php">Parkir Masuk</a></li>
            <li><a href="parkir_keluar.php" class="active">Parkir Keluar</a></li>
            <li><a href="kendaraan.php">Informasi Kendaraan</a></li>
            <?php if (isAdmin()): ?>
                <li><a href="admin/">Admin</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

    <div class="container">
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h1 class="card-title">🚪 Parkir Keluar</h1>
                <p class="card-subtitle">
                    <?php if (isAdmin()): ?>
                        [ADMIN] - Kelola keluar parkir semua user
                    <?php else: ?>
                        Pilih kendaraan yang akan keluar
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($kendaraan_parkir)): ?>
                <div class="alert alert-warning">
                    Tidak ada kendaraan yang sedang parkir.
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Pilih Kendaraan yang Akan Keluar</label>
                        <select name="log_id" class="form-select" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            <?php foreach ($kendaraan_parkir as $kendaraan): ?>
                                <option value="<?= $kendaraan['id'] ?>">
                                    <?= htmlspecialchars($kendaraan['no_plat']) ?> - 
                                    <?= ucfirst($kendaraan['jenis_kendaraan']) ?> 
                                    <?= htmlspecialchars($kendaraan['merk']) ?> - 
                                    <?= htmlspecialchars($kendaraan['nama_area']) ?> - 
                                    Masuk: <?= date('d/m/Y H:i', strtotime($kendaraan['waktu_masuk'])) ?>
                                    <?php if (isAdmin()): ?>
                                        (<?= htmlspecialchars($kendaraan['nama_lengkap']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-danger btn-full">Keluar Parkir</button>
                </form>

 <h3 class="mt-3 mb-2">Detail Kendaraan yang Sedang Parkir</h3>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <?php if (isAdmin()): ?>
                    <th>Pemilik</th>
                <?php endif; ?>
                <th>No. Plat</th>
                <th>Jenis</th>
                <th>Merk</th>
                <th>Area</th>
                <th>Waktu Masuk</th>
                <th>Durasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kendaraan_parkir as $kendaraan): ?>
                <tr>
                    <?php if (isAdmin()): ?>
                        <td><?= htmlspecialchars($kendaraan['nama_lengkap']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($kendaraan['no_plat']) ?></td>
                    <td><?= ucfirst($kendaraan['jenis_kendaraan']) ?></td>
                    <td><?= htmlspecialchars($kendaraan['merk']) ?></td>
                    <td><?= htmlspecialchars($kendaraan['nama_area']) ?></td>
                    <td class="mobile-friendly"><?= date('d/m/y H:i', strtotime($kendaraan['waktu_masuk'])) ?></td>
                    <td class="mobile-friendly">
                        <?php
                        $durasi = time() - strtotime($kendaraan['waktu_masuk']);
                        $jam = floor($durasi / 3600);
                        $menit = floor(($durasi % 3600) / 60);
                        echo $jam . 'j ' . $menit . 'm';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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