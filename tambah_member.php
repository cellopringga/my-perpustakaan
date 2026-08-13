<?php
session_start();
require_once 'config.php';

// FIX 1: Pengecekan Session yang lebih stabil
// Jika mental terus, pastikan di login.php kamu sudah set $_SESSION['role'] = 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); 
    exit();
}

$error = '';
$success = '';

if (isset($_POST['simpan'])) {
    // Ambil data dan bersihkan
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Tidak di-escape karena mau di-hash
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validasi Dasar
    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($nim)) {
        $error = "Field dengan tanda bintang (*) tidak boleh kosong!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek duplikasi Username/NIM
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR nim='$nim'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username atau NIM sudah digunakan!";
        } else {
            // FIX 2: Gunakan Password Hash (Standar Keamanan)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Query Insert (Sesuaikan urutan kolom database kamu)
            $query = "INSERT INTO users (username, password, role, nim, nama_lengkap, email, no_telepon, alamat, is_active) 
                      VALUES ('$username', '$hashed_password', 'member', '$nim', '$nama_lengkap', '$email', '$no_telepon', '$alamat', '$is_active')";
            
            if (mysqli_query($conn, $query)) {
                $success = "✅ Member baru berhasil ditambahkan!";
                // FIX 3: Gunakan URL absolut atau relatif yang pasti
                echo "<script>
                    setTimeout(function(){ 
                        window.location.href='kelola_member.php'; 
                    }, 1500);
                </script>";
            } else {
                $error = "Error Database: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Member - Sistem Perpustakaan</title>
    <style>
        /* Memakai Style dari kodingan kamu yang sudah rapi */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4A90E2, #5C6BC0); padding: 25px; color: white; text-align: center; }
        .body { padding: 30px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-primary { width: 100%; padding: 12px; background: #10B981; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #dc2626; border-left: 4px solid #dc2626; }
        .alert-success { background: #d1fae5; color: #059669; border-left: 4px solid #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ Tambah Member</h1>
            <p>Pastikan data mahasiswa sudah benar</p>
        </div>
        <div class="body">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>NIM *</label>
                        <input type="text" name="nim" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password *</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" rows="2"></textarea>
                </div>
                <div style="margin-bottom: 20px;">
                    <input type="checkbox" name="is_active" checked> Aktifkan akun member
                </div>
                <button type="submit" name="simpan" class="btn-primary">💾 Simpan Data Member</button>
                <a href="kelola_member.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">⬅️ Kembali</a>
            </form>
        </div>
    </div>
</body>
</html>