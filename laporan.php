<?php
session_start();
require_once 'config.php';

// Pastikan hanya Admin yang bisa akses
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data statistik untuk laporan ringkas
$total_buku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM buku"))['total'];
$total_member = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='member'"))['total'];
$total_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='dipinjam'"))['total'];
$total_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='kembali' OR status='dikembalikan'"))['total'];

// Ambil data detail transaksi
$query_laporan = "SELECT p.*, u.nama_lengkap, u.username, b.judul, b.kode_buku 
                  FROM peminjaman p 
                  JOIN users u ON p.user_id = u.id 
                  JOIN buku b ON p.buku_id = b.id 
                  ORDER BY p.tanggal_pinjam DESC";
$laporan = mysqli_query($conn, $query_laporan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perpustakaan - Perpustakaan Digital</title>
    <!-- Google Fonts: Plus Jakarta Sans & Times-like Font for Print -->
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

        .btn-action {
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
            cursor: pointer;
        }

        .btn-action:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        .btn-primary-action {
            background: #10b981;
            color: #ffffff;
            border: none;
        }

        .btn-primary-action:hover {
            background: #059669;
            color: #ffffff;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-icon.buku { background: #e0f2fe; color: #0284c7; }
        .stat-icon.member { background: #f3e8ff; color: #9333ea; }
        .stat-icon.pinjam { background: #fef3c7; color: #d97706; }
        .stat-icon.kembali { background: #d1fae5; color: #059669; }

        .stat-info p {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .stat-info h3 {
            font-size: 22px;
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

        .card-header-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.3px;
        }

        .badge-dipinjam {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-kembali {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px !important;
            color: #64748b;
        }

        /* Elemen Khusus Cetak (Sembunyi di Layar Web) */
        .print-header, .print-footer {
            display: none;
        }

        /* ========================================== */
        /* PRINT SPECIFIC STYLING (SURAT/LAPORAN RESMI) */
        /* ========================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Times New Roman', Times, serif;
                font-size: 11pt;
            }

            .sidebar, .top-banner, .stats-grid, .card-header-title {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Kop Surat Resmi dengan Logo Kiri */
            .print-header {
                display: flex !important;
                align-items: center;
                border-bottom: 3px double #000000;
                padding-bottom: 12px;
                margin-bottom: 20px;
                position: relative;
            }

            .print-header .logo-sekolah {
                width: 75px;
                height: auto;
                object-fit: contain;
            }

            .print-header .kop-content {
                flex: 1;
                text-align: center;
                margin-left: -75px; /* Menetralkan posisi teks agar tetap presisi di tengah halaman */
            }

            .print-header h2 {
                font-size: 15pt;
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 4px;
                letter-spacing: 0.5px;
            }

            .print-header h3 {
                font-size: 12pt;
                font-weight: bold;
                margin-bottom: 6px;
            }

            .print-header p {
                font-size: 8.5pt;
                margin-bottom: 2px;
                color: #000000;
            }

            .report-title {
                text-align: center;
                margin-bottom: 20px;
            }

            .report-title h4 {
                font-size: 12pt;
                text-transform: uppercase;
                text-decoration: underline;
                margin-bottom: 4px;
            }

            .report-title p {
                font-size: 10pt;
                font-style: italic;
            }

            /* Tabel Laporan Formal */
            .data-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 10px;
            }

            .data-table th, .data-table td {
                border: 1px solid #000000 !important;
                padding: 6px 8px !important;
                font-size: 10pt !important;
                color: #000000 !important;
            }

            .data-table th {
                background-color: #f2f2f2 !important;
                text-align: center !important;
                font-weight: bold !important;
            }

            .data-table td span {
                color: #000000 !important;
            }

            .badge {
                border: none !important;
                background: transparent !important;
                color: #000000 !important;
                padding: 0 !important;
                font-weight: normal !important;
            }

            .badge i {
                display: none !important;
            }

            /* Seksi Pengesahan / Tanda Tangan */
            .print-footer {
                display: flex !important;
                justify-content: flex-end;
                margin-top: 40px;
                page-break-inside: avoid;
            }

            .signature-box {
                text-align: center;
                width: 250px;
            }

            .signature-box p {
                font-size: 10pt;
                margin-bottom: 65px;
            }

            .signature-box strong {
                font-size: 10pt;
                text-decoration: underline;
                display: block;
            }
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
            <li>
                <a href="kelola_denda.php">
                    <i class="fa-solid fa-wallet"></i> Kelola Denda
                </a>
            </li>
            <li class="active">
                <a href="laporan.php">
                    <i class="fa-solid fa-file-lines"></i> Laporan
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Kop Surat Resmi (Hanya tampil saat dicetak) -->
        <div class="print-header">
            <!-- Logo Sekolah di Kiri Atas Kop -->
            <img src="logosma.png" alt="Logo Sekolah" class="logo-sekolah">
            
            <div class="kop-content">
                <h2>PERPUSTAKAAN SMA PASUNDAN RANCAEKEK</h2>
                <h3>LAPORAN REKAPITULASI TRANSAKSI PEMINJAMAN</h3>
                <p>Jl. Kenanga No.13, Rancaekek, Kabupaten Bandung - Jawa Barat | Email: perpustakaan@smapasundanrancaekek.sch.id</p>
            </div>
        </div>

        <!-- Top Header Banner -->
        <div class="top-banner">
            <div>
                <h1><i class="fa-solid fa-file-lines" style="color: #10b981;"></i> Laporan Perpustakaan</h1>
                <p>Ringkasan rekapitulasi data koleksi, anggota, dan seluruh riwayat transaksi perpustakaan.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn-action btn-primary-action">
                    <i class="fa-solid fa-print"></i> Cetak Laporan
                </button>
                <a href="dashboard_admin.php" class="btn-action">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Statistics Overview Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon buku"><i class="fa-solid fa-book"></i></div>
                <div class="stat-info">
                    <p>Total Koleksi Buku</p>
                    <h3><?= number_format($total_buku) ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon member"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <p>Total Member</p>
                    <h3><?= number_format($total_member) ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pinjam"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="stat-info">
                    <p>Peminjaman Aktif</p>
                    <h3><?= number_format($total_pinjam) ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon kembali"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info">
                    <p>Selesai / Dikembalikan</p>
                    <h3><?= number_format($total_kembali) ?></h3>
                </div>
            </div>
        </div>

        <!-- Main Data Table Card -->
        <div class="card">
            <div class="card-header-title">
                <i class="fa-solid fa-list-check" style="color: #10b981;"></i>
                Riwayat Transaksi Lengkap
            </div>

            <!-- Sub-judul dokumen cetak -->
            <div class="print-header" style="border: none; padding: 0; margin-bottom: 15px;">
                <p style="text-align: right; font-size: 9pt;">Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">No</th>
                            <th>Kode PMJ</th>
                            <th>Nama Member</th>
                            <th>Judul Buku</th>
                            <th style="text-align: center;">Tgl Pinjam</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($laporan) > 0): ?>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($laporan)): 
                                $is_dipinjam = strtolower($row['status']) == 'dipinjam';
                                $kode_display = isset($row['kode_peminjaman']) ? $row['kode_peminjaman'] : '#PMJ-' . $row['id'];
                            ?>
                            <tr>
                                <td style="text-align: center;"><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($kode_display) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><br>
                                    <span style="font-size: 11px; color: #64748b;">@<?= htmlspecialchars($row['username']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['judul']) ?></strong><br>
                                    <?php if (isset($row['kode_buku'])): ?>
                                        <span style="font-size: 11px; color: #64748b;">[<?= htmlspecialchars($row['kode_buku']) ?>]</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                <td style="text-align: center;">
                                    <?php if ($is_dipinjam): ?>
                                        <span class="badge badge-dipinjam">
                                            <i class="fa-solid fa-hourglass-half"></i> DIPINJAM
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-kembali">
                                            <i class="fa-solid fa-circle-check"></i> DIKEMBALIKAN
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 10px; color: #cbd5e1; display: block;"></i>
                                    Belum ada transaksi peminjaman recorded.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Seksi Tanda Tangan / Pengesahan (Hanya tampil saat dicetak) -->
            <div class="print-footer">
                <div class="signature-box">
                    <p>Sumedang, <?= date('d F Y') ?><br>Kepala Perpustakaan,</p>
                    <strong>( ________________________ )</strong>
                    <span>NIP. ........................................</span>
                </div>
            </div>

        </div>

    </div>

</body>
</html>