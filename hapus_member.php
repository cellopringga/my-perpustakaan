<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];

// Jangan hapus admin yang sedang login
if ($id == $_SESSION['user_id']) {
    header("Location: kelola_member.php?error=Tidak bisa hapus akun sendiri");
    exit();
}

// Hapus member
mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role = 'member'");

header("Location: kelola_member.php");
exit();
?>