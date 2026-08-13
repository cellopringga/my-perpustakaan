<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data admin dari session
$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$admin_username = $_SESSION['admin_username'] ?? 'admin';

// Ambil statistik
$total_buku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM buku"))['total'] ?? 0;
$total_member = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='member'"))['total'] ?? 0;
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM admin"))['total'] ?? 0;
$total_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='dipinjam'"))['total'] ?? 0;
$total_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='dikembalikan'"))['total'] ?? 0;

// Ambil statistik denda
$total_denda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(denda), 0) as total FROM peminjaman WHERE denda > 0"))['total'] ?? 0;
$total_belum_bayar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE denda > 0"))['total'] ?? 0;

// Ambil daftar buku terbaru
$buku_query = "SELECT * FROM buku ORDER BY id DESC LIMIT 5";
$buku_result = mysqli_query($conn, $buku_query);

// Ambil daftar member terbaru
$member_query = "SELECT * FROM users WHERE role='member' ORDER BY id DESC LIMIT 5";
$member_result = mysqli_query($conn, $member_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan</title>
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
            background-color: #dce7e1; /* Background khas ala foto rujukan */
            color: #2d3748;
            min-height: 100vh;
        }

        /* Sidebar Modern */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: #ffffff;
            color: #4a5568;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 15px rgba(0,0,0,0.03);
            border-right: 1px solid #e2e8f0;
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
            font-size: 24px;
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

        .sidebar-menu li.active a {
            background: #e6f4ed;
            color: #059669;
        }

        .sidebar-menu li a i {
            width: 20px;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 28px;
            transition: all 0.3s ease;
        }

        /* Banner Header Top */
        .top-banner {
            background: #edf4f0;
            border: 1px solid #d1e3d7;
            border-radius: 20px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .top-banner h1 {
            font-size: 22px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .top-banner p {
            font-size: 13px;
            color: #718096;
            font-weight: 500;
        }

        .navbar {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-name {
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.7);
            padding: 8px 14px;
            border-radius: 30px;
            border: 1px solid #cbd5e1;
        }

        .btn-logout {
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        /* Grid Kartu Statistik */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #f7faf8;
            border: 1px solid #e2ece5;
            padding: 18px 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.03);
        }

        .stat-info h3 {
            font-size: 11px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .stat-number {
            font-size: 22px;
            font-weight: 800;
            color: #1a202c;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* Warna khusus per card */
        .card-1 .stat-icon { background: #e0f2fe; color: #0284c7; }
        .card-2 .stat-icon { background: #dcfce7; color: #16a34a; }
        .card-3 .stat-icon { background: #fef3c7; color: #d97706; }
        .card-4 .stat-icon { background: #ffe4e6; color: #e11d48; }
        .card-5 .stat-icon { background: #f3e8ff; color: #9333ea; }
        .card-6 .stat-icon { background: #ecfdf5; color: #059669; }
        .card-7 .stat-icon { background: #fef2f2; color: #dc2626; }

        /* Style Card Tabel Modern */
        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            overflow-x: auto; /* Memungkinkan scroll horizontal jika tabel lebar */
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-header h3 {
            color: #1e293b;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h3 i {
            color: #10b981;
        }

        .btn-add {
            background: #475569;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: #334155;
        }

        /* Data Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap; /* Mencegah kolom berantakan di HP */
        }

        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 14px 16px;
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-admin {
            background: #fef2f2;
            color: #ef4444;
        }

        .badge-member {
            background: #ecfdf5;
            color: #10b981;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            margin-right: 4px;
            display: inline-block;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .btn-edit:hover { background: #2563eb; }
        .btn-delete:hover { background: #dc2626; }

        /* Toggle Menu Button untuk Mobile */
        .mobile-nav-toggle {
            display: none;
            font-size: 20px;
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        /* Overlay Gelap saat Menu Mobile Buka */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 999;
        }

        /* ================= RESPONSIVE DESIGN ================= */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .mobile-nav-toggle {
                display: flex;
            }

            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .top-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 18px 20px;
            }

            .top-banner h1 {
                font-size: 18px;
            }

            .navbar {
                width: 100%;
                justify-content: space-between;
            }

            .user-info {
                width: 100%;
                justify-content: space-between;
            }

            .card {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr; /* Single column di HP kecil */
            }

            .stat-card {
                padding: 14px 16px;
            }

            .stat-number {
                font-size: 18px;
            }

            .user-name {
                font-size: 12px;
                padding: 6px 10px;
            }

            .btn-logout {
                padding: 8px 14px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

    <!-- Overlay Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fa-solid fa-book-bookmark"></i></div>
            <div>
                <h3>Admin Panel</h3>
                <p style="font-size: 11px; color: #94a3b8; font-weight: 500;">Perpustakaan Digital</p>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="active">
                <a href="dashboard_admin.php">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="kelola_buku.php">
                    <i class="fa-solid fa-book"></i> Kelola Buku
                </a>
            </li>
            <li>
                <a href="kelola_member.php">
                    <i class="fa-solid fa-users"></i> Kelola Member
                </a>
            </li>
            <li>
                <a href="kelola_admin.php">
                    <i class="fa-solid fa-user-shield"></i> Kelola Admin
                </a>
            </li>
            <li>
                <a href="kelola_peminjaman.php">
                    <i class="fa-solid fa-arrows-rotate"></i> Peminjaman
                </a>
            </li>
            <li>
                <a href="kelola_denda.php">
                    <i class="fa-solid fa-wallet"></i> Kelola Denda
                </a>
            </li>
            <li>
                <a href="laporan.php">
                    <i class="fa-solid fa-file-lines"></i> Laporan
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header Banner Ala Gambar Rujukan -->
        <div class="top-banner">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                    <button class="mobile-nav-toggle" onclick="toggleSidebar()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1>Selamat Datang di Sistem Perpustakaan SMA Pasundan Rancaekek</h1>
                </div>
                <p><i class="fa-regular fa-calendar-days"></i> <?php echo date('l, d F Y'); ?> • Halo, <?php echo htmlspecialchars($admin_nama); ?></p>
            </div>
            <div class="navbar">
                <div class="user-info">
                    <span class="user-name"><i class="fa-solid fa-crown" style="color: #f59e0b;"></i> <?php echo htmlspecialchars($admin_nama); ?></span>
                    <a href="logout.php" class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>

        <!-- Stats Cards Layout Modern Grid -->
        <div class="stats-grid">
            <div class="stat-card card-1">
                <div class="stat-info">
                    <h3>Total Buku</h3>
                    <div class="stat-number"><?php echo $total_buku; ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
            </div>
            <div class="stat-card card-2">
                <div class="stat-info">
                    <h3>Total Member</h3>
                    <div class="stat-number"><?php echo $total_member; ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card card-3">
                <div class="stat-info">
                    <h3>Total Admin</h3>
                    <div class="stat-number"><?php echo $total_admin; ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
            </div>
            <div class="stat-card card-4">
                <div class="stat-info">
                    <h3>Buku Dipinjam</h3>
                    <div class="stat-number"><?php echo $total_pinjam; ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
            </div>
            <div class="stat-card card-5">
                <div class="stat-info">
                    <h3>Buku Kembali</h3>
                    <div class="stat-number"><?php echo $total_kembali; ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-card card-6">
                <div class="stat-info">
                    <h3>Total Denda</h3>
                    <div class="stat-number">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>
            <div class="stat-card card-7">
                <div class="stat-info">
                    <h3>Denda Belum Bayar</h3>
                    <div class="stat-number"><?php echo $total_belum_bayar; ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
        </div>

        <!-- Daftar Buku Terbaru -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-book-bookmark"></i> Buku Terbaru</h3>
                <a href="kelola_buku.php" class="btn-add">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kode Buku</th>
                            <th>Judul</th>
                            <th>Pengarang</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($buku_result) > 0): ?>
                            <?php while($buku = mysqli_fetch_assoc($buku_result)): ?>
                            <tr>
                                <td><?php echo $buku['id']; ?></td>
                                <td><?php echo htmlspecialchars($buku['kode_buku']); ?></td>
                                <td><?php echo htmlspecialchars($buku['judul']); ?></td>
                                <td><?php echo htmlspecialchars($buku['penulis']); ?></td>
                                <td><?php echo $buku['stok']; ?></td>
                                <td>
                                    <a href="edit_buku.php?id=<?php echo $buku['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="hapus_buku.php?id=<?php echo $buku['id']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Belum ada data buku</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daftar Member Terbaru -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-users"></i> Member Terbaru</h3>
                <a href="kelola_member.php" class="btn-add">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($member_result) > 0): ?>
                            <?php while($member = mysqli_fetch_assoc($member_result)): ?>
                            <tr>
                                <td><?php echo $member['id']; ?></td>
                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                <td><?php echo htmlspecialchars($member['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><span class="badge badge-member">Member</span></td>
                                <td>
                                    <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="hapus_member.php?id=<?php echo $member['id']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Belum ada data member</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Script JavaScript untuk Toggle Menu Mobile -->
    <script>
        function toggleSidebar() {
            const sidebar = id => document.getElementById(id);
            sidebar('sidebar').classList.toggle('active');
            sidebar('sidebarOverlay').classList.toggle('active');
        }
    </script>
</body>
</html>