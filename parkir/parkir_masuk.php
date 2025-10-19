<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getConnection();
$error = '';
$success = '';

// Jika admin, ambil semua kendaraan. Jika user biasa, hanya kendaraan miliknya
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT k.*, u.nama_lengkap FROM kendaraan k JOIN users u ON k.user_id = u.id");
    $stmt->execute();
    $kendaraan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT * FROM kendaraan WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $kendaraan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kendaraan_id = $_POST['kendaraan_id'] ?? '';
    $area_parkir_id = $_POST['area_parkir_id'] ?? '';
    $user_id = isAdmin() ? ($_POST['user_id'] ?? $_SESSION['user_id']) : $_SESSION['user_id'];
    
    if (empty($kendaraan_id) || empty($area_parkir_id)) {
        $error = 'Mohon pilih kendaraan dan area parkir!';
    } else {
        // Cek apakah kendaraan sudah parkir
        $stmt = $pdo->prepare("SELECT id FROM log_parkir WHERE kendaraan_id = ? AND status = 'parkir'");
        $stmt->execute([$kendaraan_id]);
        if ($stmt->fetch()) {
            $error = 'Kendaraan ini sudah parkir!';
        } else {
            // Cek kapasitas area parkir
            $stmt = $pdo->prepare("SELECT * FROM area_parkir WHERE id = ?");
            $stmt->execute([$area_parkir_id]);
            $area = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$area) {
                $error = 'Area parkir tidak ditemukan!';
            } elseif ($area['terisi'] >= $area['kapasitas']) {
                $error = 'Area parkir penuh!';
            } else {
                // Insert log parkir
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("INSERT INTO log_parkir (user_id, kendaraan_id, area_parkir_id) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $kendaraan_id, $area_parkir_id]);
                    
                    // Update jumlah terisi
                    $stmt = $pdo->prepare("UPDATE area_parkir SET terisi = terisi + 1 WHERE id = ?");
                    $stmt->execute([$area_parkir_id]);
                    
                    $pdo->commit();
                    $success = 'Kendaraan berhasil diparkir!';
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = 'Gagal memproses parkir: ' . $e->getMessage();
                }
            }
        }
    }
}

// Ambil area parkir yang tersedia
$areas = $pdo->query("SELECT * FROM area_parkir WHERE status = 'aktif' AND terisi < kapasitas")->fetchAll(PDO::FETCH_ASSOC);

// Jika admin, ambil daftar user untuk dropdown
$users = [];
if (isAdmin()) {
    $users = $pdo->query("SELECT id, nama_lengkap, role FROM users WHERE status = 'aktif'")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parkir Masuk - Sistem Parkir Kampus</title>
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
                <li><a href="parkir_masuk.php" class="active">Parkir Masuk</a></li>
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
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h1 class="card-title">🚗 Parkir Masuk</h1>
                <p class="card-subtitle">
                    <?php if (isAdmin()): ?>
                        [ADMIN] - Kelola parkir semua user
                    <?php else: ?>
                        Pilih kendaraan dan area parkir
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($kendaraan_list)): ?>
                <div class="alert alert-warning">
                    <?php if (isAdmin()): ?>
                        Tidak ada kendaraan terdaftar di sistem.
                    <?php else: ?>
                        Anda belum mendaftarkan kendaraan. <a href="kendaraan.php">Daftar kendaraan</a> terlebih dahulu.
                    <?php endif; ?>
                </div>
            <?php elseif (empty($areas)): ?>
                <div class="alert alert-warning">
                    Semua area parkir sedang penuh. Silakan coba lagi nanti.
                </div>
            <?php else: ?>
                <form method="POST">
                    <?php if (isAdmin()): ?>
                        <div class="form-group">
                            <label class="form-label">Pilih User</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['nama_lengkap']) ?> (<?= strtoupper($user['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Pilih Kendaraan</label>
                        <select name="kendaraan_id" class="form-select" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            <?php foreach ($kendaraan_list as $kendaraan): ?>
                                <option value="<?= $kendaraan['id'] ?>">
                                    <?= htmlspecialchars($kendaraan['no_plat']) ?> - 
                                    <?= ucfirst($kendaraan['jenis_kendaraan']) ?> 
                                    <?= htmlspecialchars($kendaraan['merk']) ?>
                                    <?php if (isAdmin()): ?>
                                        (<?= htmlspecialchars($kendaraan['nama_lengkap']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pilih Area Parkir</label>
                        <select name="area_parkir_id" class="form-select" required>
                            <option value="">-- Pilih Area Parkir --</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id'] ?>">
                                    <?= htmlspecialchars($area['nama_area']) ?> 
                                    (<?= ucfirst($area['jenis_kendaraan']) ?>) - 
                                    Tersedia: <?= $area['kapasitas'] - $area['terisi'] ?>/<?= $area['kapasitas'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success btn-full">Parkir Sekarang</button>
                </form>
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