<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    $_SESSION['error'] = "Harap login terlebih dahulu";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$buku_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($buku_id <= 0) {
    $_SESSION['error'] = "ID buku tidak valid!";
    header("Location: daftar_buku.php");
    exit();
}

$query_buku = "SELECT * FROM buku WHERE id = $buku_id";
$result_buku = mysqli_query($conn, $query_buku);
$buku = mysqli_fetch_assoc($result_buku);

if (!$buku) {
    $_SESSION['error'] = "Buku tidak ditemukan!";
    header("Location: daftar_buku.php");
    exit();
}

if ($buku['stok'] <= 0) {
    $_SESSION['error'] = "Stok buku habis!";
    header("Location: daftar_buku.php");
    exit();
}

$query_cek = "SELECT * FROM peminjaman WHERE user_id = $user_id AND buku_id = $buku_id AND status = 'dipinjam'";
$result_cek = mysqli_query($conn, $query_cek);
if (mysqli_num_rows($result_cek) > 0) {
    $_SESSION['error'] = "Anda sedang meminjam buku ini!";
    header("Location: daftar_buku.php");
    exit();
}

$query_jumlah = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = $user_id AND status = 'dipinjam'";
$result_jumlah = mysqli_query($conn, $query_jumlah);
$jumlah_dipinjam = mysqli_fetch_assoc($result_jumlah)['total'];

if ($jumlah_dipinjam >= 3) {
    $_SESSION['error'] = "Maksimal pinjam 3 buku!";
    header("Location: daftar_buku.php");
    exit();
}

$tanggal_pinjam = date('Y-m-d');
$tanggal_jatuh_tempo = date('Y-m-d', strtotime('+7 days'));

$query_insert = "INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_jatuh_tempo, status, denda) 
                 VALUES ($user_id, $buku_id, '$tanggal_pinjam', '$tanggal_jatuh_tempo', 'dipinjam', 0)";

if (mysqli_query($conn, $query_insert)) {
    $stok_baru = $buku['stok'] - 1;
    mysqli_query($conn, "UPDATE buku SET stok = $stok_baru WHERE id = $buku_id");
    $_SESSION['success'] = "Berhasil meminjam buku \"" . $buku['judul'] . "\"!";
} else {
    $_SESSION['error'] = "Gagal meminjam: " . mysqli_error($conn);
}

header("Location: daftar_buku.php");
exit();
?>