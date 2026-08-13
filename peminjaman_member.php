<?php
session_start();
require_once 'config.php';

// Cek login member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['nama_lengkap'] ?? 'Member';

// Proses AJAX - Ajukan pengembalian
if (isset($_GET['ajukan_kembali'])) {
    $peminjaman_id = (int)$_GET['ajukan_kembali'];
    
    // Cek peminjaman milik user
    $cek = mysqli_query($conn, "SELECT p.*, b.judul, b.kode_buku 
                                FROM peminjaman p 
                                JOIN buku b ON p.buku_id = b.id 
                                WHERE p.id='$peminjaman_id' AND p.user_id='$user_id' AND p.status='dipinjam'");
    
    if (mysqli_num_rows($cek) > 0) {
        $pinjam = mysqli_fetch_assoc($cek);
        $tanggal_pengajuan = date('Y-m-d');
        
        // Cek apakah sudah pernah mengajukan
        $cek_pengajuan = mysqli_query($conn, "SELECT * FROM pengembalian WHERE peminjaman_id='$peminjaman_id'");
        
        if (mysqli_num_rows($cek_pengajuan) > 0) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah mengajukan pengembalian untuk buku ini!']);
        } else {
            // Insert ke tabel pengembalian
            $insert = mysqli_query($conn, "INSERT INTO pengembalian (peminjaman_id, user_id, buku_id, tanggal_kembali, status) 
                                          VALUES ('$peminjaman_id', '$user_id', '{$pinjam['buku_id']}', '$tanggal_pengajuan', 'pending')");
            
            if ($insert) {
                echo json_encode(['success' => true, 'message' => 'Pengajuan pengembalian berhasil! Menunggu konfirmasi admin.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal mengajukan: ' . mysqli_error($conn)]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan!']);
    }
    exit();
}

// Ambil data peminjaman aktif (dipinjam)
$active_loans = mysqli_query($conn, "
    SELECT p.*, b.judul, b.kode_buku, b.penulis,
           peng.status as status_pengembalian, peng.id as pengembalian_id
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    LEFT JOIN pengembalian peng ON p.id = peng.peminjaman_id
    WHERE p.user_id = '$user_id' AND p.status = 'dipinjam'
    ORDER BY p.tanggal_jatuh_tempo ASC
");

// Ambil riwayat peminjaman (selesai/dikembalikan)
$riwayat = mysqli_query($conn, "
    SELECT p.*, b.judul, b.kode_buku, b.penulis,
           peng.tanggal_kembali as tgl_kembali, peng.denda, peng.status as status_pengembalian
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    LEFT JOIN pengembalian peng ON p.id = peng.peminjaman_id
    WHERE p.user_id = '$user_id' AND p.status = 'dikembalikan'
    ORDER BY p.tanggal_pinjam DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Saya - Perpustakaan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f9;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100%;
            background: linear-gradient(135deg, #2c3e50, #1a1a2e); color: white;
            transition: all 0.3s; z-index: 100; overflow-y: auto;
        }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 40px; }
        .sidebar-header h3 { margin-top: 10px; font-size: 18px; }
        .sidebar-menu { list-style: none; padding: 20px 0; }
        .sidebar-menu li a {
            display: flex; align-items: center; padding: 12px 25px;
            color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; gap: 10px;
        }
        .sidebar-menu li a:hover { background: rgba(255,255,255,0.1); color: white; padding-left: 30px; }
        .sidebar-menu li.active a { background: rgba(255,255,255,0.2); border-left: 4px solid #667eea; color: white; }
        
        .sidebar-menu li.has-dropdown > a::after { content: "▼"; margin-left: auto; font-size: 10px; transition: transform 0.3s; }
        .sidebar-menu li.has-dropdown.open > a::after { transform: rotate(180deg); }
        .dropdown-menu {
            list-style: none; padding-left: 45px; max-height: 0; overflow: hidden;
            transition: max-height 0.3s ease-out; background: rgba(0,0,0,0.2);
        }
        .sidebar-menu li.open .dropdown-menu { max-height: 300px; }
        .dropdown-menu li a { padding: 10px 25px; font-size: 14px; }
        
        /* Main Content */
        .main-content { margin-left: 260px; padding: 20px; }
        .navbar {
            background: white; padding: 15px 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex;
            justify-content: space-between; align-items: center; margin-bottom: 20px;
        }
        .navbar h1 { font-size: 20px; color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { color: #333; font-weight: 600; }
        .btn-logout { background: #e74c3c; color: white; padding: 8px 15px; border-radius: 8px; text-decoration: none; font-size: 14px; }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .alert-info { background: #e0f2fe; color: #0369a1; border-left: 4px solid #0ea5e9; }
        
        .active-loans {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .loan-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #F59E0B;
        }
        .loan-card.terlambat { border-left-color: #DC2626; background: #FEF2F2; }
        .loan-card.pending { border-left-color: #0EA5E9; background: #E0F2FE; }
        .book-title { font-size: 16px; font-weight: 600; color: #2C3E50; }
        .book-code { font-size: 12px; color: #666; margin: 5px 0; }
        .due-date { font-size: 13px; margin: 10px 0; }
        .due-date span { font-weight: 600; }
        .warning-text { color: #DC2626; font-size: 12px; margin-top: 5px; }
        .info-text { color: #0369A1; font-size: 12px; margin-top: 5px; }
        .btn-return {
            background: #F59E0B;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-return:hover { background: #D97706; }
        .btn-pending {
            background: #94A3B8;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            margin-top: 10px;
            width: 100%;
            cursor: not-allowed;
        }
        
        .table-wrapper {
            background: white;
            border-radius: 15px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        td { padding: 12px 15px; border-bottom: 1px solid #e0e0e0; }
        tr:hover { background: #f8f9fa; }
        
        .status-dipinjam { background: #FEF3C7; color: #D97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-terlambat { background: #FEE2E2; color: #DC2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-dikembalikan { background: #D1FAE5; color: #059669; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-pending { background: #E0F2FE; color: #0369A1; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        
        .empty-state { text-align: center; padding: 40px; color: #888; }
        .info-box {
            background: #E8F1FA;
            padding: 15px 20px;
            border-radius: 12px;
            margin-top: 25px;
        }
        .info-box ul { margin-left: 20px; margin-top: 8px; }
        .info-box li { margin: 5px 0; }
        
        .mobile-toggle { display: none; position: fixed; top: 15px; left: 15px; background: #2c3e50; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; z-index: 101; font-size: 20px; }
        
        @media (max-width: 768px) {
            .mobile-toggle { display: block; }
            .sidebar { left: -260px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; padding-top: 60px; }
            th, td { padding: 8px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">📚</div>
            <h3>Member Area</h3>
            <p style="font-size: 12px; opacity: 0.7;">Perpustakaan Digital</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard_member.php"><span>📊</span> Dashboard</a></li>
            <li class="has-dropdown" id="dropdownBuku">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownBuku')">
                    <span>📖</span> Daftar Buku <span class="dropdown-icon">▼</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="daftar_buku.php">📚 Semua Buku</a></li>
                </ul>
            </li>
            <li class="has-dropdown active" id="dropdownRiwayat">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownRiwayat')">
                    <span>📜</span> Riwayat Saya <span class="dropdown-icon">▼</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="peminjaman_member.php">📋 Peminjaman Saya</a></li>
                    <li><a href="riwayat_peminjaman.php">📋 Riwayat Peminjaman</a></li>
                </ul>
            </li>
            <li class="has-dropdown" id="dropdownProfil">
                <a href="javascript:void(0)" onclick="toggleDropdown('dropdownProfil')">
                    <span>👤</span> Profil Saya <span class="dropdown-icon">▼</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="profil_member.php">👤 Lihat Profil</a></li>
                    <li><a href="edit_profil.php">✏️ Edit Profil</a></li>
                    <li><a href="ganti_password.php">🔑 Ganti Password</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="navbar">
            <h1>📋 Peminjaman Saya</h1>
            <div class="user-info">
                <span class="user-name">👤 <?= htmlspecialchars($user_nama) ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>

        <div id="alertMessage"></div>

        <!-- Tombol Pinjam Buku -->
        <div style="margin-bottom: 20px;">
            <a href="daftar_buku.php" class="btn-return" style="width: auto; background: #10B981; display: inline-block; text-decoration: none;">🔍 Cari & Pinjam Buku</a>
        </div>

        <!-- Buku yang Sedang Dipinjam -->
        <?php if (mysqli_num_rows($active_loans) > 0): ?>
        <h2 style="margin-bottom: 15px;">📖 Buku yang Sedang Dipinjam</h2>
        <div class="active-loans">
            <?php while ($loan = mysqli_fetch_assoc($active_loans)): 
                $is_terlambat = $loan['tanggal_jatuh_tempo'] < date('Y-m-d');
                $is_pending = ($loan['status_pengembalian'] == 'pending');
            ?>
            <div class="loan-card <?= $is_terlambat ? 'terlambat' : ($is_pending ? 'pending' : '') ?>">
                <div class="book-title">📖 <?= htmlspecialchars($loan['judul']) ?></div>
                <div class="book-code">Kode: <?= $loan['kode_buku'] ?></div>
                <div class="due-date">
                    📅 Jatuh tempo: <span style="color:<?= $is_terlambat ? '#DC2626' : '#333' ?>">
                        <?= date('d/m/Y', strtotime($loan['tanggal_jatuh_tempo'])) ?>
                    </span>
                    <?php if ($is_terlambat): ?>
                        <div class="warning-text">⚠️ Terlambat! Segera ajukan pengembalian.</div>
                    <?php endif; ?>
                    <?php if ($is_pending): ?>
                        <div class="info-text">⏳ Menunggu konfirmasi admin...</div>
                    <?php endif; ?>
                </div>
                <?php if (!$is_pending): ?>
                    <button class="btn-return" onclick="ajukanKembali(<?= $loan['id'] ?>, '<?= htmlspecialchars($loan['judul']) ?>')">
                        📦 Ajukan Pengembalian
                    </button>
                <?php else: ?>
                    <button class="btn-pending" disabled>
                        ⏳ Menunggu Konfirmasi Admin
                    </button>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <div class="alert alert-info" id="infoMessage">📭 Tidak ada buku yang sedang dipinjam. <a href="daftar_buku.php">Pinjam buku sekarang</a></div>
        <?php endif; ?>

        <!-- Riwayat Peminjaman -->
        <h2 style="margin-bottom: 15px; margin-top: 30px;">📜 Riwayat Peminjaman</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Kode</th><th>Judul Buku</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Tgl Kembali</th><th>Denda</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($riwayat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                            <tr>
                                <td><?= $row['kode_peminjaman'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($row['judul']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) ?></td>
                                <td><?= $row['tgl_kembali'] ? date('d/m/Y', strtotime($row['tgl_kembali'])) : '-' ?></td>
                                <td>
                                    <?php if ($row['denda'] > 0): ?>
                                        <span style="color:#e74c3c;">Rp <?= number_format($row['denda'], 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        Rp 0
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-dikembalikan">✅ Dikembalikan</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-state">📭 Belum ada riwayat peminjaman</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="info-box">
            <strong>ℹ️ Informasi Peminjaman:</strong>
            <ul>
                <li>📚 Maksimal meminjam <strong>3 buku</strong> sekaligus</li>
                <li>📅 Lama peminjaman <strong>7 hari</strong> dari tanggal pinjam</li>
                <li>💰 Denda keterlambatan: <strong>Rp 2.000/hari</strong> per buku</li>
                <li>📦 Ajukan pengembalian, lalu admin akan mengkonfirmasi</li>
            </ul>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }
        function toggleDropdown(id) { const el = document.getElementById(id); if (el) el.classList.toggle('open'); }
        
        function ajukanKembali(id, judul) {
            if (confirm(`Ajukan pengembalian buku "${judul}"? Pengembalian akan dikonfirmasi oleh admin.`)) {
                fetch(`?ajukan_kembali=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        const alertDiv = document.getElementById('alertMessage');
                        if (data.success) {
                            alertDiv.innerHTML = `<div class="alert alert-success">✅ ${data.message}</div>`;
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            alertDiv.innerHTML = `<div class="alert alert-error">❌ ${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        document.getElementById('alertMessage').innerHTML = `<div class="alert alert-error">Terjadi kesalahan! Silakan coba lagi.</div>`;
                    });
            }
        }
        
        // Auto close alert setelah 5 detik
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => { if (alert) alert.style.display = 'none'; }, 5000);
            });
        }, 100);
    </script>
</body>
</html>