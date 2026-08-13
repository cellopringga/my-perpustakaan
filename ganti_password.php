<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['nama_lengkap'] ?? 'Member';

// ========== TAMPILKAN PESAN SESSION ==========
$success_msg = $_SESSION['success'] ?? '';
$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// ========== PROSES GANTI PASSWORD ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    $errors = [];

    // Validasi Input
    if (empty($password_lama)) {
        $errors[] = "Password saat ini wajib diisi";
    }
    if (empty($password_baru)) {
        $errors[] = "Password baru wajib diisi";
    } elseif (strlen($password_baru) < 6) {
        $errors[] = "Password baru minimal 6 karakter";
    }
    if ($password_baru !== $konfirmasi_password) {
        $errors[] = "Konfirmasi password baru tidak cocok";
    }

    if (empty($errors)) {
        // Ambil data user dari database untuk verifikasi password lama
        $query = "SELECT password FROM users WHERE id = $user_id AND role = 'member'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password_lama, $user['password'])) {
            // Hash password baru
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

            // Update password ke database
            $update_query = "UPDATE users SET password = '$password_hash' WHERE id = $user_id";
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['success'] = "Password berhasil diperbarui!";
                header("Location: profil_member.php");
                exit();
            } else {
                $error_msg = "Gagal memperbarui password: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Password saat ini salah!";
        }
    } else {
        $error_msg = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Perpustakaan Digital</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #dce7e1;
            color: #2d3748;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: #ffffff;
            color: #4a5568;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: 2px 0 15px rgba(0,0,0,0.03);
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .sidebar-header .logo {
            font-size: 22px;
            background: #e6f4ed;
            color: #10b981;
            padding: 8px 12px;
            border-radius: 12px;
        }

        .sidebar-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 12px;
        }

        .sidebar-menu li {
            margin-bottom: 4px;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            gap: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .sidebar-menu li a:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .sidebar-menu li.active > a {
            background: #e6f4ed;
            color: #059669;
        }

        .sidebar-menu li a i {
            width: 20px;
            font-size: 16px;
        }

        /* Submenu Dropdown */
        .sidebar-menu li.has-dropdown > a .dropdown-icon {
            margin-left: auto;
            font-size: 11px;
            transition: transform 0.3s ease;
        }

        .sidebar-menu li.has-dropdown.open > a .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            list-style: none;
            padding-left: 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .sidebar-menu li.has-dropdown.open .dropdown-menu {
            max-height: 300px;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .dropdown-menu li a {
            padding: 8px 16px;
            font-size: 13px;
        }

        /* Main Content Layout */
        .main-content {
            margin-left: 250px;
            padding: 28px;
        }

        /* Header Navbar */
        .navbar {
            background: #ffffff;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .navbar h1 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-name {
            color: #334155;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout {
            background: #fef2f2;
            color: #ef4444;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid #fecaca;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Alert Messages */
        .alert {
            padding: 14px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .close-alert {
            cursor: pointer;
            font-size: 18px;
            opacity: 0.7;
        }

        .close-alert:hover {
            opacity: 1;
        }

        /* Form Card */
        .form-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 32px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .form-group label i {
            color: #10b981;
            margin-right: 6px;
            width: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            color: #0f172a;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-help {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #94a3b8;
        }

        .button-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
        }

        .btn-submit {
            background: #10b981;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #059669;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Mobile Navigation Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            z-index: 101;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        @media (max-width: 768px) {
            .mobile-toggle { display: block; }
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; padding: 20px; padding-top: 65px; }
            .form-card { padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- Mobile Navigation Toggle -->
    <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fa-solid fa-book-open-reader"></i></div>
            <div>
                <h3>Member Area</h3>
                <p style="font-size: 11px; color: #94a3b8; font-weight: 500;">Perpustakaan Digital</p>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard_member.php">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            
            <li class="has-dropdown" id="dropdownBuku">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownBuku')">
                    <i class="fa-solid fa-book"></i> Daftar Buku <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="daftar_buku.php">• Semua Buku</a></li>
                </ul>
            </li>

            <li class="has-dropdown" id="dropdownRiwayat">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownRiwayat')">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Saya <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="riwayat_peminjaman.php">• Riwayat Peminjaman</a></li>
                </ul>
            </li>

            <li class="has-dropdown active open" id="dropdownProfil">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownProfil')">
                    <i class="fa-solid fa-user"></i> Profil Saya <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="profil_member.php">• Lihat Profil</a></li>
                    <li><a href="edit_profil.php">• Edit Profil</a></li>
                    <li><a href="ganti_password.php" style="color: #059669; font-weight: 700;">• Ganti Password</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Top Header Navbar -->
        <div class="navbar">
            <h1>Ganti Password</h1>
            <div class="user-info">
                <span class="user-name">
                    <i class="fa-solid fa-circle-user" style="font-size: 18px; color: #10b981;"></i>
                    <?= htmlspecialchars($user_nama); ?>
                </span>
                <a href="logout.php" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>

        <!-- Alert System -->
        <?php if ($error_msg): ?>
        <div class="alert alert-error">
            <span><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> <?= $error_msg; ?></span>
            <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="form-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fa-solid fa-lock"></i> Password Saat Ini *</label>
                    <input type="password" name="password_lama" required placeholder="Masukkan password saat ini">
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-key"></i> Password Baru *</label>
                    <input type="password" name="password_baru" required placeholder="Masukkan password baru">
                    <span class="form-help">Minimal 6 karakter</span>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-shield-halved"></i> Konfirmasi Password Baru *</label>
                    <input type="password" name="konfirmasi_password" required placeholder="Ulangi password baru">
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Update Password
                    </button>
                    <a href="profil_member.php" class="btn-cancel">
                        <i class="fa-solid fa-xmark"></i> Batal
                    </a>
                </div>
            </form>
        </div>

    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        function toggleDropdown(id) {
            const element = document.getElementById(id);
            if (element) element.classList.toggle('open');
        }

        // Close sidebar on outside click (Mobile view)
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 768) {
                if (sidebar && toggleBtn && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>