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

// ========== FILTER STATUS ==========
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'semua';

// ========== KONFIGURASI PAGINATION ==========
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ========== QUERY DASAR ==========
$where = "WHERE p.user_id = $user_id";
if ($filter_status == 'dipinjam') {
    $where .= " AND p.status = 'dipinjam'";
} elseif ($filter_status == 'dikembalikan') {
    $where .= " AND p.status = 'dikembalikan'";
}

// ========== AMBIL TOTAL DATA ==========
$count_query = "SELECT COUNT(*) as total FROM peminjaman p $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// ========== AMBIL DATA RIWAYAT ==========
$query = "SELECT p.*, b.judul, b.penulis, b.kode_buku, b.penerbit 
          FROM peminjaman p
          JOIN buku b ON p.buku_id = b.id
          $where 
          ORDER BY p.tanggal_pinjam DESC 
          LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// ========== HITUNG STATISTIK ==========
$query_stat = "SELECT 
                    SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as sedang_dipinjam,
                    SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END) as sudah_dikembalikan,
                    SUM(denda) as total_denda
               FROM peminjaman 
               WHERE user_id = $user_id";
$result_stat = mysqli_query($conn, $query_stat);
$stats = mysqli_fetch_assoc($result_stat);
$sedang_dipinjam = $stats['sedang_dipinjam'] ?? 0;
$sudah_dikembalikan = $stats['sudah_dikembalikan'] ?? 0;
$total_denda = $stats['total_denda'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Perpustakaan Digital</title>
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

        /* Stats Cards Layout */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
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

        .icon-kembali {
            background: #d1fae5;
            color: #059669;
        }

        .icon-denda {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Filter Section */
        .filter-section {
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn.active {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
        }

        .filter-btn:hover:not(.active) {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Table Design */
        .table-container {
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .data-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .data-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        /* Status Badge */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .badge-dipinjam {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-dikembalikan {
            background: #d1fae5;
            color: #047857;
        }

        .overdue {
            color: #dc2626;
            font-weight: 700;
        }

        .btn-return {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .btn-return:hover {
            background: #059669;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 20px;
            flex-wrap: wrap;
            border-top: 1px solid #f1f5f9;
        }

        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #475569;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .pagination .active {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state .icon {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 4px;
        }

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

            <li class="has-dropdown active open" id="dropdownRiwayat">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownRiwayat')">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Saya <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="riwayat_peminjaman.php" style="color: #059669; font-weight: 700;">• Riwayat Peminjaman</a></li>
                </ul>
            </li>

            <li class="has-dropdown" id="dropdownProfil">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownProfil')">
                    <i class="fa-solid fa-user"></i> Profil Saya <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="profil_member.php">• Lihat Profil</a></li>
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
            <h1>Riwayat Peminjaman</h1>
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

        <!-- Stats Grid Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Sedang Dipinjam</h3>
                    <div class="stat-number"><?= $sedang_dipinjam; ?></div>
                </div>
                <div class="stat-icon icon-dipinjam">
                    <i class="fa-solid fa-book-bookmark"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Sudah Dikembalikan</h3>
                    <div class="stat-number"><?= $sudah_dikembalikan; ?></div>
                </div>
                <div class="stat-icon icon-kembali">
                    <i class="fa-solid fa-circle-check"></i>
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

        <!-- Filter Buttons Section -->
        <div class="filter-section">
            <a href="?status=semua" class="filter-btn <?= ($filter_status == 'semua') ? 'active' : ''; ?>">
                <i class="fa-solid fa-list-check"></i> Semua
            </a>
            <a href="?status=dipinjam" class="filter-btn <?= ($filter_status == 'dipinjam') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book-open"></i> Sedang Dipinjam
            </a>
            <a href="?status=dikembalikan" class="filter-btn <?= ($filter_status == 'dikembalikan') ? 'active' : ''; ?>">
                <i class="fa-solid fa-rotate-left"></i> Sudah Dikembalikan
            </a>
        </div>

        <!-- Table Riwayat Section -->
        <div class="table-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Buku</th>
                            <th>Judul Buku</th>
                            <th>Penulis</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Tgl Kembali</th>
                            <th>Denda</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        $today = date('Y-m-d');
                        while($row = mysqli_fetch_assoc($result)): 
                            $is_overdue = ($row['status'] == 'dipinjam' && $row['tanggal_jatuh_tempo'] < $today);
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: #64748b;"><?= $no++; ?></td>
                            <td><span style="font-family: monospace; font-weight: 600; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;"><?= htmlspecialchars($row['kode_buku']); ?></span></td>
                            <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($row['judul']); ?></td>
                            <td><?= htmlspecialchars($row['penulis']); ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                            <td class="<?= $is_overdue ? 'overdue' : ''; ?>">
                                <?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])); ?>
                                <?php if($is_overdue): ?>
                                    <span style="font-size: 11px; display: block;"><i class="fa-solid fa-triangle-exclamation"></i> Terlambat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-'; ?>
                            </td>
                            <td>
                                <?php if($row['denda'] > 0): ?>
                                    <span style="color: #dc2626; font-weight: 700;">Rp <?= number_format($row['denda'], 0, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">Rp 0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= ($row['status'] == 'dipinjam') ? 'badge-dipinjam' : 'badge-dikembalikan'; ?>">
                                    <?= ($row['status'] == 'dipinjam') ? 'Dipinjam' : 'Dikembalikan'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['status'] == 'dipinjam'): ?>
                                    <a href="proses_kembali.php?id=<?= $row['id']; ?>" 
                                       class="btn-return" 
                                       onclick="return confirm('Kembalikan buku <?= htmlspecialchars($row['judul']); ?>?')">
                                        <i class="fa-solid fa-arrow-rotate-left"></i> Kembalikan
                                    </a>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= ($page-1); ?>&status=<?= $filter_status; ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i; ?>&status=<?= $filter_status; ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= ($page+1); ?>&status=<?= $filter_status; ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state">
                <div class="icon"><i class="fa-solid fa-box-open"></i></div>
                <h3>Belum Ada Riwayat Peminjaman</h3>
                <p style="font-size: 13px; margin-top: 4px; margin-bottom: 16px;">Anda belum memiliki catatan transaksi peminjaman buku.</p>
                <a href="daftar_buku.php" class="btn-return" style="padding: 10px 20px; font-size: 13px;">
                    <i class="fa-solid fa-book"></i> Pinjam Buku Sekarang
                </a>
            </div>
            <?php endif; ?>
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