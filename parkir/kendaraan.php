<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getConnection();
$error = '';
$success = '';

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$currentUserRole = $_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? null;

if (!$currentUserRole && $currentUserId) {
    $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$currentUserId]);
    $r = $stmtRole->fetch(PDO::FETCH_ASSOC);

    if ($r) {
        $currentUserRole = $r['role'];
        }
}

if ($currentUserRole) {
    $currentUserRole = strtolower($currentUserRole);
}

// Diambil daftar users(hanya untuk admin)
$users = [];
if ($currentUserRole === 'admin') {
        $stmtUsers = $pdo->prepare("SELECT id, nama_lengkap, role FROM users WHERE status = 'aktif' ORDER BY nama_lengkap");
            $stmtUsers->execute();
                $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
}

// Proses tambah kendaraan (tetap seperti asal, menggunakan user_id dari session) 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $no_plat = strtoupper(trim($_POST['no_plat'] ?? ''));
             $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? '';
                $merk = trim($_POST['merk'] ?? '');
                    $warna = trim($_POST['warna'] ?? '');
    
    // Jika admin, gunakan user_id dari form. Jika bukan admin, gunakan current user
    if ($currentUserRole === 'admin') {
        $user_id = $_POST['user_id'] ?? $currentUserId;
    } 
    else {
        $user_id = $currentUserId;
    }

    // Validasi
    if (empty($no_plat) || empty($jenis_kendaraan) || empty($merk)) {
        $error = 'Mohon isi semua field yang wajib!';
    } 
    else if ($currentUserRole === 'admin' && empty($user_id)) {
        $error = 'Mohon pilih user pemilik kendaraan!';
    } 
    else {
        // Cek apakah no plat sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM kendaraan WHERE no_plat = ?");
            $stmt->execute([$no_plat]);
                if ($stmt->fetch()) {
                     $error = 'Nomor plat sudah terdaftar!';
                } 
                else {
        
        // Insert menggunakan user_id (pastikan tidak null)
            if (!$user_id) {
                $error = 'User tidak dikenali. Silakan login ulang.';
            } 
            else {
                 $stmt = $pdo->prepare("INSERT INTO kendaraan (user_id, no_plat, jenis_kendaraan, merk, warna) VALUES (?, ?, ?, ?, ?)");
                
             if ($stmt->execute([$user_id, $no_plat, $jenis_kendaraan, $merk, $warna])) {
                    $success = 'Kendaraan berhasil didaftarkan!';
                } 
            else {
                $error = 'Gagal mendaftarkan kendaraan!';
                }
            }
        }
    }
}

// --- Proses hapus kendaraan ---
// Admin dapat menghapus semua kendaraan; 
// user biasa hanya kendaraannya sendiri.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $kendaraan_id = $_POST['kendaraan_id'] ?? '';

    if ($kendaraan_id) {
        // Pertama: cek apakah kendaraan sedang parkir
            $stmt = $pdo->prepare("SELECT id FROM log_parkir WHERE kendaraan_id = ? AND status = 'parkir'");
                $stmt->execute([$kendaraan_id]);
                
            if ($stmt->fetch()) {
                $error = 'Tidak dapat menghapus kendaraan yang sedang parkir!';
             } 
            else {

        // Jika bukan admin, pastikan kendaraan milik current user
            if ($currentUserRole !== 'admin') {
                    $stmtCheckOwner = $pdo->prepare("SELECT user_id FROM kendaraan WHERE id = ?");
                        $stmtCheckOwner->execute([$kendaraan_id]);
                            $owner = $stmtCheckOwner->fetch(PDO::FETCH_ASSOC);
                if (!$owner || $owner['user_id'] != $currentUserId) {
                $error = 'Kendaraan tidak ditemukan atau bukan milik Anda.';
            }
    }

            if (empty($error)) {
                // Mulai transaksi untuk menghapus kendaraan dan semua log terkait
                    $pdo->beginTransaction();

                    try {
                        // Hapus semua log parkir terkait kendaraan ini
                            $stmtDelLogs = $pdo->prepare("DELETE FROM log_parkir WHERE kendaraan_id = ?");
                             $stmtDelLogs->execute([$kendaraan_id]);

                        // Hapus kendaraan - jika admin, hapus tanpa mengecek user_id; jika bukan admin, hapus dengan user_id untuk safety
                    if ($currentUserRole === 'admin') {
                            $stmtDelKend = $pdo->prepare("DELETE FROM kendaraan WHERE id = ?");
                                 $stmtDelKend->execute([$kendaraan_id]);
                    } 
                    else {
                        $stmtDelKend = $pdo->prepare("DELETE FROM kendaraan WHERE id = ? AND user_id = ?");
                            $stmtDelKend->execute([$kendaraan_id, $currentUserId]);
                    }

                        $pdo->commit();
                            $success = 'Kendaraan dan riwayat parkir berhasil dihapus!';
                } 
                    catch (Exception $e) {
                        $pdo->rollback();
                        // Jangan expose detail exception ke user production; tampilkan pesan generik
                        $error = 'Gagal menghapus kendaraan. Silakan coba lagi.';
                }
            }
        }
    }
}

// Ambil daftar kendaraan sesuai role
// Jika admin: ambil semua kendaraan, JOIN ke users untuk mendapatkan role pemilik.
// Jika bukan admin: ambil hanya kendaraan milik current user (tetap JOIN ke users untuk dapatkan role).
try {
    if ($currentUserRole === 'admin') {
         $stmt = $pdo->prepare("SELECT kendaraan.*, users.nama_lengkap, users.role AS owner_role FROM kendaraan LEFT JOIN users ON kendaraan.user_id = users.id ORDER BY kendaraan.created_at DESC");
             $stmt->execute();
    } 
    else {
        // pastikan currentUserId ada
             if (!$currentUserId) {
                // no user id: kosongkan list
                 $kendaraan_list = [];
        } 
            else {
                 $stmt = $pdo->prepare("SELECT kendaraan.*, users.nama_lengkap, users.role AS owner_role FROM kendaraan LEFT JOIN users ON kendaraan.user_id = users.id WHERE kendaraan.user_id = ? ORDER BY kendaraan.created_at DESC");
                    $stmt->execute([$currentUserId]);
            }
    }

        // Jika statement ada, fetchAll; jika tidak (no user) set array kosong
            if (isset($stmt)) {
                $kendaraan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } 
            else {
                $kendaraan_list = [];
            }
} 
    
    catch (Exception $e) {
        $kendaraan_list = [];
            $error = 'Gagal mengambil data kendaraan.';
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kendaraan Saya - Sistem Parkir Kampus</title>
    <link rel="stylesheet" href="style.css">
        <style>
            .vehicle-card { padding: 1rem; border-radius: 12px; background: rgba(255,255,255,0.95); margin-bottom: 1rem; }
            .vehicle-plate { font-size: 1.3rem; font-weight: 800; margin-bottom: 0.25rem; }
            .vehicle-meta { color: #444; font-size: 0.95rem; }
            .admin-badge { 
                background: #dc3545; 
                color: white; 
                padding: 2px 8px; 
                border-radius: 4px; 
                font-size: 0.8rem; 
                margin-left: 8px;
                font-weight: bold;
            }
        </style>
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
                        <li><a href="parkir_keluar.php">Parkir Keluar</a></li>
                        <li><a href="kendaraan.php" class="active">Informasi Kendaraan</a></li>
                            <?php if (isAdmin()): ?>
                        <li><a href="admin/">Admin</a></li>
                            <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Informasi Kendaraan</h1>
                <p class="card-subtitle">
                    <?php if ($currentUserRole === 'admin'): ?>
                        [ADMIN] - Kelola kendaraan semua user
                    <?php else: ?>
                        Kelola kendaraan yang terdaftar
                    <?php endif; ?>
                </p>
            </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

                <!-- Form Tambah Kendaraan -->
                    <div class="card mb-3">
                        <h3>
                            <?php if ($currentUserRole === 'admin'): ?>
                                Daftarkan Kendaraan Baru untuk User
                            <?php else: ?>
                                Daftarkan Kendaraan Baru
                            <?php endif; ?>
                        </h3>

                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                                <div class="grid grid-2">
                                    <?php if ($currentUserRole === 'admin'): ?>
                                        <div class="form-group">
                                            <label class="form-label">Pilih User Pemilik *</label>
                                                <select name="user_id" class="form-select" required>
                                                    <option value="">-- Pilih User --</option>
                                                    <?php foreach ($users as $user): ?>
                                                        <option value="<?= $user['id'] ?>" <?= (($_POST['user_id'] ?? '') == $user['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($user['nama_lengkap']) ?> (<?= strtoupper($user['role']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                        </div>
                                    <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Nomor Plat *</label>
                                <input type="text" name="no_plat" class="form-input" required 
                                    placeholder="Contoh: B1234ABC" style="text-transform: uppercase;"
                                        value="<?= htmlspecialchars($_POST['no_plat'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jenis Kendaraan *</label>
                                <select name="jenis_kendaraan" class="form-select" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="motor" <?= (($_POST['jenis_kendaraan'] ?? '') === 'motor') ? 'selected' : '' ?>>Motor</option>
                                    <option value="mobil" <?= (($_POST['jenis_kendaraan'] ?? '') === 'mobil') ? 'selected' : '' ?>>Mobil</option>
                                </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Merk *</label>
                                <input type="text" name="merk" class="form-input" required 
                                        placeholder="Contoh: Honda, Toyota"
                                            value="<?= htmlspecialchars($_POST['merk'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Warna</label>
                                <input type="text" name="warna" class="form-input" 
                                    placeholder="Contoh: Hitam, Putih"
                                        value="<?= htmlspecialchars($_POST['warna'] ?? '') ?>">
                        </div>
                    </div>

                            <button type="submit" class="btn btn-primary">
                                <?php if ($currentUserRole === 'admin'): ?>
                                    Daftarkan Kendaraan untuk User
                                <?php else: ?>
                                    Daftarkan Kendaraan
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>

                <!-- Daftar Kendaraan -->
                    <h3>
                        <?php if ($currentUserRole === 'admin'): ?>
                            Semua Kendaraan Terdaftar (<?= count($kendaraan_list) ?>)
                        <?php else: ?>
                            Kendaraan Terdaftar (<?= count($kendaraan_list) ?>)
                        <?php endif; ?>
                    </h3>
            
                    <?php if (empty($kendaraan_list)): ?>
                        <div class="alert alert-warning">
                            <?php if ($currentUserRole === 'admin'): ?>
                                Belum ada kendaraan yang terdaftar di sistem.
                            <?php else: ?>
                                Belum ada kendaraan yang terdaftar. Silakan daftarkan kendaraan Anda terlebih dahulu.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>

                    <div class="grid grid-2">
                        <?php foreach ($kendaraan_list as $kendaraan): ?>
                            <div class="card vehicle-card">
                                <div class="vehicle-plate">
                                    <?= htmlspecialchars($kendaraan['no_plat']) ?>
                                    <?php if ($currentUserRole === 'admin'): ?>
                                        <span class="admin-badge">ADMIN</span>
                                    <?php endif; ?>
                                </div>
                            
                                    <?php if ($currentUserRole === 'admin' && !empty($kendaraan['nama_lengkap'])): ?>
                                        <div class="vehicle-meta">
                                            <p><strong>Pemilik:</strong> <?= htmlspecialchars($kendaraan['nama_lengkap']) ?></p>
                                        </div>
                                    <?php endif; ?>
                            
                                    <div class="vehicle-meta">
                                        <p><strong>Jenis:</strong> <?= ucfirst(htmlspecialchars($kendaraan['jenis_kendaraan'])) ?></p>
                                        <p><strong>Merk:</strong> <?= htmlspecialchars($kendaraan['merk']) ?></p>
                                            <?php if (!empty($kendaraan['warna'])): ?>
                                                <p><strong>Warna:</strong> <?= htmlspecialchars($kendaraan['warna']) ?></p>
                                             <?php endif; ?>
                                                <p class="mb-1"><strong>Terdaftar:</strong> <?= date('d/m/Y', strtotime($kendaraan['created_at'])) ?></p>
                                     </div>

            <?php
                 // Cek status parkir
                    $stmtPark = $pdo->prepare("SELECT ap.nama_area, lp.waktu_masuk FROM log_parkir lp JOIN area_parkir ap ON lp.area_parkir_id = ap.id WHERE lp.kendaraan_id = ? AND lp.status = 'parkir'");
                        $stmtPark->execute([$kendaraan['id']]);
                            $parkir_info = $stmtPark->fetch(PDO::FETCH_ASSOC);

                    // Role pemilik (dari join) fallback ke 'mahasiswa' kalau null
                        $ownerRole = $kendaraan['owner_role'] ?? 'mahasiswa';
                            $ownerRole = strtolower($ownerRole);
                    // map to valid css class
                        $roleClass = 'role-mahasiswa';
                            if ($ownerRole === 'dosen') $roleClass = 'role-dosen';
                                elseif ($ownerRole === 'staff') $roleClass = 'role-staff';
                                    elseif ($ownerRole === 'admin') $roleClass = 'role-admin';
            ?>

            <?php if ($parkir_info): ?>
                <div class="alert alert-warning" style="margin-top:10px;">
                    <strong>Sedang Parkir</strong><br>
                            Area: <?= htmlspecialchars($parkir_info['nama_area']) ?><br>
                                Sejak: <?= date('d/m/Y H:i', strtotime($parkir_info['waktu_masuk'])) ?>
                </div>
            <?php else: ?>

                <!-- Hanya tampilkan tombol hapus jika user pemilik atau admin (admin full akses) -->
                    <?php
                        $isOwner = ($currentUserRole === 'admin') ? true : ($kendaraan['user_id'] == $currentUserId);
                    ?>
                        <?php if ($isOwner): ?>
                            <form method="POST" style="margin-top: 10px;" onsubmit="return confirm('Yakin ingin menghapus kendaraan ini? Semua riwayat parkir juga akan terhapus.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="kendaraan_id" value="<?= (int)$kendaraan['id'] ?>">
                                <button type="submit" class="btn btn-danger">Hapus Kendaraan</button>
                            </form>
                        <?php endif; ?>
            <?php endif; ?>

                <!-- Role badge: tampil di bawah merk/warna -->
                    <div class="card-meta">
                        <span class="role-badge <?= $roleClass ?>"><?= strtoupper(htmlspecialchars($ownerRole)) ?></span>
                    </div>
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