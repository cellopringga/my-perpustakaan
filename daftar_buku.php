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

// ========== KONFIGURASI PAGINATION ==========
$limit = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ========== FILTER PENCARIAN ==========
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';

// ========== QUERY DASAR ==========
$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (judul LIKE '%$search%' OR penulis LIKE '%$search%' OR kode_buku LIKE '%$search%')";
}
if (!empty($kategori)) {
    $where .= " AND kategori = '$kategori'";
}

// ========== AMBIL TOTAL BUKU ==========
$count_query = "SELECT COUNT(*) as total FROM buku $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// ========== AMBIL DATA BUKU ==========
$query = "SELECT * FROM buku $where ORDER BY id DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// ========== AMBIL DAFTAR KATEGORI UNIK ==========
$kategori_query = "SELECT DISTINCT kategori FROM buku WHERE kategori IS NOT NULL AND kategori != ''";
$kategori_result = mysqli_query($conn, $kategori_query);

// ========== CEK JUMLAH PINJAMAN AKTIF MEMBER ==========
$query_pinjam_aktif = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = $user_id AND status = 'dipinjam'";
$result_pinjam_aktif = mysqli_query($conn, $query_pinjam_aktif);
$total_pinjam_aktif = mysqli_fetch_assoc($result_pinjam_aktif)['total'];
$max_pinjam = 3;
$bisa_pinjam = ($total_pinjam_aktif < $max_pinjam);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku - Perpustakaan Digital</title>
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

        /* Custom Alert Messages */
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

        /* Status Info Banner */
        .info-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            font-size: 14px;
            font-weight: 600;
        }

        .pinjam-count {
            color: #059669;
            font-weight: 700;
        }

        /* Search Section */
        .search-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .search-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-input, .filter-select {
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            background: #f8fafc;
        }

        .search-input {
            flex: 2;
            min-width: 200px;
        }

        .filter-select {
            flex: 1;
            min-width: 150px;
        }

        .search-input:focus, .filter-select:focus {
            border-color: #10b981;
            background: #ffffff;
        }

        .btn-search {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-search:hover {
            background: #059669;
        }

        .btn-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Book Grid Layout */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .book-card {
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }

        .book-cover {
            background: #e6f4ed;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #059669;
            border-bottom: 1px solid #f1f5f9;
            overflow: hidden;
        }

        /* Penambahan style khusus image pada cover */
        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .book-title {
            font-size: 16px;
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

        .book-details {
            margin: 12px 0;
            font-size: 13px;
        }

        .book-details div {
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            color: #64748b;
        }

        .book-details .value {
            font-weight: 600;
            color: #1e293b;
        }

        .stock {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .stock-available {
            background: #d1fae5;
            color: #047857;
        }

        .stock-out {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Buttons */
        .btn-borrow {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #10b981;
            color: white;
            border: none;
            padding: 11px;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            font-size: 13px;
            margin-top: 10px;
            transition: background 0.2s;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-borrow:hover:not(:disabled) {
            background: #059669;
            color: white;
        }

        .btn-borrow:disabled, .btn-borrow.disabled {
            background: #cbd5e1;
            color: #64748b;
            cursor: not-allowed;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            flex-wrap: wrap;
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

        .no-results {
            text-align: center;
            padding: 50px 20px;
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            color: #64748b;
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
            .search-form { flex-direction: column; }
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
            
            <li class="has-dropdown active open" id="dropdownBuku">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownBuku')">
                    <i class="fa-solid fa-book"></i> Daftar Buku <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="daftar_buku.php" style="color: #059669; font-weight: 700;">• Semua Buku</a></li>
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
            <h1>Daftar Buku</h1>
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

        <!-- Borrow Status Info -->
        <div class="info-box">
            <div>
                <i class="fa-solid fa-book-bookmark" style="color: #10b981; margin-right: 6px;"></i> 
                Status Peminjaman: <span class="pinjam-count"><?= $total_pinjam_aktif; ?>/<?= $max_pinjam; ?></span> buku aktif
            </div>
            <?php if (!$bisa_pinjam): ?>
                <div style="color: #ef4444;">
                    <i class="fa-solid fa-circle-exclamation"></i> Batas maksimal 3 buku tercapai!
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Section -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Cari berdasarkan judul, penulis, atau kode..." value="<?= htmlspecialchars($search); ?>">
                
                <select name="kategori" class="filter-select">
                    <option value="">Semua Kategori</option>
                    <?php 
                    mysqli_data_seek($kategori_result, 0);
                    while($kat = mysqli_fetch_assoc($kategori_result)): 
                    ?>
                    <option value="<?= htmlspecialchars($kat['kategori']); ?>" <?= ($kategori == $kat['kategori']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($kat['kategori']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="btn-search">
                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                </button>
                <a href="daftar_buku.php" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </form>
        </div>

        <!-- Book Collection Grid -->
        <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="book-grid">
            <?php while($buku = mysqli_fetch_assoc($result)): ?>
            <div class="book-card">
                <div class="book-cover">
                    <?php 
                        // Cek nama kolom gambar di database (misal: cover, gambar, atau foto)
                        $gambar = $buku['cover'] ?? $buku['gambar'] ?? $buku['foto'] ?? '';
                        $path_gambar = 'uploads/' . $gambar; // sesuaikan folder tempat menyimpan foto
                        
                        if (!empty($gambar) && file_exists($path_gambar)): 
                    ?>
                        <img src="<?= htmlspecialchars($path_gambar); ?>" alt="<?= htmlspecialchars($buku['judul']); ?>">
                    <?php else: ?>
                        <?php 
                            $iconClass = 'fa-book'; 
                            if (stripos($buku['judul'], 'php') !== false) $iconClass = 'fa-code'; 
                            elseif (stripos($buku['judul'], 'javascript') !== false) $iconClass = 'fa-square-js'; 
                            elseif (stripos($buku['judul'], 'python') !== false) $iconClass = 'fa-brands fa-python'; 
                        ?>
                        <i class="fa-solid <?= $iconClass; ?>"></i>
                    <?php endif; ?>
                </div>

                <div class="book-info">
                    <div>
                        <div class="book-title"><?= htmlspecialchars($buku['judul']); ?></div>
                        <div class="book-author">
                            <i class="fa-solid fa-pen-nib" style="font-size: 11px;"></i> <?= htmlspecialchars($buku['penulis']); ?>
                        </div>
                        
                        <div class="book-details">
                            <div>
                                <span class="label">Kode Buku:</span>
                                <span class="value"><?= htmlspecialchars($buku['kode_buku']); ?></span>
                            </div>
                            <div>
                                <span class="label">Kategori:</span>
                                <span class="value"><?= htmlspecialchars($buku['kategori'] ?? '-'); ?></span>
                            </div>
                            <div>
                                <span class="label">Ketersediaan:</span>
                                <span class="value">
                                    <span class="stock <?= ($buku['stok'] > 0) ? 'stock-available' : 'stock-out'; ?>">
                                        <?= $buku['stok']; ?> tersisa
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Action Pinjam Buku -->
                    <?php if ($buku['stok'] > 0 && $bisa_pinjam): ?>
                        <a href="proses_pinjam.php?id=<?= $buku['id']; ?>" class="btn-borrow" onclick="return confirm('Pinjam buku <?= htmlspecialchars($buku['judul']); ?>?')">
                            <i class="fa-solid fa-download"></i> Pinjam Buku
                        </a>
                    <?php else: ?>
                        <button class="btn-borrow" disabled>
                            <?php if ($buku['stok'] <= 0): ?>
                                <i class="fa-solid fa-circle-xmark"></i> Stok Habis
                            <?php else: ?>
                                <i class="fa-solid fa-ban"></i> Batas Maksimal
                            <?php endif; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination Section -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= ($page-1); ?>&search=<?= urlencode($search); ?>&kategori=<?= urlencode($kategori); ?>">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&kategori=<?= urlencode($kategori); ?>"><?= $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= ($page+1); ?>&search=<?= urlencode($search); ?>&kategori=<?= urlencode($kategori); ?>">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="no-results">
            <i class="fa-solid fa-folder-open" style="font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
            <h3>Buku Tidak Ditemukan</h3>
            <p style="font-size: 13px; margin-top: 4px; margin-bottom: 16px;">Coba gunakan kata kunci atau filter kategori yang berbeda.</p>
            <a href="daftar_buku.php" class="btn-reset" style="display: inline-flex;">
                <i class="fa-solid fa-rotate-left"></i> Tampilkan Semua Buku
            </a>
        </div>
        <?php endif; ?>

    </div>

    <script>
        function toggleSidebar() { 
            document.getElementById('sidebar').classList.toggle('show'); 
        }

        function toggleDropdown(id) { 
            const el = document.getElementById(id); 
            if (el) el.classList.toggle('open'); 
        }

        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 768 && sidebar && toggleBtn && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        setTimeout(() => { 
            document.querySelectorAll('.alert').forEach(a => { 
                setTimeout(() => { if (a) a.style.display = 'none'; }, 5000); 
            }); 
        }, 100);
    </script>
</body>
</html>