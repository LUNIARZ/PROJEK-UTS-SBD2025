<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = MD5(?)");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            redirect('index.php');
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Mohon isi semua field!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Parkir Kampus</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="card" style="max-width: 450px; width: 100%; margin: 0;">

            <!-- Logo dan Header -->
            <div class="card-header" style="margin-bottom: 2.5rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🅿️</div>
                <h1 class="card-title" style="margin-bottom: 0.5rem;">Parkir Kampus</h1>
                <p class="card-subtitle">Masuk ke Sistem Parkir Digital</p>
            </div>

            <!-- Alert -->
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    <strong>❌ Error!</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label">
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            👤 Username
                        </span>
                    </label>
                    <input type="text" 
                           name="username" 
                           class="form-input" 
                           required 
                           placeholder="Masukkan username Anda"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            🔒 Password
                        </span>
                    </label>
                    <input type="password" 
                           name="password" 
                           class="form-input" 
                           required 
                           placeholder="Masukkan password Anda"
                           autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
                    <span>🚀 Masuk ke Dashboard</span>
                </button>
            </form>

            <!-- Link Register -->
            <div class="text-center mt-3" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(46, 125, 50, 0.1);">
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                    Belum punya akun?
                </p>
                <a href="register.php" 
                   class="btn" 
                   style="background: rgba(46, 125, 50, 0.1); color: var(--primary-color); text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--border-radius-small); font-weight: 600; transition: var(--transition);">
                    📝 Daftar Sekarang
                </a>
            </div>

            <!-- Info Tambahan -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(46, 125, 50, 0.05); border-radius: var(--border-radius-small); text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏫</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">
                    Sistem Parkir Digital<br>
                    <strong>Politeknik Negeri Sambas</strong>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Form validation dan animasi
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.username.value.trim();
            const password = this.password.value.trim();
            const loginBtn = document.getElementById('loginBtn');
            
            if (!username || !password) {
                e.preventDefault();
                showAlert('Mohon isi semua field!', 'error');
                return;
            }
            
            // Loading animation
            loginBtn.innerHTML = '<span>⏳ Sedang masuk...</span>';
            loginBtn.disabled = true;
            
            // Simulate loading (akan di-override oleh form submit)
            setTimeout(() => {
                loginBtn.innerHTML = '<span>🚀 Masuk ke Dashboard</span>';
                loginBtn.disabled = false;
            }, 3000);
        });

        // Input focus animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Show alert function
        function showAlert(message, type = 'error') {
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `<strong>${type === 'error' ? '❌ Error!' : '✅ Success!'}</strong> ${message}`;
            alert.style.marginBottom = '2rem';
            
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alert, form);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Enter key to submit
            if (e.key === 'Enter' && !e.shiftKey) {
                const activeElement = document.activeElement;
                if (activeElement.classList.contains('form-input')) {
                    document.getElementById('loginForm').submit();
                }
            }
        });

        // Auto-focus username field
        window.addEventListener('load', function() {
            const usernameField = document.querySelector('input[name="username"]');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            }
        });

        // Add floating animation to logo
        const logo = document.querySelector('.card-header div');
        if (logo) {
            logo.style.animation = 'float 3s ease-in-out infinite';
        }

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

    <style>
        body {
            background: var(--primary-gradient);
            background-attachment: fixed;
        }

        .form-group {
            transition: var(--transition);
        }

        .form-input:focus {
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.1);
            border-color: var(--primary-color);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        a[href="register.php"]:hover {
            background: rgba(46, 125, 50, 0.15) !important;
            transform: translateY(-2px);
        }

        /* Mobile */
        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .card-title {
                font-size: 2rem;
            }
        }
    </style>
</body>
</html>
