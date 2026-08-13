<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['simpan'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    
    // Validasi
    if (empty($username) || empty($password) || empty($nik) || empty($nama_lengkap) || empty($email)) {
        $error = "Semua field wajib diisi!";
    } elseif ($password != $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 5) {
        $error = "Password minimal 5 karakter!";
    } elseif (strlen($nik) < 5) {
        $error = "NIK minimal 5 karakter!";
    } else {
        // Cek username atau NIK sudah ada
        $cek = mysqli_query($conn, "SELECT id FROM admin WHERE username='$username' OR nik='$nik'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username atau NIK sudah terdaftar!";
        } else {
            $insert = mysqli_query($conn, "INSERT INTO admin (username, password, nik, nama_lengkap, email, no_telepon, alamat, jabatan, is_active) 
                VALUES ('$username', '$password', '$nik', '$nama_lengkap', '$email', '$no_telepon', '$alamat', '$jabatan', 1)");
            
            if ($insert) {
                // Catat log
                mysqli_query($conn, "INSERT INTO log_aktivitas (admin_id, aktivitas, deskripsi) 
                    VALUES ('{$_SESSION['admin_id']}', 'Tambah Admin', 'Menambahkan admin baru: $nama_lengkap')");
                
                $success = "✅ Admin berhasil ditambahkan!";
                echo "<script>setTimeout(function(){ window.location='kelola_admin.php'; }, 1500);</script>";
            } else {
                $error = "Gagal menambahkan admin: " . mysqli_error($conn);
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
    <title>Tambah Admin - Perpustakaan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 25px 30px;
            text-align: center;
            color: white;
        }
        .header .icon { font-size: 50px; margin-bottom: 10px; }
        .header h2 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .form-group label .required { color: #e74c3c; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea { resize: vertical; min-height: 80px; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .note { font-size: 11px; color: #888; margin-top: 5px; }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        .btn-simpan {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-simpan:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-batal {
            flex: 1;
            padding: 14px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-batal:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .alert-error {
            background: #fee;
            color: #e74c3c;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        @media (max-width: 550px) {
            .body { padding: 20px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">👑</div>
            <h2>Tambah Admin Baru</h2>
            <p>Input data petugas perpustakaan</p>
        </div>
        <div class="body">
            <?php if ($error): ?>
                <div class="alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" placeholder="Pilih username" required>
                    </div>
                    <div class="form-group">
                        <label>NIK <span class="required">*</span></label>
                        <input type="text" name="nik" placeholder="Nomor Induk Kependudukan" required>
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
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" placeholder="Nama lengkap" required>
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" placeholder="Kepala/Petugas">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" placeholder="email@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="tel" name="no_telepon" placeholder="08123456789">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" name="simpan" class="btn-simpan">💾 Simpan Admin</button>
                    <a href="kelola_admin.php" class="btn-batal">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>