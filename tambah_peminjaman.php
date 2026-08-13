<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai admin
// Catatan: Pastikan di login.php kamu pakai $_SESSION['admin_id'] atau ganti jadi $_SESSION['user_id']
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Ambil daftar member
$members = mysqli_query($conn, "SELECT id, nama_lengkap, nim FROM users WHERE role='member' ORDER BY nama_lengkap");

// Ambil daftar buku yang tersedia (Aki sesuaikan 'stok' sesuai gambar pemin.jpg kamu)
$books = mysqli_query($conn, "SELECT id, judul, kode_buku, stok FROM buku WHERE stok > 0 ORDER BY judul");

// Proses tambah peminjaman
if (isset($_POST['simpan'])) {
    $user_id = (int)$_POST['user_id'];
    $buku_id = (int)$_POST['buku_id'];
    $tanggal_pinjam = mysqli_real_escape_string($conn, $_POST['tanggal_pinjam']);
    $jatuh_tempo = mysqli_real_escape_string($conn, $_POST['jatuh_tempo']);
    $admin_id = $_SESSION['user_id']; // Mengambil ID admin dari session login
    
    if (empty($user_id) || empty($buku_id)) {
        $error = "Pilih member dan buku terlebih dahulu!";
    } elseif (strtotime($jatuh_tempo) < strtotime($tanggal_pinjam)) {
        $error = "Tanggal jatuh tempo tidak boleh kurang dari tanggal pinjam!";
    } else {
        // Generate kode peminjaman otomatis
        $kode_pinjam = "PJM" . date('Ymd') . rand(100, 999);
        
        // Query INSERT (Sudah disesuaikan dengan adanya kolom admin_id yang kita tambah tadi)
        $query = "INSERT INTO peminjaman (kode_peminjaman, user_id, buku_id, admin_id, tanggal_pinjam, tanggal_jatuh_tempo, status) 
                  VALUES ('$kode_pinjam', '$user_id', '$buku_id', '$admin_id', '$tanggal_pinjam', '$jatuh_tempo', 'dipinjam')";
        
        if (mysqli_query($conn, $query)) {
            // Kurangi stok buku (Aki pakai kolom 'stok' sesuai database kamu)
            mysqli_query($conn, "UPDATE buku SET stok = stok - 1 WHERE id='$buku_id'");
            
            $success = "✅ Peminjaman berhasil! Kode: $kode_pinjam";
            // FIX: Mengarah ke kelola_peminjaman.php agar tidak Error 404
            echo "<script>
                setTimeout(function(){ 
                    window.location.href='kelola_peminjaman.php'; 
                }, 1500);
            </script>";
        } else {
            $error = "Gagal simpan: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Peminjaman - Perpustakaan</title>
    <style>
        /* Style tetap sama seperti sebelumnya karena sudah bagus UI/UX-nya */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #764ba2; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; }
        .header { background: #667eea; color: white; padding: 20px; text-align: center; }
        .body { padding: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-simpan { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .alert-error { background: #fee; color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>➕ Tambah Peminjaman</h2>
        </div>
        <div class="body">
            <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Member</label>
                    <select name="user_id" required>
                        <option value="">-- Pilih Member --</option>
                        <?php while ($m = mysqli_fetch_assoc($members)): ?>
                            <option value="<?= $m['id'] ?>"><?= $m['nama_lengkap'] ?> (<?= $m['nim'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Buku</label>
                    <select name="buku_id" required>
                        <option value="">-- Pilih Buku --</option>
                        <?php while ($b = mysqli_fetch_assoc($books)): ?>
                            <option value="<?= $b['id'] ?>"><?= $b['judul'] ?> (Stok: <?= $b['stok'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tgl Pinjam</label>
                    <input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Tgl Jatuh Tempo (7 Hari)</label>
                    <input type="date" name="jatuh_tempo" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
                <button type="submit" name="simpan" class="btn-simpan">💾 Simpan Peminjaman</button>
                <a href="kelola_peminjaman.php" style="display:block; text-align:center; margin-top:10px; color:#666;">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>