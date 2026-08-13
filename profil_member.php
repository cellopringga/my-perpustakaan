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

// ========== AMBIL DATA PROFIL MEMBER ==========
$query = "SELECT * FROM users WHERE id = $user_id AND role = 'member'";
$result = mysqli_query($conn, $query);
$member = mysqli_fetch_assoc($result);

if (!$member) {
    $_SESSION['error'] = "Data member tidak ditemukan!";
    header("Location: dashboard_member.php");
    exit();
}

// ========== HITUNG STATISTIK ==========
$query_pinjam = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = $user_id AND status = 'dipinjam'";
$result_pinjam = mysqli_query($conn, $query_pinjam);
$total_dipinjam = mysqli_fetch_assoc($result_pinjam)['total'];

$query_riwayat = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = $user_id AND status = 'dikembalikan'";
$result_riwayat = mysqli_query($conn, $query_riwayat);
$total_riwayat = mysqli_fetch_assoc($result_riwayat)['total'];

$query_denda = "SELECT SUM(denda) as total FROM peminjaman WHERE user_id = $user_id AND denda > 0";
$result_denda = mysqli_query($conn, $query_denda);
$total_denda = mysqli_fetch_assoc($result_denda)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Perpustakaan Digital</title>
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

        .alert-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
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

        /* Profile Card */
        .profile-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }

        .profile-header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 36px 20px;
            text-align: center;
            color: #ffffff;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: #ffffff;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 38px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .profile-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .profile-role {
            font-size: 13px;
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 600;
        }

        .profile-body {
            padding: 28px;
        }

        .info-group {
            display: flex;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-group:last-of-type {
            border-bottom: none;
        }

        .info-label {
            width: 180px;
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label i {
            color: #10b981;
            width: 18px;
        }

        .info-value {
            flex: 1;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn-edit, .btn-password {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #10b981;
            color: #ffffff;
        }

        .btn-edit:hover {
            background: #059669;
        }

        .btn-password {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-password:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Stats Cards Layout */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-info h3 {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .icon-dipinjam {
            background: #fef3c7;
            color: #d97706;
        }

        .icon-riwayat {
            background: #d1fae5;
            color: #059669;
        }

        .icon-denda {
            background: #fee2e2;
            color: #dc2626;
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
            .info-group { flex-direction: column; align-items: flex-start; gap: 4px; }
            .info-label { width: 100%; }
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
                    <li><a href="profil_member.php" style="color: #059669; font-weight: 700;">• Lihat Profil</a></li>
                    <li><a href="edit_profil.php">• Edit Profil</a></li>
                    <li><a href="ganti_password.php">• Ganti Password</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Top Header Navbar -->
        <div class="navbar">
            <h1>Profil Saya</h1>
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
        <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <span><i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> <?= $success_msg; ?></span>
            <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
        <div class="alert alert-error">
            <span><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> <?= $error_msg; ?></span>
            <span class="close-alert" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php
                    $firstLetter = strtoupper(substr($member['nama_lengkap'] ?? 'M', 0, 1));
                    if ($firstLetter >= 'A' && $firstLetter <= 'Z') {
                        echo $firstLetter;
                    } else {
                        echo '<i class="fa-solid fa-user"></i>';
                    }
                    ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($member['nama_lengkap']); ?></div>
                <div class="profile-role"><i class="fa-solid fa-id-badge" style="margin-right: 4px;"></i> Member Perpustakaan</div>
            </div>
            
            <div class="profile-body">
                <div class="info-group">
                    <div class="info-label"><i class="fa-solid fa-hashtag"></i> ID Member</div>
                    <div class="info-value"><?= htmlspecialchars($member['id']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label"><i class="fa-solid fa-user-tag"></i> Username</div>
                    <div class="info-value"><?= htmlspecialchars($member['username']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label"><i class="fa-solid fa-envelope"></i> Email</div>
                    <div class="info-value"><?= htmlspecialchars($member['email']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label"><i class="fa-solid fa-calendar-check"></i> Bergabung Sejak</div>
                    <div class="info-value"><?= date('d F Y', strtotime($member['created_at'] ?? $member['tanggal_daftar'] ?? date('Y-m-d'))); ?></div>
                </div>
                
                <div class="action-buttons">
                    <a href="edit_profil.php" class="btn-edit">
                        <i class="fa-solid fa-user-pen"></i> Edit Profil
                    </a>
                    <a href="ganti_password.php" class="btn-password">
                        <i class="fa-solid fa-key"></i> Ganti Password
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Sedang Dipinjam</h3>
                    <div class="stat-number"><?= $total_dipinjam; ?></div>
                </div>
                <div class="stat-icon icon-dipinjam">
                    <i class="fa-solid fa-book-bookmark"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Riwayat Peminjaman</h3>
                    <div class="stat-number"><?= $total_riwayat; ?></div>
                </div>
                <div class="stat-icon icon-riwayat">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Denda</h3>
                    <div class="stat-number">Rp <?= number_format($total_denda, 0, ',', '.'); ?></div>
                </div>
                <div class="stat-icon icon-denda">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>

    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        function toggleDropdown(id) {
            const element = document.getElementById(id);
            if (element) {
                element.classList.toggle('open');
            }
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

        // Auto close alert notifications
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert) alert.style.display = 'none';
                }, 5000);
            });
        }, 100);
    </script>
</body>
</html>