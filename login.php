<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard_admin.php");
    exit();
}
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Cek di tabel ADMIN dulu
    $query_admin = "SELECT * FROM admin WHERE username = '$username' AND password = '$password' AND is_active = 1";
    $result_admin = mysqli_query($conn, $query_admin);
    
    if (mysqli_num_rows($result_admin) == 1) {
        $admin = mysqli_fetch_assoc($result_admin);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_nama'] = $admin['nama_lengkap'];
        $_SESSION['admin_nik'] = $admin['nik'];
        $_SESSION['role'] = 'admin';
        
        // Redirect ke dashboard admin
        header("Location: dashboard_admin.php");
        exit();
    }
    
    // Cek di tabel MEMBER (users)
    $query_member = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND role = 'member' AND is_active = 1";
    $result_member = mysqli_query($conn, $query_member);
    
    if (mysqli_num_rows($result_member) == 1) {
        $member = mysqli_fetch_assoc($result_member);
        $_SESSION['user_id'] = $member['id'];
        $_SESSION['username'] = $member['username'];
        $_SESSION['nama_lengkap'] = $member['nama_lengkap'];
        $_SESSION['nim'] = $member['nim'];
        $_SESSION['role'] = 'member';
        
        header("Location: dashboard_member.php");
        exit();
    }
    
    $error = "Username atau password salah!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .logo { font-size: 50px; margin-bottom: 10px; }
        .header h2 { margin-bottom: 5px; }
        .body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .alert-error {
            background: #fee;
            color: #e74c3c;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .demo-account {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 12px;
            text-align: center;
        }

        /* ========== PENAMBAHAN CSS RESPONSIVE UNTUK HP ========== */
        @media (max-width: 480px) {
            body {
                padding: 12px; /* Margin luar diperkecil di HP */
            }
            .header {
                padding: 20px 15px; /* Padding header disesuaikan */
            }
            .logo img {
                max-height: 70px !important; /* Ukuran logo disesuaikan di HP */
            }
            .header h2 {
                font-size: 16px; /* Ukuran font judul disesuaikan */
            }
            .header p {
                font-size: 12px;
            }
            .body {
                padding: 20px 15px; /* Margin internal form diperkecil */
            }
            input {
                padding: 10px 12px; /* Input box sedikit lebih tipis di HP */
            }
            .btn-login {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <div class="logo">
                <img src="logosma.png" alt="Logo SMA Pasundan Rancaekek" style="max-height: 100px; width: auto;">
            </div>
            <h2>SELAMAT DATANG DI PERPUSTAKAAN SMA PASUNDAN RANCAEKEK</h2>
            <p>Silakan login untuk melanjutkan</p>
        </div>
        <div class="body">
            <?php if($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <div class="register-link">
                <a href="register.php">Belum punya akun? Daftar di sini</a>
            </div>
        </div>
    </div>
</body>
</html>