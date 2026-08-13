<?php
session_start();

// 1. Cek apakah user sudah login atau belum
if (!isset($_SESSION['role'])) {
    // Kalau belum login, tendang balik ke halaman login
    header("Location: login.php");
    exit();
}

// 2. Cek Role (Jabatan) user dan arahkan ke halaman masing-masing
if ($_SESSION['role'] == 'admin') {
    header("Location: dashboard_admin.php");
    exit();
} else if ($_SESSION['role'] == 'member') {
    header("Location: dashboard_member.php");
    exit();
} else {
    // Jika role tidak dikenal, logout paksa
    header("Location: logout.php");
    exit();
}
?>