<?php
session_start();
require_once 'config.php';

// 1. Cek Autentikasi Admin (Disesuaikan dengan kelola_buku.php)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Ambil & Validasi ID Buku
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error'] = "ID Buku tidak valid!";
    header("Location: kelola_buku.php");
    exit();
}

// 3. Ambil Informasi Buku Terlebih Dahulu
$query_buku = mysqli_query($conn, "SELECT * FROM buku WHERE id = '$id'");
$buku = mysqli_fetch_assoc($query_buku);

if (!$buku) {
    $_SESSION['error'] = "Buku tidak ditemukan!";
    header("Location: kelola_buku.php");
    exit();
}

// 4. Eksekusi Penghapusan Jika Ada Konfirmasi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_hapus'])) {
    
    $id_clean = mysqli_real_escape_string($conn, $id);
    $query_delete = "DELETE FROM buku WHERE id = '$id_clean'";

    if (mysqli_query($conn, $query_delete)) {
        $_SESSION['success'] = "Buku <strong>" . htmlspecialchars($buku['judul']) . "</strong> berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus buku: " . mysqli_error($conn);
    }

    header("Location: kelola_buku.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Buku - Perpustakaan Digital</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Confirm Card */
        .card-confirm {
            background: #ffffff;
            border-radius: 20px;
            padding: 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: #fef2f2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 20px;
            border: 1px solid #fecaca;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .card-desc {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .book-detail {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-align: left;
            margin-bottom: 28px;
            font-size: 14px;
        }

        .book-detail p {
            margin-bottom: 6px;
            color: #334155;
        }

        .book-detail p:last-child {
            margin-bottom: 0;
        }

        .book-detail strong {
            color: #0f172a;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 12px;
        }

        .btn-delete {
            flex: 1;
            background: #ef4444;
            color: #ffffff;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-cancel {
            flex: 1;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>

    <div class="card-confirm">
        <div class="card-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h2 class="card-title">Konfirmasi Hapus Buku</h2>
        <p class="card-desc">Apakah Anda yakin ingin menghapus data buku ini secara permanen?</p>

        <div class="book-detail">
            <p><strong>Judul:</strong> <?= htmlspecialchars($buku['judul']); ?></p>
            <p><strong>Penulis:</strong> <?= htmlspecialchars($buku['penulis'] ?? '-'); ?></p>
            <p><strong>Kategori:</strong> <?= htmlspecialchars($buku['kategori'] ?? '-'); ?></p>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="konfirmasi_hapus" value="1">
            <div class="button-group">
                <a href="kelola_buku.php" class="btn-cancel">
                    <i class="fa-solid fa-xmark"></i> Batal
                </a>
                <button type="submit" class="btn-delete">
                    <i class="fa-solid fa-trash"></i> Ya, Hapus
                </button>
            </div>
        </form>
    </div>

</body>
</html>