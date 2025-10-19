<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pdo = getConnection();
$error = '';
$success = '';

// Ambil ID user dari URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data user yang akan diedit
$user = null;
if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Jika user tidak ditemukan
if (!$user) {
    $error = 'User tidak ditemukan!';
}

// Proses Update User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $role = $_POST['role'] ?? 'mahasiswa';
    
    if (empty($username) || empty($nama_lengkap) || empty($email)) {
        $error = 'Mohon isi semua field yang wajib!';
    } else {
        // Cek username dan email sudah digunakan oleh user lain
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah digunakan!';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, email = ?, no_telepon = ?, role = ? WHERE id = ?");
            if ($stmt->execute([$username, $nama_lengkap, $email, $no_telepon, $role, $user_id])) {
                $success = 'User berhasil diperbarui!';
                // Refresh data user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Gagal memperbarui user!';
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
    <title>Edit User - Sistem Parkir Kampus</title>
    <link rel="stylesheet" href="../style.css">
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
                <li><a href="logs.php">Log Aktivitas</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h1 class="card-title">✏️ Edit User</h1>
                <p class="card-subtitle">Edit data pengguna sistem parkir</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-input" required 
                               value="<?= htmlspecialchars($user['username']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" class="form-input" required
                               value="<?= htmlspecialchars($user['nama_lengkap']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required
                               value="<?= htmlspecialchars($user['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="tel" name="no_telepon" class="form-input"
                               value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="mahasiswa" <?= $user['role'] === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                            <option value="dosen" <?= $user['role'] === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Daftar</label>
                        <input type="text" class="form-input" 
                               value="<?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>" 
                               readonly style="background: #f8f9fa;">
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Update User</button>
                        <a href="users.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Kembali</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-error">
                    User tidak ditemukan. <a href="users.php">Kembali ke daftar user</a>
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
    </script>
</body>
</html>