<?php
session_start();
require_once 'config.php';

// Cek login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ========== PROSES PENGEMBALIAN ==========
if (isset($_GET['kembalikan'])) {
    $id = (int)$_GET['kembalikan'];
    
    // Ambil data peminjaman
    $query = mysqli_query($conn, "SELECT * FROM peminjaman WHERE id='$id'");
    $pinjam = mysqli_fetch_assoc($query);
    
    if ($pinjam && $pinjam['status'] == 'dipinjam') {
        $tanggal_kembali = date('Y-m-d');
        $jatuh_tempo = $pinjam['tanggal_jatuh_tempo'];
        
        // Hitung denda jika terlambat
        $denda = 0;
        $hari_terlambat = 0;
        if ($tanggal_kembali > $jatuh_tempo) {
            $selisih = strtotime($tanggal_kembali) - strtotime($jatuh_tempo);
            $hari_terlambat = ceil($selisih / (60 * 60 * 24));
            $denda = $hari_terlambat * 2000; // Rp 2000 per hari
        }
        
        // UPDATE status dan tanggal kembali
        $update = "UPDATE peminjaman SET 
                    status = 'dikembalikan', 
                    tanggal_kembali = '$tanggal_kembali',
                    denda = '$denda'
                  WHERE id = '$id'";
        
        if (mysqli_query($conn, $update)) {
            // Update stok buku (+1)
            mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = '{$pinjam['buku_id']}'");
            
            if ($denda > 0) {
                $_SESSION['success'] = "Buku dikembalikan! Terlambat $hari_terlambat hari, Denda Rp " . number_format($denda, 0, ',', '.');
            } else {
                $_SESSION['success'] = "Buku berhasil dikembalikan!";
            }
        } else {
            $_SESSION['error'] = "Gagal mengembalikan buku: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Data peminjaman tidak ditemukan atau sudah dikembalikan!";
    }
    
    header("Location: kelola_peminjaman.php");
    exit();
}

// ========== HAPUS PEMINJAMAN ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM peminjaman WHERE id='$id'");
    $_SESSION['success'] = "Data peminjaman berhasil dihapus!";
    header("Location: kelola_peminjaman.php");
    exit();
}

// ========== EDIT DENDA ==========
if (isset($_GET['edit_denda'])) {
    $id = (int)$_GET['edit_denda'];
    $denda_baru = (int)$_GET['denda'];
    mysqli_query($conn, "UPDATE peminjaman SET denda = $denda_baru WHERE id = $id");
    $_SESSION['success'] = "Denda berhasil diperbarui!";
    header("Location: kelola_peminjaman.php");
    exit();
}

// ========== FILTER ==========
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';
$where = "";
if ($status_filter == 'dipinjam') $where = "WHERE p.status = 'dipinjam'";
if ($status_filter == 'dikembalikan') $where = "WHERE p.status = 'dikembalikan'";

// ========== STATISTIK ==========
$total_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='dipinjam'"))['total'];
$total_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='dikembalikan'"))['total'];
$total_terlambat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='dipinjam' AND tanggal_jatuh_tempo < CURDATE()"))['total'];
$total_denda_keseluruhan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(denda), 0) as total FROM peminjaman WHERE denda > 0"))['total'];

// ========== AMBIL DATA ==========
$query = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap, b.judul, b.kode_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    $where
    ORDER BY p.tanggal_pinjam DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman - Perpustakaan Digital</title>
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
            z-index: 100;
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

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            padding: 28px;
        }

        /* Top Header Banner */
        .top-banner {
            background: #edf4f0;
            border: 1px solid #d1e3d7;
            border-radius: 20px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .top-banner h1 {
            font-size: 22px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-banner p {
            font-size: 13px;
            color: #718096;
            font-weight: 500;
        }

        .btn-back {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
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

        .stat-icon.pinjam { background: #fef3c7; color: #d97706; }
        .stat-icon.kembali { background: #d1fae5; color: #059669; }
        .stat-icon.terlambat { background: #fee2e2; color: #dc2626; }
        .stat-icon.denda { background: #fef3c7; color: #b45309; }

        .stat-info p {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .stat-info h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Card Container */
        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #e6f4ed;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Filter Box */
        .filter-container {
            margin-bottom: 20px;
        }

        .filter-box {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-box select {
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            background: #f8fafc;
            color: #334155;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-box select:focus {
            background: #ffffff;
            border-color: #10b981;
        }

        .filter-box button {
            background: #334155;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-box button:hover {
            background: #1e293b;
        }

        .btn-reset {
            background: #ef4444;
            color: white;
            padding: 10px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-reset:hover {
            background: #dc2626;
        }

        /* Data Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 14px 16px;
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-dipinjam {
            background: #fef3c7;
            color: #d97706;
        }

        .status-dikembalikan {
            background: #d1fae5;
            color: #059669;
        }

        .status-terlambat {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Action Buttons */
        .btn-kembali {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-kembali:hover { background: #059669; }

        .btn-hapus {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-hapus:hover { background: #dc2626; }

        .btn-edit-denda {
            background: #f59e0b;
            color: white;
            padding: 3px 8px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
            transition: all 0.2s;
        }

        .btn-edit-denda:hover { background: #d97706; }

        /* Fine Text */
        .denda-positif {
            color: #dc2626;
            font-weight: 700;
        }

        .denda-nol {
            color: #059669;
            font-weight: 600;
        }

        /* Modal styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .modal-card h3 {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px !important;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            .main-content {
                margin-left: 0;
            }
            .top-banner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fa-solid fa-book-bookmark"></i></div>
            <div>
                <h3>Admin Panel</h3>
                <p style="font-size: 11px; color: #94a3b8; font-weight: 500;">Perpustakaan Digital</p>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
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
            <li class="active">
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

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Top Header Banner -->
        <div class="top-banner">
            <div>
                <h1><i class="fa-solid fa-arrows-rotate" style="color: #10b981;"></i> Kelola Peminjaman Buku</h1>
                <p>Kelola seluruh aktivitas sirkulasi dan transaksi peminjaman perpus digital.</p>
            </div>
            <a href="dashboard_admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Alert Notification -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-xmark"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pinjam"><i class="fa-solid fa-book-open"></i></div>
                <div class="stat-info">
                    <p>Sedang Dipinjam</p>
                    <h3><?= $total_pinjam ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon kembali"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info">
                    <p>Sudah Dikembalikan</p>
                    <h3><?= $total_kembali ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon terlambat"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="stat-info">
                    <p>Peminjaman Terlambat</p>
                    <h3><?= $total_terlambat ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon denda"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="stat-info">
                    <p>Total Denda</p>
                    <h3>Rp <?= number_format($total_denda_keseluruhan, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <div class="filter-container">
                <form method="GET" class="filter-box">
                    <select name="status">
                        <option value="semua" <?= $status_filter == 'semua' ? 'selected' : '' ?>>Semua Status Peminjaman</option>
                        <option value="dipinjam" <?= $status_filter == 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                        <option value="dikembalikan" <?= $status_filter == 'dikembalikan' ? 'selected' : '' ?>>Sudah Dikembalikan</option>
                    </select>
                    <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
                    <?php if ($status_filter != 'semua'): ?>
                        <a href="kelola_peminjaman.php" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Peminjaman -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query)): 
                                $is_terlambat = ($row['status'] == 'dipinjam' && $row['tanggal_jatuh_tempo'] < date('Y-m-d'));
                                $denda = (int)$row['denda'];
                                $is_denda = ($denda > 0);
                            ?>
                                <tr style="<?= $is_denda ? 'background: #fffcf5;' : '' ?>">
                                    <td>#<?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['judul']) ?></strong><br>
                                        <span style="font-size: 11px; color: #64748b;"><i class="fa-solid fa-barcode"></i> <?= $row['kode_buku'] ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) ?>
                                        <?php if ($is_terlambat): ?>
                                            <br><span style="color:#ef4444; font-size: 11px; font-weight: 600;"><i class="fa-solid fa-triangle-exclamation"></i> Lewat tempo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['tanggal_kembali']): ?>
                                            <span style="color: #10b981;"><i class="fa-solid fa-calendar-check"></i> <?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;"><i class="fa-regular fa-clock"></i> Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'dikembalikan'): ?>
                                            <span class="status-badge status-dikembalikan"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Dikembalikan</span>
                                        <?php elseif ($is_terlambat): ?>
                                            <span class="status-badge status-terlambat"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Terlambat</span>
                                        <?php else: ?>
                                            <span class="status-badge status-dipinjam"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Dipinjam</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($denda > 0): ?>
                                            <span class="denda-positif">Rp <?= number_format($denda, 0, ',', '.') ?></span><br>
                                            <a href="javascript:void(0)" class="btn-edit-denda" onclick="editDenda(<?= $row['id'] ?>, <?= $denda ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                        <?php else: ?>
                                            <span class="denda-nol">Rp 0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <?php if ($row['status'] == 'dipinjam'): ?>
                                                <a href="?kembalikan=<?= $row['id'] ?>" class="btn-kembali" onclick="return confirm('Kembalikan buku ini?')">
                                                    <i class="fa-solid fa-box-archive"></i> Kembalikan
                                                </a>
                                            <?php endif; ?>
                                            <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus data peminjaman ini?')">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <i class="fa-solid fa-box-open" style="font-size: 32px; margin-bottom: 10px; color: #cbd5e1; display: block;"></i>
                                    Belum ada data peminjaman.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Edit Denda -->
    <div id="modalDenda" class="modal-overlay">
        <div class="modal-card">
            <h3><i class="fa-solid fa-pen-to-square" style="color: #f59e0b;"></i> Edit Denda</h3>
            <form method="GET" action="">
                <input type="hidden" name="edit_denda" id="edit_denda_id">
                <div class="form-group">
                    <label>Nominal Denda (Rp)</label>
                    <input type="number" name="denda" id="edit_denda_nominal" class="form-control" required min="0">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-back" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn-kembali" style="background: #f59e0b;"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editDenda(id, denda) {
            document.getElementById('edit_denda_id').value = id;
            document.getElementById('edit_denda_nominal').value = denda;
            document.getElementById('modalDenda').style.display = 'flex';
        }
        
        function tutupModal() {
            document.getElementById('modalDenda').style.display = 'none';
        }
        
        // Tutup modal klik di luar area modal
        window.onclick = function(event) {
            var modal = document.getElementById('modalDenda');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>