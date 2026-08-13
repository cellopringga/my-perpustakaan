<?php
session_start();
require_once 'config.php';

// Cek login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Generasi token CSRF jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== PROSES EDIT DENDA ==========
if (isset($_POST['edit_denda'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token keamanan tidak valid!";
        header("Location: kelola_denda.php");
        exit();
    }

    $id = (int)$_POST['id'];
    $denda_baru = (int)$_POST['denda'];
    
    $stmt = mysqli_prepare($conn, "UPDATE peminjaman SET denda = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $denda_baru, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Denda berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui denda!";
    }
    mysqli_stmt_close($stmt);
    header("Location: kelola_denda.php");
    exit();
}

// ========== HAPUS DENDA (SET 0) ==========
if (isset($_GET['hapus_denda'])) {
    $id = (int)$_GET['hapus_denda'];
    
    $stmt = mysqli_prepare($conn, "UPDATE peminjaman SET denda = 0 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Denda berhasil dihapus (diubah menjadi Rp 0)!";
    } else {
        $_SESSION['error'] = "Gagal menghapus denda!";
    }
    mysqli_stmt_close($stmt);
    header("Location: kelola_denda.php");
    exit();
}

// ========== LUNASI DENDA ==========
if (isset($_GET['lunasi'])) {
    $id = (int)$_GET['lunasi'];
    
    // Ambil data peminjaman menggunakan Prepared Statement
    $stmt = mysqli_prepare($conn, "SELECT p.*, u.nama_lengkap, b.judul 
                                  FROM peminjaman p
                                  JOIN users u ON p.user_id = u.id
                                  JOIN buku b ON p.buku_id = b.id
                                  WHERE p.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $denda_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($denda_data && $denda_data['denda'] > 0) {
        // Update status denda menjadi lunas di tabel peminjaman
        $stmt_update = mysqli_prepare($conn, "UPDATE peminjaman SET denda = 0, status_pembayaran = 'lunas' WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "i", $id);
        $update = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        if ($update) {
            // Catat log pembayaran
            $admin_id = $_SESSION['admin_id'];
            $jumlah = $denda_data['denda'];
            $user_id = $denda_data['user_id'];
            
            $stmt_log = mysqli_prepare($conn, "INSERT INTO pembayaran_denda (peminjaman_id, user_id, jumlah, admin_id, tanggal_bayar, status) 
                                              VALUES (?, ?, ?, ?, CURDATE(), 'lunas')");
            mysqli_stmt_bind_param($stmt_log, "iiii", $id, $user_id, $jumlah, $admin_id);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);
            
            $_SESSION['success'] = "Pembayaran denda Rp " . number_format($jumlah, 0, ',', '.') . " berhasil! Member: " . htmlspecialchars($denda_data['nama_lengkap']);
        } else {
            $_SESSION['error'] = "Gagal memproses pembayaran denda!";
        }
    } else {
        $_SESSION['error'] = "Data denda tidak ditemukan atau sudah lunas!";
    }
    header("Location: kelola_denda.php");
    exit();
}

// ========== FILTER ==========
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where = "";
if ($filter == 'belum_bayar') {
    $where = "WHERE p.denda > 0";
} elseif ($filter == 'lunas') {
    $where = "WHERE p.denda = 0 AND p.status = 'dikembalikan'";
}

// ========== AMBIL DATA DENDA ==========
$query = mysqli_query($conn, "
    SELECT p.id, p.denda, p.status, p.tanggal_pinjam, p.tanggal_kembali,
           u.id as user_id, u.nama_lengkap, u.username, u.email,
           b.judul, b.kode_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    $where
    ORDER BY p.denda DESC, p.tanggal_pinjam DESC
");

// ========== STATISTIK ==========
$total_denda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(denda) as total FROM peminjaman WHERE denda > 0"))['total'] ?? 0;
$total_belum_bayar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE denda > 0"))['total'];
$total_sudah_bayar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE denda = 0 AND status = 'dikembalikan'"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Denda - Perpustakaan Digital</title>
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

        .stat-icon.total { background: #fef3c7; color: #d97706; }
        .stat-icon.belum { background: #fee2e2; color: #dc2626; }
        .stat-icon.sudah { background: #d1fae5; color: #059669; }

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

        /* Filter Pills */
        .filter-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .filter-btn.active {
            background: #059669;
            color: #ffffff;
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

        .status-belum {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-lunas {
            background: #d1fae5;
            color: #059669;
        }

        /* Action Buttons */
        .btn-edit {
            background: #f59e0b;
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
            border: none;
            cursor: pointer;
        }

        .btn-edit:hover { background: #d97706; }

        .btn-lunasi {
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

        .btn-lunasi:hover { background: #059669; }

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
            <li>
                <a href="kelola_peminjaman.php">
                    <i class="fa-solid fa-arrows-rotate"></i> Peminjaman
                </a>
            </li>
            <li class="active">
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
                <h1><i class="fa-solid fa-wallet" style="color: #10b981;"></i> Kelola Denda</h1>
                <p>Pantau dan kelola seluruh catatan serta pembayaran denda keterlambatan anggota.</p>
            </div>
            <a href="dashboard_admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>

        <!-- Alert Notification -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="stat-info">
                    <p>Total Denda Aktif</p>
                    <h3>Rp <?= number_format($total_denda, 0, ',', '.') ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon belum"><i class="fa-solid fa-circle-exclamation"></i></div>
                <div class="stat-info">
                    <p>Belum Bayar</p>
                    <h3><?= $total_belum_bayar ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon sudah"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info">
                    <p>Sudah Lunas</p>
                    <h3><?= $total_sudah_bayar ?></h3>
                </div>
            </div>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <div class="filter-container">
                <a href="?filter=semua" class="filter-btn <?= $filter == 'semua' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-ul"></i> Semua
                </a>
                <a href="?filter=belum_bayar" class="filter-btn <?= $filter == 'belum_bayar' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Belum Bayar
                </a>
                <a href="?filter=lunas" class="filter-btn <?= $filter == 'lunas' ? 'active' : '' ?>">
                    <i class="fa-solid fa-check-double"></i> Sudah Lunas
                </a>
            </div>

            <!-- Table Denda -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Nominal Denda</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query)): 
                                $denda = (int)$row['denda'];
                                $is_belum_bayar = ($denda > 0);
                            ?>
                                <tr style="<?= $is_belum_bayar ? 'background: #fffcf5;' : '' ?>">
                                    <td>#<?= $row['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><br>
                                        <span style="font-size: 11px; color: #64748b;"><i class="fa-solid fa-at"></i> <?= htmlspecialchars($row['username']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['judul']) ?></strong><br>
                                        <span style="font-size: 11px; color: #64748b;"><i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($row['kode_buku']) ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                    <td>
                                        <?php if ($row['tanggal_kembali']): ?>
                                            <?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $denda > 0 ? 'denda-positif' : 'denda-nol' ?>">
                                            Rp <?= number_format($denda, 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($denda > 0): ?>
                                            <span class="status-badge status-belum"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Belum Bayar</span>
                                        <?php else: ?>
                                            <span class="status-badge status-lunas"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <a href="javascript:void(0)" class="btn-edit" onclick="editDenda(<?= $row['id'] ?>, <?= $denda ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                            <?php if ($denda > 0): ?>
                                                <a href="?lunasi=<?= $row['id'] ?>" class="btn-lunasi" onclick="return confirm('Konfirmasi pelunasan denda ini?')">
                                                    <i class="fa-solid fa-receipt"></i> Lunasi
                                                </a>
                                                <a href="?hapus_denda=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus denda menjadi Rp 0?')">
                                                    <i class="fa-solid fa-trash"></i> Hapus
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 10px; color: #cbd5e1; display: block;"></i>
                                    Tidak ada data denda yang ditemukan.
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
            <h3><i class="fa-solid fa-pen-to-square" style="color: #f59e0b;"></i> Edit Nominal Denda</h3>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nominal Denda (Rp)</label>
                    <input type="number" name="denda" id="edit_denda" class="form-control" required min="0">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-back" onclick="tutupModal()">Batal</button>
                    <button type="submit" name="edit_denda" class="btn-edit" style="background: #10b981;"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editDenda(id, denda) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_denda').value = denda;
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

        // Auto close alert setelah 5 detik
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert) alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>