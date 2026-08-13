<?php
session_start();
require_once 'config.php';

// FIX 1: Pengecekan Session Admin yang seragam
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$kode_otomatis = '';

// Generate kode buku otomatis (Aki tambahkan pengecekan jika tabel masih kosong)
$query_max = mysqli_query($conn, "SELECT MAX(id) as max_id FROM buku");
$data_max = mysqli_fetch_assoc($query_max);
$next_id = ($data_max['max_id'] ?? 0) + 1;
$kode_otomatis = "BK" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_buku = mysqli_real_escape_string($conn, $_POST['kode_buku']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $penerbit = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $tahun_terbit = mysqli_real_escape_string($conn, $_POST['tahun_terbit']);
    $stok = (int)$_POST['stok'];

    if (empty($judul)) {
        $error = "Judul Buku wajib diisi!";
    } else {
        // Cek apakah kode buku sudah ada untuk menghindari duplikat
        $cek_kode = mysqli_query($conn, "SELECT id FROM buku WHERE kode_buku = '$kode_buku'");
        if (mysqli_num_rows($cek_kode) > 0) {
            $error = "Kode buku $kode_buku sudah terdaftar!";
        } else {

            // --- PROSES UPLOAD COVER (FILE ATAU KAMERA) ---
            $nama_cover = NULL;
            $upload_dir = 'uploads/';

            // Pastikan folder uploads ada
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // A. Jika Menggunakan Foto Kamera (Base64)
            if (!empty($_POST['cover_camera_data'])) {
                $img = $_POST['cover_camera_data'];
                $img = str_replace('data:image/png;base64,', '', $img);
                $img = str_replace(' ', '+', $img);
                $data = base64_decode($img);
                
                $file_name = 'cover_' . time() . '_' . rand(100, 999) . '.png';
                $file_path = $upload_dir . $file_name;

                if (file_put_contents($file_path, $data)) {
                    $nama_cover = $file_name;
                } else {
                    $error = "Gagal menyimpan foto dari kamera.";
                }
            } 
            // B. Jika Menggunakan File Upload
            elseif (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['cover_file']['tmp_name'];
                $file_orig_name = $_FILES['cover_file']['name'];
                $file_ext = strtolower(pathinfo($file_orig_name, PATHINFO_EXTENSION));
                
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed_ext)) {
                    $file_name = 'cover_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $nama_cover = $file_name;
                    } else {
                        $error = "Gagal mengunggah file gambar.";
                    }
                } else {
                    $error = "Format file gambar harus JPG, JPEG, PNG, atau WEBP!";
                }
            }

            // Jalankan simpan ke database jika tidak ada error upload
            if (empty($error)) {
                $cover_db = $nama_cover ? "'$nama_cover'" : "NULL";

                $query = "INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, stok, cover) 
                          VALUES ('$kode_buku', '$judul', '$penulis', '$penerbit', '$tahun_terbit', '$stok', $cover_db)";
                
                if (mysqli_query($conn, $query)) {
                    $success = "✅ Buku berhasil ditambahkan!";
                    // FIX 2: Gunakan JavaScript Redirect agar lebih sinkron dengan halaman lain
                    echo "<script>
                        setTimeout(function(){ 
                            window.location.href='kelola_buku.php'; 
                        }, 1500);
                    </script>";
                } else {
                    $error = "Gagal menambahkan: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku Baru</title>
    <style>
        /* Aki pertahankan style kamu karena sudah sangat bagus secara visual */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 550px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 25px;
            text-align: center;
            color: white;
        }
        .header .icon { font-size: 50px; }
        .header h2 { margin-top: 10px; }
        .body { padding: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        input[readonly] { background: #f0f0f0; cursor: not-allowed; }
        .btn-simpan {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: bold; cursor: pointer;
            transition: all 0.3s ease; margin-top: 10px;
        }
        .btn-simpan:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
        .btn-batal {
            width: 100%; padding: 12px; background: #6B7280;
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: bold; cursor: pointer;
            margin-top: 10px; text-align: center; display: inline-block; text-decoration: none;
        }
        .alert { padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee; color: #e74c3c; border-left: 4px solid #e74c3c; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .info-kode { background: #e8f0fe; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; color: #667eea; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 550px) { .form-row { grid-template-columns: 1fr; gap: 0; } }

        /* Styling Tambahan Khusus Upload & Kamera */
        .tab-options { display: flex; gap: 10px; margin-bottom: 10px; }
        .tab-btn {
            flex: 1; padding: 8px; border: 1px solid #667eea; background: white;
            color: #667eea; border-radius: 8px; cursor: pointer; font-weight: 600;
            font-size: 13px; transition: all 0.2s ease; text-align: center;
        }
        .tab-btn.active { background: #667eea; color: white; }
        .upload-section { display: none; margin-top: 8px; }
        .upload-section.active { display: block; }
        
        .camera-box {
            position: relative; width: 100%; height: 200px; background: #222;
            border-radius: 10px; overflow: hidden; display: flex;
            align-items: center; justify-content: center;
        }
        .camera-box video, .camera-box img { width: 100%; height: 100%; object-fit: cover; }
        .btn-cam {
            margin-top: 8px; width: 100%; padding: 8px; background: #3B82F6;
            color: white; border: none; border-radius: 8px; font-weight: 600;
            cursor: pointer; transition: 0.2s;
        }
        .btn-cam:hover { background: #2563EB; }
        .preview-file { width: 100px; height: 130px; object-fit: cover; border-radius: 8px; margin-top: 10px; display: none; border: 2px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">📖</div>
            <h2>Tambah Buku Baru</h2>
        </div>

        <div class="body">
            <?php if($error): ?>
                <div class="alert alert-error">⚠️ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="info-kode">
                🔑 Kode Buku otomatis: <strong><?php echo $kode_otomatis; ?></strong>
            </div>

            <!-- Tambahan enctype="multipart/form-data" untuk mendukun unggah file -->
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Kode Buku</label>
                    <input type="text" name="kode_buku" value="<?php echo $kode_otomatis; ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Judul Buku *</label>
                    <input type="text" name="judul" placeholder="Masukkan judul buku" required autofocus>
                </div>

                <div class="form-group">
                    <label>Penulis</label>
                    <input type="text" name="penulis" placeholder="Nama penulis">
                </div>

                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" placeholder="Nama penerbit">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun Terbit</label>
                        <input type="number" name="tahun_terbit" value="<?php echo date('Y'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" value="1" min="1">
                    </div>
                </div>

                <!-- FIELD UNTUK COVER BUKU (FILE ATAU KAMERA) -->
                <div class="form-group">
                    <label>Cover Buku (Opsional)</label>
                    <div class="tab-options">
                        <div class="tab-btn active" id="btn-tab-file" onclick="switchTab('file')">📁 Unggah File</div>
                        <div class="tab-btn" id="btn-tab-cam" onclick="switchTab('cam')">📷 Ambil Foto</div>
                    </div>

                    <!-- Pilihan 1: Upload File -->
                    <div id="sec-file" class="upload-section active">
                        <input type="file" name="cover_file" id="cover_file" accept="image/*" onchange="previewFileImage(this)">
                        <img id="file-preview" class="preview-file" alt="Preview Gambar">
                    </div>

                    <!-- Pilihan 2: Ambil Foto Kamera -->
                    <div id="sec-cam" class="upload-section">
                        <div class="camera-box">
                            <video id="webcam" autoplay playsinline></video>
                            <img id="cam-preview" style="display:none;" alt="Hasil Foto">
                        </div>
                        <button type="button" class="btn-cam" id="btn-capture" onclick="takeSnapshot()">📸 Ambil Gambar</button>
                        <button type="button" class="btn-cam" id="btn-retake" style="display:none; background:#E11D48;" onclick="resetCamera()">🔄 Foto Ulang</button>
                        <input type="hidden" name="cover_camera_data" id="cover_camera_data">
                    </div>
                </div>

                <button type="submit" class="btn-simpan">💾 Simpan Buku</button>
                <a href="kelola_buku.php" class="btn-batal">❌ Batal</a>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT UNTUK KAMERA DAN PREVIEW FILE -->
    <script>
        let videoStream = null;

        function switchTab(type) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.upload-section').forEach(sec => sec.classList.remove('active'));

            if(type === 'file') {
                document.getElementById('btn-tab-file').classList.add('active');
                document.getElementById('sec-file').classList.add('active');
                stopCamera();
            } else {
                document.getElementById('btn-tab-cam').classList.add('active');
                document.getElementById('sec-cam').classList.add('active');
                startCamera();
            }
        }

        function previewFileImage(input) {
            const preview = document.getElementById('file-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        async function startCamera() {
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ video: true });
                const video = document.getElementById('webcam');
                video.srcObject = videoStream;
            } catch (err) {
                alert("Akses kamera ditolak atau tidak ditemukan di perangkat ini.");
            }
        }

        function stopCamera() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
        }

        function takeSnapshot() {
            const video = document.getElementById('webcam');
            const preview = document.getElementById('cam-preview');
            const inputData = document.getElementById('cover_camera_data');
            
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth || 320;
            canvas.height = video.videoHeight || 240;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const dataUrl = canvas.toDataURL('image/png');
            inputData.value = dataUrl;
            
            preview.src = dataUrl;
            preview.style.display = 'block';
            video.style.display = 'none';
            
            document.getElementById('btn-capture').style.display = 'none';
            document.getElementById('btn-retake').style.display = 'block';
        }

        function resetCamera() {
            document.getElementById('cover_camera_data').value = '';
            document.getElementById('cam-preview').style.display = 'none';
            document.getElementById('webcam').style.display = 'block';
            
            document.getElementById('btn-capture').style.display = 'block';
            document.getElementById('btn-retake').style.display = 'none';
        }
    </script>
</body>
</html>