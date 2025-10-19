<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $no_telepon = $_POST['no_telepon'] ?? '';
    $role = $_POST['role'] ?? 'mahasiswa';
    
    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
        $error = 'Mohon isi semua field yang wajib!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        $pdo = getConnection();
        
        // Cek username sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah digunakan!';
        } else {
            // Insert user baru
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, no_telepon, role) VALUES (?, MD5(?), ?, ?, ?, ?)");
            if ($stmt->execute([$username, $password, $nama_lengkap, $email, $no_telepon, $role])) {
                $success = 'Pendaftaran berhasil! Silakan login.';
            } else {
                $error = 'Pendaftaran gagal! Silakan coba lagi.';
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
    <title>Daftar - Sistem Parkir Kampus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card" style="max-width: 500px; margin: 50px auto;">
            <div class="card-header">
                <h1 class="card-title">🅿️ Daftar Akun</h1>
                <p class="card-subtitle">Sistem Parkir Kampus</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password *</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" class="form-input" required 
                           value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="tel" name="no_telepon" class="form-input" 
                           value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="role" class="form-select" required>
                        <option value="mahasiswa" <?= ($_POST['role'] ?? '') === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                        <option value="dosen" <?= ($_POST['role'] ?? '') === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                        <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Daftar</button>
            </form>

            <div class="text-center mt-3">
                <p>Sudah punya akun? <a href="login.php" style="color: #667eea;">Login di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>