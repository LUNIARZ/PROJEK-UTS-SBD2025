<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pdo = getConnection();

// Inisialisasi variabel
$error = '';
$success = '';
$users = [];
$total_users = 0;
$admin_count = [];
$dosen_count = [];
$mahasiswa_count = [];

// Proses form tambah user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $role = $_POST['role'] ?? 'mahasiswa';
        
        // Validasi
        if (empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
            $error = "Semua field wajib diisi!";
        } elseif ($password !== $confirm_password) {
            $error = "Konfirmasi password tidak sesuai!";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal 6 karakter!";
        } else {
            // Cek username sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username sudah digunakan!";
            } else {
                // Tambah user baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, no_telepon, role) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$username, $hashed_password, $nama_lengkap, $email, $no_telepon, $role])) {
                    $success = "User berhasil ditambahkan!";
                    // Reset form
                    $_POST = [];
                } else {
                    $error = "Gagal menambahkan user!";
                }
            }
        }
    }
    
    // Reset password
    elseif ($action === 'reset_password') {
        $user_id = $_POST['user_id'] ?? 0;
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $new_password = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$new_password, $user_id])) {
                $success = "Password berhasil direset ke: password123";
            } else {
                $error = "Gagal reset password!";
            }
        }
    }
    
    // Hapus user
    elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? 0;
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = "User berhasil dihapus!";
            } else {
                $error = "Gagal menghapus user!";
            }
        }
    }
    
    // Edit user
    elseif ($action === 'edit') {
        $user_id = $_POST['user_id'] ?? 0;
        $username = trim($_POST['username'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $role = $_POST['role'] ?? 'mahasiswa';
        
        if (empty($username) || empty($nama_lengkap) || empty($email)) {
            $error = "Semua field wajib diisi!";
        } else {
            // Cek username sudah ada (kecuali untuk user ini)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $error = "Username sudah digunakan!";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, email = ?, no_telepon = ?, role = ? WHERE id = ?");
                if ($stmt->execute([$username, $nama_lengkap, $email, $no_telepon, $role, $user_id])) {
                    $success = "User berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate user!";
                }
            }
        }
    }
}

// Ambil data users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$total_users = count($users);

// Hitung per role
$admin_count = array_filter($users, fn($user) => $user['role'] === 'admin');
$dosen_count = array_filter($users, fn($user) => $user['role'] === 'dosen');
$mahasiswa_count = array_filter($users, fn($user) => $user['role'] === 'mahasiswa');
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
                <li><a href="users.php" class="active">Kelola User</a></li>
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
                <h1 class="card-title">👥 Kelola User</h1>
                <p class="card-subtitle">Manajemen data pengguna sistem parkir</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Statistik User -->
            <div class="grid grid-4 mb-3">
                <div class="status-card status-available">
                    <div class="status-number"><?= $total_users ?></div>
                    <div class="status-label">Total User</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= count($admin_count) ?></div>
                    <div class="status-label">Admin</div>
                </div>
                <div class="status-card status-available">
                    <div class="status-number"><?= count($dosen_count) ?></div>
                    <div class="status-label">Dosen</div>
                </div>
                <div class="status-card status-occupied">
                    <div class="status-number"><?= count($mahasiswa_count) ?></div>
                    <div class="status-label">Mahasiswa</div>
                </div>
            </div>

            <!-- Form Tambah User -->
            <div class="card mb-3">
                <h3>Tambah User Baru</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-input" required 
                                   placeholder="Username unik" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-input" required 
                                   placeholder="Minimal 6 karakter">
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
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="mahasiswa" <?= (($_POST['role'] ?? '') === 'mahasiswa') ? 'selected' : '' ?>>Mahasiswa</option>
                                <option value="dosen" <?= (($_POST['role'] ?? '') === 'dosen') ? 'selected' : '' ?>>Dosen</option>
                                <option value="staff" <?= (($_POST['role'] ?? '') === 'staff') ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah User</button>
                </form>
            </div>

            <!-- Daftar Users -->
            <h3>Daftar User (<?= $total_users ?>)</h3>
            
            <?php if (empty($users)): ?>
                <div class="alert alert-warning">
                    Belum ada user yang terdaftar.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <br><small style="color: #667eea;">(Anda)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['no_telepon'] ?? '-') ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $user['role'] ?>">
                                            <?= strtoupper($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: #28a745; font-weight: bold;">
                                            AKTIF
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button type="button" onclick='editUser(<?= json_encode($user) ?>)' class="btn btn-success btn-sm">
                                                Edit
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" 
                                                            onclick="return confirm('Reset password user ini? Password akan diubah menjadi: password123')">Reset Password</button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Hapus user <?= htmlspecialchars(addslashes($user['nama_lengkap'])) ?>? Tindakan ini tidak dapat dibatalkan!')">Hapus</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #666; font-style: italic; font-size: 0.8rem;">Akun sendiri</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

           <!-- Modal Edit User -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header">
            <h3>Edit User</h3>
        </div>
        <div class="modal-body">
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" id="edit_username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="tel" name="no_telepon" id="edit_no_telepon" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" id="edit_role" class="form-select" required>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="submit" form="editForm" class="btn btn-primary">Update User</button>
            <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Batal</button>
        </div>
    </div>
</div>
            <div class="mt-3">
                <a href="index.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <script>
// Fungsi untuk menampilkan modal edit user
function editUser(user) {
    try {
        // Parse data user jika berupa string
        if (typeof user === 'string') {
            user = JSON.parse(user);
        }
        
        // Isi form dengan data user
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username || '';
        document.getElementById('edit_nama_lengkap').value = user.nama_lengkap || '';
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_no_telepon').value = user.no_telepon || '';
        document.getElementById('edit_role').value = user.role || 'mahasiswa';
        
        // Tampilkan modal
        document.getElementById('editModal').classList.add('active');
        // HAPUS BARIS INI: document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error('Error editing user:', error);
        alert('Terjadi error saat memuat data user');
    }
}

// Fungsi untuk menutup modal edit
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
    // HAPUS BARIS INI: document.body.style.overflow = '';
}

// Tutup modal ketika klik di luar modal
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Tutup modal dengan ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});

// Validasi form sebelum submit
document.getElementById('editForm').addEventListener('submit', function(e) {
    const username = document.getElementById('edit_username').value.trim();
    const namaLengkap = document.getElementById('edit_nama_lengkap').value.trim();
    const email = document.getElementById('edit_email').value.trim();
    
    if (!username || !namaLengkap || !email) {
        e.preventDefault();
        alert('Mohon isi semua field yang wajib!');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Format email tidak valid!');
        return false;
    }
    
    return true;
});

// Mobile Menu Toggle
document.getElementById('mobile-menu').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('nav-links').classList.toggle('active');
});

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const nav = document.querySelector('.nav');
    const navLinks = document.getElementById('nav-links');
    
    if (!nav.contains(event.target) && navLinks.classList.contains('active')) {
        navLinks.classList.remove('active');
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
</body>
</html>