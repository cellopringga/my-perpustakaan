<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil keyword pencarian
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query dengan pencarian menggunakan Prepared Statements (Aman dari SQL Injection)
if (!empty($keyword)) {
    $buku_query = "SELECT * FROM buku 
                   WHERE judul LIKE ? 
                   OR penulis LIKE ? 
                   OR kode_buku LIKE ? 
                   ORDER BY id DESC";
    
    $stmt = mysqli_prepare($conn, $buku_query);
    $search_param = '%' . $keyword . '%';
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $buku_result = mysqli_stmt_get_result($stmt);
} else {
    $buku_query = "SELECT * FROM buku ORDER BY id DESC";
    $buku_result = mysqli_query($conn, $buku_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Admin Perpustakaan</title>
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

        .banner-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Buttons */
        .btn-add {
            background: #10b981;
            color: white;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }

        .btn-add:hover {
            background: #059669;
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

        /* Card Structure */
        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        /* Search Section Inside Card */
        .search-container {
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box input {
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            width: 320px;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .search-box input:focus {
            background: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .search-box button {
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

        .search-box button:hover {
            background: #1e293b;
        }

        /* Info Pencarian */
        .info-pencarian {
            background: #e6f4ed;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #a7f3d0;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Style Badges */
        .badge-stok {
            background: #dcfce7;
            color: #15803d;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-stok-habis {
            background: #fee2e2;
            color: #b91c1c;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Action Buttons */
        .btn-edit {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            margin-right: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }

        .btn-edit:hover { background: #2563eb; }
        .btn-delete:hover { background: #dc2626; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px !important;
            color: #64748b;
        }

        .empty-state a {
            color: #10b981;
            font-weight: 600;
            text-decoration: none;
        }

        .empty-state a:hover {
            text-decoration: underline;
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
            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
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
            <li class="active">
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

        <!-- Top Header Banner -->
        <div class="top-banner">
            <div>
                <h1><i class="fa-solid fa-book" style="color: #10b981;"></i> Kelola Data Buku</h1>
                <p>Manajemen koleksi buku, pencarian, stok, dan pembaruan katalog.</p>
            </div>
            <div class="banner-actions">
                <a href="tambah_buku.php" class="btn-add"><i class="fa-solid fa-plus"></i> Tambah Buku</a>
                <a href="dashboard_admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            </div>
        </div>

        <div class="card">
            <!-- Search Box Area -->
            <div class="search-container">
                <form method="GET" class="search-box">
                    <input type="text" 
                           name="search" 
                           placeholder="Cari judul, penulis, atau kode buku..." 
                           value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                    <?php if (!empty($keyword)): ?>
                        <a href="kelola_buku.php" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Info Pencarian -->
            <?php if (!empty($keyword)): ?>
                <div class="info-pencarian">
                    <i class="fa-solid fa-circle-info"></i> Menampilkan hasil pencarian untuk: <strong>"<?php echo htmlspecialchars($keyword); ?>"</strong>
                </div>
            <?php endif; ?>

            <!-- Table Buku -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Buku</th>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Penerbit</th>
                            <th>Tahun</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Tersedia</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($buku_result) > 0): ?>
                            <?php $no = 1; while ($buku = mysqli_fetch_assoc($buku_result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($buku['kode_buku']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($buku['judul']); ?></td>
                                    <td><?php echo htmlspecialchars($buku['penulis']); ?></td>
                                    <td><?php echo htmlspecialchars($buku['penerbit'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($buku['tahun_terbit'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($buku['kategori'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($buku['stok'] > 0): ?>
                                            <span class="badge-stok"><i class="fa-solid fa-check"></i> <?php echo (int)$buku['stok']; ?></span>
                                        <?php else: ?>
                                            <span class="badge-stok-habis"><i class="fa-solid fa-xmark"></i> Habis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($buku['stok_tersedia'] > 0): ?>
                                            <span class="badge-stok"><i class="fa-solid fa-check"></i> <?php echo (int)$buku['stok_tersedia']; ?></span>
                                        <?php else: ?>
                                            <span class="badge-stok-habis"><i class="fa-solid fa-xmark"></i> 0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_buku.php?id=<?php echo urlencode($buku['id']); ?>" class="btn-edit">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a href="hapus_buku.php?id=<?php echo urlencode($buku['id']); ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Yakin ingin menghapus buku &quot;<?php echo htmlspecialchars($buku['judul'], ENT_QUOTES); ?>&quot;?')">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="empty-state">
                                    <i class="fa-solid fa-book-open" style="font-size: 32px; margin-bottom: 10px; color: #cbd5e1; display: block;"></i>
                                    <?php if (!empty($keyword)): ?>
                                        Tidak ada buku dengan kata kunci "<strong><?php echo htmlspecialchars($keyword); ?></strong>"
                                    <?php else: ?>
                                        Belum ada data buku. <a href="tambah_buku.php">Tambah buku sekarang</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>