<?php
session_start();
require_once 'config.php';

// Cek login member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    $_SESSION['error'] = "Harap login terlebih dahulu!";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$peminjaman_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($peminjaman_id <= 0) {
    $_SESSION['error'] = "ID peminjaman tidak valid!";
    header("Location: riwayat_peminjaman.php");
    exit();
}

// Ambil data peminjaman
$query = "SELECT p.*, b.judul, b.stok, b.id as buku_id 
          FROM peminjaman p 
          JOIN buku b ON p.buku_id = b.id 
          WHERE p.id = $peminjaman_id AND p.user_id = $user_id AND p.status = 'dipinjam'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Data peminjaman tidak ditemukan atau sudah dikembalikan!";
    header("Location: riwayat_peminjaman.php");
    exit();
}

$pinjam = mysqli_fetch_assoc($result);
$buku_id = $pinjam['buku_id'];

// Hitung denda jika terlambat
$tanggal_kembali = date('Y-m-d');
$jatuh_tempo = $pinjam['tanggal_jatuh_tempo'];
$denda = 0;
$hari_terlambat = 0;

if ($tanggal_kembali > $jatuh_tempo) {
    $selisih = strtotime($tanggal_kembali) - strtotime($jatuh_tempo);
    $hari_terlambat = ceil($selisih / (60 * 60 * 24));
    $denda = $hari_terlambat * 2000; // Rp 2000 per hari
}

// Cek apakah sudah pernah dikembalikan
$query_cek = "SELECT * FROM pengembalian WHERE peminjaman_id = $peminjaman_id";
$cek_result = mysqli_query($conn, $query_cek);

if (mysqli_num_rows($cek_result) > 0) {
    $_SESSION['error'] = "Buku ini sudah pernah dikembalikan!";
    header("Location: riwayat_peminjaman.php");
    exit();
}

// ========== PROSES PENGEMBALIAN ==========

// Insert ke tabel pengembalian
$query_insert = "INSERT INTO pengembalian (peminjaman_id, user_id, buku_id, tanggal_kembali, denda, status_pembayaran) 
                 VALUES ('$peminjaman_id', '$user_id', '$buku_id', '$tanggal_kembali', '$denda', 'belum_bayar')";

if (mysqli_query($conn, $query_insert)) {
    
    // Update status peminjaman menjadi dikembalikan
    mysqli_query($conn, "UPDATE peminjaman SET status = 'dikembalikan' WHERE id = $peminjaman_id");
    
    // Update stok buku (tambah 1)
    $stok_baru = $pinjam['stok'] + 1;
    mysqli_query($conn, "UPDATE buku SET stok = $stok_baru WHERE id = $buku_id");
    
    // Pesan sukses
    if ($denda > 0) {
        $_SESSION['success'] = "✅ Buku \"" . $pinjam['judul'] . "\" berhasil dikembalikan!<br>
                                ⚠️ Terlambat $hari_terlambat hari<br>
                                💰 Denda: Rp " . number_format($denda, 0, ',', '.');
    } else {
        $_SESSION['success'] = "✅ Buku \"" . $pinjam['judul'] . "\" berhasil dikembalikan. Terima kasih!";
    }
    
} else {
    $_SESSION['error'] = "Gagal mengembalikan buku: " . mysqli_error($conn);
}

header("Location: riwayat_peminjaman.php");
exit();
?>