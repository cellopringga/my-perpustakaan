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
$user_username = $_SESSION['username'] ?? 'member';

// Query total denda yang belum dibayar
$query_denda = "SELECT COALESCE(SUM(denda), 0) as total_denda 
                FROM peminjaman 
                WHERE user_id = '$user_id' AND status = 'dipinjam' AND denda > 0";
$result_denda = mysqli_query($conn, $query_denda);
$total_denda = mysqli_fetch_assoc($result_denda)['total_denda'] ?? 0;

// Query buku yang sedang dipinjam
$query_pinjam = "SELECT p.*, b.judul, b.penulis, b.kode_buku 
                 FROM peminjaman p
                 JOIN buku b ON p.buku_id = b.id
                 WHERE p.user_id = '$user_id' AND p.status = 'dipinjam'
                 ORDER BY p.tanggal_pinjam DESC";
$result_pinjam = mysqli_query($conn, $query_pinjam);

// Query riwayat peminjaman (sudah dikembalikan)
$query_riwayat = "SELECT p.*, b.judul, b.penulis, b.kode_buku 
                  FROM peminjaman p
                  JOIN buku b ON p.buku_id = b.id
                  WHERE p.user_id = '$user_id' AND p.status = 'dikembalikan'
                  ORDER BY p.tanggal_kembali DESC
                  LIMIT 10";
$result_riwayat = mysqli_query($conn, $query_riwayat);

// Hitung statistik
$total_dipinjam = mysqli_num_rows($result_pinjam);
$total_riwayat = mysqli_num_rows($result_riwayat);

// Hitung buku yang terlambat
$today = date('Y-m-d');
$query_terlambat = "SELECT COUNT(*) as total 
                    FROM peminjaman 
                    WHERE user_id = '$user_id' 
                    AND status = 'dipinjam' 
                    AND tanggal_jatuh_tempo < '$today'";
$result_terlambat = mysqli_query($conn, $query_terlambat);
$total_terlambat = mysqli_fetch_assoc($result_terlambat)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Member - Perpustakaan Digital</title>
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

        .sidebar-menu li.active a {
            background: #e6f4ed;
            color: #059669;
        }

        .sidebar-menu li a i {
            width: 20px;
            font-size: 16px;
        }

        /* Main Content Layout */
        .main-content {
            margin-left: 250px;
            padding: 28px;
            transition: all 0.3s ease;
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

        .navbar-title-container {
            display: flex;
            align-items: center;
            gap: 12px;
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
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
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
            white-space: nowrap;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Fine Alert Banner */
        .fine-alert {
            background: #fff7ed;
            border: 1px solid #ffedd5;
            border-left: 5px solid #f97316;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .fine-alert .fine-text {
            font-weight: 600;
            color: #c2410c;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pay-btn {
            background: #f97316;
            border: none;
            padding: 8px 18px;
            border-radius: 10px;
            color: #ffffff;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .pay-btn:hover {
            background: #ea580c;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-info p {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.borrowed { background: #e0f2fe; color: #0284c7; }
        .stat-icon.history { background: #e6f4ed; color: #059669; }
        .stat-icon.overdue { background: #fef2f2; color: #ef4444; }

        /* Card Elements */
        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-header h3 {
            color: #1e293b;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-borrow {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-add-borrow:hover {
            background: #059669;
        }

        /* Book Grid Layout */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
        }

        .book-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s;
        }

        .book-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .book-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #0f172a;
            line-height: 1.4;
        }

        .book-author {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .book-meta {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .due-date {
            color: #d97706;
            font-weight: 600;
        }

        .overdue {
            color: #dc2626;
            font-weight: 700;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .status-badge.borrowed {
            background: #fef3c7;
            color: #b45309;
        }

        .btn-return {
            background: #0284c7;
            color: white;
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            font-size: 13px;
            margin-top: 12px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-return:hover {
            background: #0369a1;
        }

        /* Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
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
            font-weight: 700;
        }

        .badge-tepat {
            background: #d1fae5;
            color: #047857;
        }

        .badge-terlambat {
            background: #fee2e2;
            color: #b91c1c;
        }

        .empty-box {
            text-align: center;
            padding: 30px;
            color: #64748b;
            font-size: 14px;
        }

        /* Toggle Button Navigation Mobile */
        .mobile-nav-toggle {
            display: none;
            font-size: 18px;
            background: #e6f4ed;
            color: #10b981;
            border: none;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        /* Background Overlay Gelap saat Mobile Sidebar Terbuka */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 999;
        }

        /* ================= RESPONSIVE DESIGN ================= */
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

            .navbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
                padding: 16px;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .user-info {
                width: 100%;
                justify-content: space-between;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .fine-alert {
                flex-direction: column;
                align-items: flex-start;
            }

            .pay-btn {
                width: 100%;
            }

            .card {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .book-grid {
                grid-template-columns: 1fr;
            }

            .user-name {
                font-size: 12px;
            }

            .btn-logout {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

    <!-- Overlay Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

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
            <li class="active">
                <a href="dashboard_member.php">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="daftar_buku.php">
                    <i class="fa-solid fa-book"></i> Daftar Buku
                </a>
            </li>
            <li>
                <a href="riwayat_peminjaman.php">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Saya
                </a>
            </li>
            <li>
                <a href="profil_member.php">
                    <i class="fa-solid fa-user"></i> Profil Saya
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Top Header Navbar -->
        <div class="navbar">
            <div class="navbar-title-container">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1>Selamat Datang di Sistem Perpustakaan SMA Pasundan Rancaekek</h1>
            </div>
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

        <!-- Alert Notification for Denda -->
        <?php if ($total_denda > 0): ?>
        <div class="fine-alert" id="fineAlert">
            <span class="fine-text">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
                Anda memiliki tunggakan denda sebesar Rp <?= number_format($total_denda, 0, ',', '.'); ?>. Harap segera melunasi denda untuk dapat melakukan peminjaman kembali.
            </span>
            <button class="pay-btn" onclick="window.location.href='bayar_denda.php'">
                <i class="fa-solid fa-wallet"></i> Bayar Denda
            </button>
        </div>
        <?php endif; ?>

        <!-- Statistics Overview Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Sedang Dipinjam</p>
                    <div class="stat-number"><?= $total_dipinjam; ?></div>
                </div>
                <div class="stat-icon borrowed"><i class="fa-solid fa-book-bookmark"></i></div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <p>Riwayat Selesai</p>
                    <div class="stat-number"><?= $total_riwayat; ?></div>
                </div>
                <div class="stat-icon history"><i class="fa-solid fa-square-check"></i></div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <p>Buku Terlambat</p>
                    <div class="stat-number"><?= $total_terlambat; ?></div>
                </div>
                <div class="stat-icon overdue"><i class="fa-solid fa-clock"></i></div>
            </div>
        </div>

        <!-- Section: Active Borrowed Books -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-book-open" style="color: #10b981;"></i> Buku yang Sedang Dipinjam</h3>
                <a href="daftar_buku.php" class="btn-add-borrow">
                    <i class="fa-solid fa-plus"></i> Pinjam Buku
                </a>
            </div>

            <?php if ($total_dipinjam > 0): ?>
            <div class="book-grid">
                <?php while($pinjam = mysqli_fetch_assoc($result_pinjam)): 
                    $jatuh_tempo = $pinjam['tanggal_jatuh_tempo'];
                    $is_overdue = ($jatuh_tempo < $today);
                ?>
                <div class="book-card">
                    <div>
                        <div class="book-title"><?= htmlspecialchars($pinjam['judul']); ?></div>
                        <div class="book-author">
                            <i class="fa-solid fa-pen-nib" style="font-size: 11px;"></i> <?= htmlspecialchars($pinjam['penulis']); ?>
                        </div>
                        
                        <div class="book-meta">
                            <span>Pinjam: <strong><?= date('d/m/Y', strtotime($pinjam['tanggal_pinjam'])); ?></strong></span>
                        </div>

                        <div class="book-meta">
                            <span class="<?= $is_overdue ? 'overdue' : 'due-date'; ?>">
                                Jatuh Tempo: <?= date('d/m/Y', strtotime($jatuh_tempo)); ?>
                                <?= $is_overdue ? '(Terlambat)' : ''; ?>
                            </span>
                        </div>

                        <div class="book-meta" style="margin-top: 10px;">
                            <span class="status-badge borrowed"><i class="fa-solid fa-hourglass-half"></i> Dipinjam</span>
                            <?php if($pinjam['denda'] > 0): ?>
                                <span style="color: #ef4444; font-size: 11px; font-weight: 700;">Denda: Rp <?= number_format($pinjam['denda'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button class="btn-return" onclick="returnBook(<?= $pinjam['id']; ?>)">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Kembalikan Buku
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <div class="empty-box">
                    <i class="fa-solid fa-box-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    Tidak ada buku yang sedang dipinjam saat ini.
                </div>
            <?php endif; ?>
        </div>

        <!-- Section: Borrowing History -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-history" style="color: #10b981;"></i> Riwayat Peminjaman Terakhir</h3>
                <a href="riwayat_peminjaman.php" class="btn-add-borrow" style="background: #64748b;">Lihat Semua</a>
            </div>

            <?php if ($total_riwayat > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode Buku</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Denda</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($result_riwayat, 0);
                        while($riwayat = mysqli_fetch_assoc($result_riwayat)): 
                            $status_class = ($riwayat['denda'] > 0) ? 'badge-terlambat' : 'badge-tepat';
                            $status_text = ($riwayat['denda'] > 0) ? 'Terlambat' : 'Tepat Waktu';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($riwayat['kode_buku']); ?></strong></td>
                            <td><?= htmlspecialchars($riwayat['judul']); ?></td>
                            <td><?= date('d/m/Y', strtotime($riwayat['tanggal_pinjam'])); ?></td>
                            <td><?= date('d/m/Y', strtotime($riwayat['tanggal_kembali'])); ?></td>
                            <td>Rp <?= number_format($riwayat['denda'], 0, ',', '.'); ?></td>
                            <td><span class="badge <?= $status_class; ?>"><?= $status_text; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-box">
                    <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    Belum ada riwayat peminjaman buku.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Script JavaScript untuk Toggle Menu Mobile & Konfirmasi Kembali -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function returnBook(peminjamanId) {
            if(confirm('Apakah Anda yakin ingin mengembalikan buku ini?')) {
                window.location.href = 'proses_kembali.php?id=' + peminjamanId;
            }
        }
    </script>
</body>
</html>