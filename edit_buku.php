<?php
session_start();
require_once 'config.php';

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil ID buku dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = "ID buku tidak valid!";
    header("Location: kelola_buku.php");
    exit();
}

// Ambil data buku
$query = "SELECT * FROM buku WHERE id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Buku tidak ditemukan!";
    header("Location: kelola_buku.php");
    exit();
}

$buku = mysqli_fetch_assoc($result);

// Ambil data cover lama
$cover_lama = $buku['cover'] ?? $buku['gambar'] ?? $buku['foto'] ?? '';

// Proses update buku
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penerbit = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $stok = (int)$_POST['stok'];
    $stok_tersedia = (int)$_POST['stok_tersedia'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // Validasi
    $errors = [];
    if (empty($judul)) $errors[] = "Judul buku tidak boleh kosong";
    if ($stok < 0) $errors[] = "Stok tidak boleh negatif";
    if ($stok_tersedia < 0) $errors[] = "Stok tersedia tidak boleh negatif";
    
    // ========== PENANGANAN UPLOAD / KAMERA GAMBAR ==========
    $nama_file_cover = $cover_lama; // Secara default gunakan cover lama
    
    // 1. Jika ada foto dari Kamera (Base64)
    if (!empty($_POST['image_camera_data'])) {
        $img_data = $_POST['image_camera_data'];
        $img_data = str_replace('data:image/png;base64,', '', $img_data);
        $img_data = str_replace(' ', '+', $img_data);
        $data = base64_decode($img_data);
        
        $file_name = 'cover_' . time() . '_' . rand(100, 999) . '.png';
        $upload_path = 'uploads/' . $file_name;
        
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        if (file_put_contents($upload_path, $data)) {
            // Hapus file lama jika ada
            if (!empty($cover_lama) && file_exists('uploads/' . $cover_lama)) {
                unlink('uploads/' . $cover_lama);
            }
            $nama_file_cover = $file_name;
        } else {
            $errors[] = "Gagal menyimpan foto dari kamera";
        }
    }
    // 2. Jika ada Upload File Biasa
    elseif (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['cover']['tmp_name'];
        $file_name = $_FILES['cover']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = 'cover_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_file_name;
            
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Hapus file lama jika ada
                if (!empty($cover_lama) && file_exists('uploads/' . $cover_lama)) {
                    unlink('uploads/' . $cover_lama);
                }
                $nama_file_cover = $new_file_name;
            } else {
                $errors[] = "Gagal mengunggah gambar";
            }
        } else {
            $errors[] = "Format file tidak didukung! Gunakan JPG, PNG, atau WEBP.";
        }
    }
    
    if (empty($errors)) {
        // Cek nama kolom di tabel (cover, gambar, atau foto)
        // Default menggunakan kolom 'cover'
        $update = "UPDATE buku SET 
                    judul = '$judul',
                    penerbit = '$penerbit',
                    kategori = '$kategori',
                    stok = $stok,
                    stok_tersedia = $stok_tersedia,
                    deskripsi = '$deskripsi',
                    cover = '$nama_file_cover'
                  WHERE id = $id";
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['success'] = "✅ Buku berhasil diperbarui!";
            header("Location: kelola_buku.php");
            exit();
        } else {
            $error_msg = "Gagal memperbarui buku: " . mysqli_error($conn);
        }
    } else {
        $error_msg = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Admin Perpustakaan</title>
    <!-- FontAwesome Font untuk ikon kamera/upload -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
        }

        .card-header h1 {
            font-size: 24px;
        }

        .card-header p {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group input:disabled {
            background: #f5f5f5;
            color: #888;
            cursor: not-allowed;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #5a67d8;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .info-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        /* Styling Tambahan untuk Fitur Cover & Kamera */
        .cover-preview-box {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .current-cover-container {
            width: 130px;
            height: 170px;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            position: relative;
        }

        .current-cover-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-cam {
            background: #10b981;
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }

        .btn-cam:hover {
            background: #059669;
        }

        #cameraContainer {
            display: none;
            margin-top: 15px;
            background: #000;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }

        #webcam {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
        }

        .cam-controls {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>✏️ Edit Buku</h1>
                <p>Perbarui informasi buku di perpustakaan</p>
            </div>
            <div class="card-body">
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-error"><?= $error_msg ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- ID Buku (DISABLED) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>🆔 ID Buku</label>
                            <input type="text" value="<?= $buku['id'] ?>" disabled>
                            <small class="info-text">ID tidak dapat diubah</small>
                        </div>
                        <div class="form-group">
                            <label>📋 Kode Buku</label>
                            <input type="text" value="<?= htmlspecialchars($buku['kode_buku']) ?>" disabled>
                            <small class="info-text">Kode buku tidak dapat diubah</small>
                        </div>
                    </div>

                    <!-- Judul Buku -->
                    <div class="form-group">
                        <label>📖 Judul Buku *</label>
                        <input type="text" name="judul" required value="<?= htmlspecialchars($buku['judul']) ?>">
                    </div>

                    <!-- Gambar Sampul Buku / Kamera (BAGIAN BARU) -->
                    <div class="form-group">
                        <label>🖼️ Sampul Buku (Cover)</label>
                        <div class="cover-preview-box">
                            <div>
                                <div class="current-cover-container">
                                    <?php if (!empty($cover_lama) && file_exists('uploads/' . $cover_lama)): ?>
                                        <img id="coverPreview" src="uploads/<?= htmlspecialchars($cover_lama); ?>" alt="Cover Buku">
                                    <?php else: ?>
                                        <img id="coverPreview" src="" style="display:none;" alt="Cover Buku">
                                        <span id="noCoverText" style="color: #94a3b8; font-size: 12px; text-align: center;"><i class="fa-solid fa-image" style="font-size: 24px; margin-bottom: 5px;"><br></i>Belum Ada Cover</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <input type="file" name="cover" id="fileInput" accept="image/*" onchange="previewFile()">
                                <small class="info-text" style="display: block; margin-top: 5px;">Pilih file dari komputer (JPG, PNG, WEBP)</small>
                                
                                <button type="button" class="btn-cam" onclick="openCamera()">
                                    <i class="fa-solid fa-camera"></i> Ambil via Kamera
                                </button>
                                <input type="hidden" name="image_camera_data" id="cameraData">
                            </div>
                        </div>

                        <!-- Container Kamera Web -->
                        <div id="cameraContainer">
                            <video id="webcam" autoplay playsinline></video>
                            <canvas id="canvas" style="display:none;"></canvas>
                            <div class="cam-controls">
                                <button type="button" class="btn-submit" style="padding: 6px 15px; font-size: 13px;" onclick="takeSnapshot()">📸 Jepret Foto</button>
                                <button type="button" class="btn-cancel" style="padding: 6px 15px; font-size: 13px;" onclick="closeCamera()">Tutup Kamera</button>
                            </div>
                        </div>
                    </div>

                    <!-- Penulis (DISABLED) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>✍️ Penulis</label>
                            <input type="text" value="<?= htmlspecialchars($buku['penulis']) ?>" disabled>
                            <small class="info-text">Nama penulis tidak dapat diubah</small>
                        </div>
                        <div class="form-group">
                            <label>🏢 Penerbit</label>
                            <input type="text" name="penerbit" value="<?= htmlspecialchars($buku['penerbit'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Tahun Terbit (DISABLED) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>📅 Tahun Terbit</label>
                            <input type="number" value="<?= $buku['tahun_terbit'] ?? '' ?>" disabled>
                            <small class="info-text">Tahun terbit tidak dapat diubah</small>
                        </div>
                        <div class="form-group">
                            <label>🏷️ Kategori</label>
                            <select name="kategori">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Fiksi" <?= ($buku['kategori'] == 'Fiksi') ? 'selected' : '' ?>>Fiksi</option>
                                <option value="Non Fiksi" <?= ($buku['kategori'] == 'Non Fiksi') ? 'selected' : '' ?>>Non Fiksi</option>
                                <option value="Novel" <?= ($buku['kategori'] == 'Novel') ? 'selected' : '' ?>>Novel</option>
                                <option value="Komik" <?= ($buku['kategori'] == 'Komik') ? 'selected' : '' ?>>Komik</option>
                                <option value="Pendidikan" <?= ($buku['kategori'] == 'Pendidikan') ? 'selected' : '' ?>>Pendidikan</option>
                                <option value="Teknologi" <?= ($buku['kategori'] == 'Teknologi') ? 'selected' : '' ?>>Teknologi</option>
                                <option value="Agama" <?= ($buku['kategori'] == 'Agama') ? 'selected' : '' ?>>Agama</option>
                                <option value="Sejarah" <?= ($buku['kategori'] == 'Sejarah') ? 'selected' : '' ?>>Sejarah</option>
                            </select>
                        </div>
                    </div>

                    <!-- Stok -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>📚 Stok *</label>
                            <input type="number" name="stok" min="0" required value="<?= $buku['stok'] ?>">
                        </div>
                        <div class="form-group">
                            <label>📦 Stok Tersedia</label>
                            <input type="number" name="stok_tersedia" min="0" value="<?= $buku['stok_tersedia'] ?? $buku['stok'] ?>">
                            <small class="info-text">Jumlah buku yang bisa dipinjam</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>📝 Deskripsi</label>
                        <textarea name="deskripsi" rows="4" placeholder="Deskripsi singkat tentang buku..."><?= htmlspecialchars($buku['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
                        <a href="kelola_buku.php" class="btn-cancel">❌ Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let videoStream = null;

        // Preview File dari PC
        function previewFile() {
            const preview = document.getElementById('coverPreview');
            const noCoverText = document.getElementById('noCoverText');
            const file = document.getElementById('fileInput').files[0];
            const reader = new FileReader();

            document.getElementById('cameraData').value = ''; // Reset data kamera

            reader.onloadend = function () {
                preview.src = reader.result;
                preview.style.display = 'block';
                if(noCoverText) noCoverText.style.display = 'none';
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        // Buka Webcam
        async function openCamera() {
            const container = document.getElementById('cameraContainer');
            const video = document.getElementById('webcam');
            
            container.style.display = 'block';

            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = videoStream;
            } catch (err) {
                alert("Gagal mengakses kamera. Pastikan izin kamera telah diberikan.");
                container.style.display = 'none';
            }
        }

        // Jepret Foto dari Webcam
        function takeSnapshot() {
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const preview = document.getElementById('coverPreview');
            const noCoverText = document.getElementById('noCoverText');
            const cameraDataInput = document.getElementById('cameraData');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataUrl = canvas.toDataURL('image/png');
            preview.src = dataUrl;
            preview.style.display = 'block';
            if(noCoverText) noCoverText.style.display = 'none';

            cameraDataInput.value = dataUrl; 
            document.getElementById('fileInput').value = ''; // Reset file input

            closeCamera();
        }

        // Tutup Webcam
        function closeCamera() {
            const container = document.getElementById('cameraContainer');
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
            container.style.display = 'none';
        }
    </script>
</body>
</html>