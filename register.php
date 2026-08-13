<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$selected_role = 'member';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $role = $_POST['role'];
    $nim = mysqli_real_escape_string($conn, $_POST['nim'] ?? '');
    $token = $_POST['token'] ?? '';
    
    $ADMIN_TOKEN = "ADMIN2024"; // Token khusus admin
    
    // Validasi
    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        $error = "Username, Password, dan Nama Lengkap wajib diisi!";
    } elseif ($password != $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 5) {
        $error = "Password minimal 5 karakter!";
    } elseif ($role == 'member' && empty($nim)) {
        $error = "NIM wajib diisi untuk member!";
    } elseif ($role == 'admin' && $token != $ADMIN_TOKEN) {
        $error = "Token admin salah! Hubungi administrator.";
    } else {
        // CEK USERNAME SUDAH ADA DI TABEL users ATAU admin
        $cek_users = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        $cek_admin = mysqli_query($conn, "SELECT id FROM admin WHERE username = '$username'");
        
        if (mysqli_num_rows($cek_users) > 0 || mysqli_num_rows($cek_admin) > 0) {
            $error = "Username sudah terdaftar!";
        } else {
            // INSERT berdasarkan role
            if ($role == 'member') {
                // Member masuk ke tabel users
                $query = "INSERT INTO users (username, password, nama_lengkap, email, no_telepon, alamat, role, nim, is_active) 
                          VALUES ('$username', '$password', '$nama_lengkap', '$email', '$no_telepon', '$alamat', 'member', '$nim', 1)";
            } else {
                // Admin masuk ke tabel admin
                $query = "INSERT INTO admin (username, password, nik, nama_lengkap, email, no_telepon, alamat, jabatan, is_active) 
                          VALUES ('$username', '$password', 'ADMIN" . rand(100000, 999999) . "', '$nama_lengkap', '$email', '$no_telepon', '$alamat', 'Petugas', 1)";
            }
            
            if (mysqli_query($conn, $query)) {
                $success = "Pendaftaran berhasil! Silakan login.";
                echo '<meta http-equiv="refresh" content="2;url=login.php">';
            } else {
                $error = "Pendaftaran gagal: " . mysqli_error($conn);
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
    <title>Register - Perpustakaan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .register-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 50px;
            margin-bottom: 10px;
        }

        .header h2 {
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        label .required {
            color: #e74c3c;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .role-option {
            flex: 1;
            cursor: pointer;
        }

        .role-option input {
            display: none;
        }

        .role-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .role-option input:checked + .role-card {
            background: #e8eaf6;
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .role-card:hover {
            transform: translateY(-2px);
            border-color: #667eea;
        }

        .role-icon {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .role-title {
            font-weight: bold;
            color: #333;
        }

        .role-desc {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .dynamic-fields {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .token-note {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 550px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .role-selector {
                flex-direction: column;
            }
            
            .body {
                padding: 20px;
            }
        }
    </style>
    <script>
        function toggleRoleFields() {
            var role = document.querySelector('input[name="role"]:checked').value;
            var memberFields = document.getElementById('member-fields');
            var adminFields = document.getElementById('admin-fields');
            
            if (role === 'member') {
                memberFields.style.display = 'block';
                adminFields.style.display = 'none';
                document.getElementById('nim').required = true;
                document.getElementById('token').required = false;
            } else {
                memberFields.style.display = 'none';
                adminFields.style.display = 'block';
                document.getElementById('nim').required = false;
                document.getElementById('token').required = true;
            }
        }
        
        window.onload = function() {
            toggleRoleFields();
        }
    </script>
</head>
<body>
    <div class="register-container">
        <div class="header">
            <div class="logo">📚</div>
            <h2>Daftar Akun Baru</h2>
            <p>Bergabunglah dengan Perpustakaan Digital</p>
        </div>

        <div class="body">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Pilihan Role -->
                <div class="role-selector">
                    <label class="role-option">
                        <input type="radio" name="role" value="member" checked onchange="toggleRoleFields()">
                        <div class="role-card">
                            <div class="role-icon">🎓</div>
                            <div class="role-title">Member</div>
                            <div class="role-desc">Siswa / Anggota</div>
                        </div>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="role" value="admin" onchange="toggleRoleFields()">
                        <div class="role-card">
                            <div class="role-icon">👑</div>
                            <div class="role-title">Admin</div>
                            <div class="role-desc">Petugas Perpustakaan</div>
                        </div>
                    </label>
                </div>

                <!-- Field untuk Member (NIM) -->
                <div id="member-fields" class="dynamic-fields">
                    <div class="form-group">
                        <label>NIM <span class="required">*</span></label>
                        <input type="text" id="nim" name="nim" placeholder="Masukkan NIS (Nomor Induk Siswa)">
                        <div class="token-note">📌 NIS digunakan sebagai identitas member</div>
                    </div>
                </div>

                <!-- Field untuk Admin (Token) -->
                <div id="admin-fields" class="dynamic-fields" style="display:none;">
                    <div class="form-group">
                        <label>Token Admin <span class="required">*</span></label>
                        <input type="password" id="token" name="token" placeholder="Masukkan token admin">
                    </div>
                </div>

                <!-- Field Umum -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" placeholder="Pilih username" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" placeholder="Nama lengkap" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" placeholder="Minimal 5 karakter" required>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" placeholder="Ulangi password" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon" placeholder="08123456789">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" placeholder="Masukkan alamat lengkap"></textarea>
                </div>

                <button type="submit" class="btn-register">✨ Daftar Sekarang</button>

                <div class="login-link">
                    <a href="login.php">Sudah punya akun? Login di sini</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>