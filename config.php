<?php
// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'perpustakaan_sistem';

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Fungsi cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi cek admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Fungsi redirect jika belum login
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Fungsi redirect jika bukan admin
function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Fungsi logout
function logout() {
    global $conn;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, deskripsi, ip_address) 
                             VALUES ($user_id, 'Logout', 'User logout dari sistem', '$ip')");
    }
    
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
?>