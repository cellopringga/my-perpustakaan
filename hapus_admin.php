<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = (int)$_GET['id'];

// Cek apakah admin mencoba menghapus dirinya sendiri
if ($id == $_SESSION['admin_id']) {
    echo "<script>
        alert('❌ Anda tidak dapat menghapus akun sendiri!');
        window.location.href = 'kelola_admin.php';
    </script>";
    exit();
}

// Ambil data admin yang akan dihapus
$query = mysqli_query($conn, "SELECT * FROM admin WHERE id = '$id'");
$admin = mysqli_fetch_assoc($query);

if (!$admin) {
    echo "<script>
        alert('Data admin tidak ditemukan!');
        window.location.href = 'kelola_admin.php';
    </script>";
    exit();
}

// Cek apakah ini admin terakhir
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin");
$total_admin = mysqli_fetch_assoc($count_query)['total'];

if ($total_admin <= 1) {
    echo "<script>
        alert('❌ Tidak dapat menghapus admin terakhir! Minimal harus ada 1 admin.');
        window.location.href = 'kelola_admin.php';
    </script>";
    exit();
}

// Proses hapus
$delete = mysqli_query($conn, "DELETE FROM admin WHERE id = '$id'");

if ($delete) {
    // Catat log aktivitas
    mysqli_query($conn, "INSERT INTO log_aktivitas (admin_id, aktivitas, deskripsi) 
        VALUES ('{$_SESSION['admin_id']}', 'Hapus Admin', 'Menghapus admin: {$admin['nama_lengkap']}')");
    
    echo "<script>
        alert('✅ Admin berhasil dihapus!');
        window.location.href = 'kelola_admin.php';
    </script>";
} else {
    echo "<script>
        alert('❌ Gagal menghapus admin: " . mysqli_error($conn) . "');
        window.location.href = 'kelola_admin.php';
    </script>";
}
?>