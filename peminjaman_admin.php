<?php
require_once 'config.php';
redirectIfNotAdmin();

// Proses pengembalian buku
if (isset($_GET['kembalikan'])) {
    $id = (int)$_GET['kembalikan'];
    
    // Ambil data peminjaman
    $pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id='$id'"));
    
    if ($pinjam && $pinjam['status'] == 'dipinjam') {
        // Hitung denda jika terlambat
        $jatuh_tempo = new DateTime($pinjam['tanggal_jatuh_tempo']);
        $today = new DateTime();
        $denda = 0;
        
        if ($today > $jatuh_tempo) {
            $selisih = $today->diff($jatuh_tempo)->days;
            $denda = $selisih * 1000;
        }
        
        // Update peminjaman
        mysqli_query($conn, "UPDATE peminjaman SET 
            tanggal_kembali = CURDATE(), 
            status = 'dikembalikan',
            denda = '$denda'
            WHERE id='$id'");
        
        // Update stok buku
        mysqli_query($conn, "UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id='{$pinjam['buku_id']}'");
        
        // Catat log
        $admin_id = $_SESSION['user_id'];
        mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, deskripsi) 
            VALUES ('$admin_id', 'Pengembalian Buku', 'Mengembalikan buku ID: {$pinjam['buku_id']}')");
        
        echo "<script>alert('✅ Buku berhasil dikembalikan! Denda: Rp " . number_format($denda, 0, ',', '.') . "'); window.location='peminjaman_admin.php';</script>";
        exit();
    }
}

// Hapus peminjaman
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM peminjaman WHERE id='$id'");
    echo "<script>alert('Data peminjaman dihapus!'); window.location='peminjaman_admin.php';</script>";
    exit();
}

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';
$where = "";
if ($status_filter == 'dipinjam') {
    $where = "WHERE p.status = 'dipinjam'";
} elseif ($status_filter == 'dikembalikan') {
    $where = "WHERE p.status = 'dikembalikan'";
}

// Ambil semua peminjaman
$query = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap, u.username, u.nim, b.judul, b.kode_buku
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
    <title>Kelola Peminjaman - Perpustakaan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
        }
        .container {
            max-width: 1300px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .header {
            background: linear-gradient(135deg, #4A90E2 0%, #5C6BC0 100%);
            border-radius: 20px;
            padding: 25px 30px;
            color: white;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: #10B981;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6B7280;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover { background: #4B5563; }
        .filter-group {
            background: white;
            padding: 15px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .filter-group button {
            padding: 8px 20px;
            background: #4A90E2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .table-wrapper {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #F8FAFC;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #1E293B;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #F1F5F9;
        }
        tr:hover { background: #F8FAFC; }
        .status-dipinjam {
            background: #FEF3C7;
            color: #D97706;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-terlambat {
            background: #FEE2E2;
            color: #DC2626;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-dikembalikan {
            background: #D1FAE5;
            color: #059669;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .btn-kembali {
            background: #10B981;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 12px;
            display: inline-block;
        }
        .btn-hapus {
            background: #EF4444;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 12px;
            display: inline-block;
        }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #888;
        }
        @media (max-width: 768px) {
            th, td { padding: 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 Kelola Peminjaman Buku</h1>
                <p>Monitoring peminjaman dan pengembalian buku</p>
            </div>
            <a href="dashboard_admin.php" class="btn-secondary">← Kembali ke Dashboard</a>
        </div>

        <!-- Tombol Tambah Peminjaman -->
        <div class="btn-group">
            <a href="tambah_peminjaman.php" class="btn-primary">➕ Tambah Peminjaman Baru</a>
            <a href="peminjaman_admin.php" class="btn-secondary">🔄 Refresh</a>
        </div>

        <div class="filter-group">
            <label>Filter Status:</label>
            <form method="GET" style="display: flex; gap: 10px;">
                <select name="status">
                    <option value="semua" <?= $status_filter == 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="dipinjam" <?= $status_filter == 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                    <option value="dikembalikan" <?= $status_filter == 'dikembalikan' ? 'selected' : '' ?>>Sudah Dikembalikan</option>
                </select>
                <button type="submit">Filter</button>
                <?php if ($status_filter != 'semua'): ?>
                    <a href="peminjaman_admin.php" style="padding: 6px 12px; background: #E5E7EB; border-radius: 8px; text-decoration: none; color: #666;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kode Pinjam</th>
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
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['kode_peminjaman'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><br>
                                <small><?= $row['nim'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['judul']) ?><br><small><?= $row['kode_buku'] ?></small></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) ?></td>
                            <td><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-' ?></td>
                            <td>
                                <?php if ($row['status'] == 'dipinjam'): ?>
                                    <?php if ($is_terlambat): ?>
                                        <span class="status-terlambat">⚠️ Terlambat</span>
                                    <?php else: ?>
                                        <span class="status-dipinjam">📖 Dipinjam</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-dikembalikan">✅ Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                            <td>Rp <?= number_format($row['denda'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($row['status'] == 'dipinjam'): ?>
                                    <a href="?kembalikan=<?= $row['id'] ?>" class="btn-kembali" onclick="return confirm('Verifikasi pengembalian buku ini?')">📦 Kembalikan</a>
                                <?php else: ?>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus data peminjaman?')">🗑️ Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="empty-state">📭 Belum ada data peminjaman</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>