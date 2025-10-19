<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pdo = getConnection();
$error = '';
$success = '';

// Proses Tambah Area Parkir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama_area = trim($_POST['nama_area'] ?? '');
    $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? '';
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    
    if (empty($nama_area) || empty($jenis_kendaraan) || $kapasitas <= 0) {
        $error = 'Mohon isi semua field dengan benar!';
    } else {
        // Cek apakah nama area sudah ada
        $stmt = $pdo->prepare("SELECT id FROM area_parkir WHERE nama_area = ?");
        $stmt->execute([$nama_area]);
        if ($stmt->fetch()) {
            $error = 'Nama area sudah digunakan!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO area_parkir (nama_area, jenis_kendaraan, kapasitas, terisi, status) VALUES (?, ?, ?, 0, 'aktif')");
            if ($stmt->execute([$nama_area, $jenis_kendaraan, $kapasitas])) {
                $success = 'Area parkir berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan area parkir!';
            }
        }
    }
}

// Proses Edit Area Parkir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $area_id = (int)($_POST['area_id'] ?? 0);
    $nama_area = trim($_POST['nama_area'] ?? '');
    $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? '';
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    $status = $_POST['status'] ?? 'aktif';
    
    if (empty($nama_area) || empty($jenis_kendaraan) || $kapasitas <= 0) {
        $error = 'Mohon isi semua field dengan benar!';
    } else {
        // Cek apakah nama area sudah digunakan oleh area lain
        $stmt = $pdo->prepare("SELECT id FROM area_parkir WHERE nama_area = ? AND id != ?");
        $stmt->execute([$nama_area, $area_id]);
        if ($stmt->fetch()) {
            $error = 'Nama area sudah digunakan!';
        } else {
            $stmt = $pdo->prepare("UPDATE area_parkir SET nama_area = ?, jenis_kendaraan = ?, kapasitas = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$nama_area, $jenis_kendaraan, $kapasitas, $status, $area_id])) {
                $success = 'Area parkir berhasil diperbarui!';
            } else {
                $error = 'Gagal memperbarui area parkir!';
            }
        }
    }
}

// Proses Hapus Area Parkir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $area_id = (int)($_POST['area_id'] ?? 0);
    
    // Cek apakah area sedang digunakan
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM log_parkir WHERE area_parkir_id = ? AND status = 'parkir'");
    $stmt->execute([$area_id]);
    $used = $stmt->fetchColumn();
    
    if ($used > 0) {
        $error = 'Tidak dapat menghapus area yang sedang digunakan untuk parkir!';
    } else {
        $stmt = $pdo->prepare("DELETE FROM area_parkir WHERE id = ?");
        if ($stmt->execute([$area_id])) {
            $success = 'Area parkir berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus area parkir!';
        }
    }
}

// Ambil semua area parkir
$areas = $pdo->query("SELECT * FROM area_parkir ORDER BY status, nama_area")->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_areas = count($areas);
$active_areas = array_filter($areas, fn($area) => $area['status'] === 'aktif');
$motor_areas = array_filter($areas, fn($area) => $area['jenis_kendaraan'] === 'motor');
$mobil_areas = array_filter($areas, fn($area) => $area['jenis_kendaraan'] === 'mobil');
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
                <li><a href="areas.php" class="active">Kelola Area</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="logs.php">Log Aktivitas</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">🅿️ Kelola Area Parkir</h1>
                <p class="card-subtitle">Manajemen area parkir kampus</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Statistik Area -->
            <div class="grid grid-4 mb-3">
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_areas ?></div>
                    <div class="status-label">Total Area</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= count($active_areas) ?></div>
                    <div class="status-label">Aktif</div>
                </div>
                <div class="status-card status-available">
                    <div class="status-number"><?= count($motor_areas) ?></div>
                    <div class="status-label">Area Motor</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= count($mobil_areas) ?></div>
                    <div class="status-label">Area Mobil</div>
                </div>
            </div>

            <!-- Form Tambah Area -->
            <div class="card mb-3">
                <h3>Tambah Area Parkir Baru</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label class="form-label">Nama Area *</label>
                            <input type="text" name="nama_area" class="form-input" required 
                                   placeholder="Contoh: Area A, Parkir Utara">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis Kendaraan *</label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="motor">Motor</option>
                                <option value="mobil">Mobil</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kapasitas *</label>
                            <input type="number" name="kapasitas" class="form-input" required 
                                   min="1" max="1000" placeholder="Jumlah kendaraan">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Area</button>
                </form>
            </div>

            <!-- Daftar Area Parkir -->
<h3>Daftar Area Parkir (<?= $total_areas ?>)</h3>

<?php if (empty($areas)): ?>
    <div class="alert alert-warning">
        Belum ada area parkir yang terdaftar.
    </div>
<?php else: ?>
    <div class="grid grid-2">
        <?php foreach ($areas as $area): ?>
            <!-- Pastikan class area-aktif/area-nonaktif diterapkan dengan benar -->
            <div class="card area-card <?= $area['status'] === 'aktif' ? 'area-aktif' : 'area-nonaktif' ?>">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <h4 style="margin: 0; flex: 1;"><?= htmlspecialchars($area['nama_area']) ?></h4>
                    <span class="status-badge <?= $area['status'] === 'aktif' ? 'status-aktif' : 'status-nonaktif' ?>">
                        <?= strtoupper($area['status']) ?>
                    </span>
                </div>
                
                <div class="vehicle-meta">
                    <p><strong>Jenis:</strong> <?= ucfirst($area['jenis_kendaraan']) ?></p>
                    <p><strong>Kapasitas:</strong> <?= $area['terisi'] ?>/<?= $area['kapasitas'] ?></p>
                    <p><strong>Persentase:</strong> 
                        <?= number_format(($area['terisi'] / $area['kapasitas']) * 100, 1) ?>%
                    </p>
                </div>

                <!-- Progress Bar -->
                <?php if ($area['status'] === 'aktif'): ?>
                <div style="background: #e9ecef; height: 8px; border-radius: 4px; margin: 10px 0;">
                    <div style="background: <?= $area['terisi'] >= $area['kapasitas'] ? '#dc3545' : '#28a745' ?>; 
                                height: 100%; 
                                width: <?= min(100, ($area['terisi'] / $area['kapasitas']) * 100) ?>%; 
                                border-radius: 4px;"></div>
                </div>
                <?php else: ?>
                <div style="background: #e9ecef; height: 8px; border-radius: 4px; margin: 10px 0;">
                    <div style="background: #6c757d; 
                                height: 100%; 
                                width: 100%; 
                                border-radius: 4px;"></div>
                </div>
                <p style="color: #dc3545; font-size: 0.8rem; margin-top: 5px; text-align: center;">
                    ⚠️ Area Nonaktif - Tidak dapat digunakan untuk parkir
                </p>
                <?php endif; ?>

                <!-- Form Edit -->
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="area_id" value="<?= $area['id'] ?>">
                    
                    <div class="grid grid-2" style="gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" name="nama_area" class="form-input" value="<?= htmlspecialchars($area['nama_area']) ?>" required>
                        <select name="jenis_kendaraan" class="form-select" required>
                            <option value="motor" <?= $area['jenis_kendaraan'] === 'motor' ? 'selected' : '' ?>>Motor</option>
                            <option value="mobil" <?= $area['jenis_kendaraan'] === 'mobil' ? 'selected' : '' ?>>Mobil</option>
                        </select>
                        <input type="number" name="kapasitas" class="form-input" value="<?= $area['kapasitas'] ?>" min="1" required>
                        <select name="status" class="form-select" required>
                            <option value="aktif" <?= $area['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $area['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-success" style="flex: 1;">Update</button>
                        <button type="button" onclick="if(confirm('Yakin menghapus area ini?')) { document.getElementById('delete-form-<?= $area['id'] ?>').submit(); }" 
                                class="btn btn-danger" <?= $area['terisi'] > 0 ? 'disabled' : '' ?>>Hapus</button>
                    </div>
                </form>

                <!-- Form Hapus (Hidden) -->
                <form id="delete-form-<?= $area['id'] ?>" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="area_id" value="<?= $area['id'] ?>">
                </form>
            </div>
        <?php endforeach; ?>
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