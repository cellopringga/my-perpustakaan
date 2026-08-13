-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2026 at 09:11 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_sistem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `alamat` text,
  `jabatan` varchar(50) DEFAULT 'Petugas',
  `foto` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nik`, `nama_lengkap`, `email`, `no_telepon`, `alamat`, `jabatan`, `foto`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'petugas', '11111', 'ADMIN210669', 'akun tes', 'ardiachacha@gmail.com', '085745770970', '', 'Petugas', NULL, 1, '2026-04-29 13:04:21', '2026-07-31 07:09:43');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `kode_buku` varchar(20) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `penulis` varchar(100) NOT NULL,
  `penerbit` varchar(100) DEFAULT NULL,
  `tahun_terbit` int(11) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `deskripsi` text,
  `jumlah_halaman` int(11) DEFAULT NULL,
  `lokasi_rak` varchar(20) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT '0',
  `cover` varchar(255) DEFAULT NULL,
  `stok_tersedia` int(11) NOT NULL DEFAULT '0',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `kode_buku`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `isbn`, `kategori`, `deskripsi`, `jumlah_halaman`, `lokasi_rak`, `stok`, `cover`, `stok_tersedia`, `gambar`, `created_at`, `updated_at`) VALUES
(17, 'BK001', 'Ekonomi XI', 'Yeni Fitriani dan Aisyah Nurjanah', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekn', 2021, NULL, '', 'Untuk Kelas XI', NULL, NULL, 178, 'cover_1785307406_930.jpeg', 0, NULL, '2026-07-29 00:59:20', '2026-07-29 06:43:26'),
(18, 'BK018', 'Pendidikan Pancasila XI', 'Sri Cahyati, Siti Nurjanah, dan Ali Usman', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785307569_779.jpeg', 0, NULL, '2026-07-29 01:02:00', '2026-07-29 06:46:09'),
(20, 'BK019', 'Pendidikan Jasmani, Olahraga, dan Kesehatan XI', 'Sri Wahyuni dan Sutarman ', 'PT Tiga Serangkai Pustaka Mandiri', 2023, NULL, '', '', NULL, NULL, 180, 'cover_1785308930_185.jpeg', 0, NULL, '2026-07-29 01:14:05', '2026-07-29 07:08:50'),
(21, 'BK021', 'Matematika XI', 'Dicky Susanto, Savitri K. Sihombing, Marianna Magdalena Radjawane, Yulian Candra, dan Daniel Sinambe', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekn', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308916_556.jpeg', 0, NULL, '2026-07-29 01:17:25', '2026-07-29 07:08:36'),
(22, 'BK022', 'Pendidikan Agama Islam dan Budi Pekerti XI', 'Abd. Rahman, dan Hery Nugroho', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308537_577.jpeg', 0, NULL, '2026-07-29 01:19:57', '2026-07-29 07:02:17'),
(23, 'BK023', 'Bahasa Inggris XI', 'Puji Astuti, Aria Septi Anggaira, Atti Herawati, Yeyet Nurhayati, Dadan, dan Dayang Suriani', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785308898_467.jpeg', 0, NULL, '2026-07-29 01:21:47', '2026-07-29 07:08:18'),
(24, 'BK024', 'Matematika Tingkat Lanjut XI', 'Al Azhary Masta, Yosep Dwi Kristanto, Elyda Yulfiana, dan Muhammad Taqiyuddin', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308874_823.jpeg', 0, NULL, '2026-07-29 01:23:43', '2026-07-29 07:07:54'),
(25, 'BK025', 'Fisika XI', 'Marianna Magdalena Radjawane, Alvius Tinambunan, dan Suntar Jono', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785308815_824.jpeg', 0, NULL, '2026-07-29 01:25:00', '2026-07-29 07:06:55'),
(26, 'BK026', 'Kimia XI', 'Munasprianto Ramli, Nanda Saridewi, Tiktik Mustika Budhi, dan Aang Suhendar ', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785308791_235.jpeg', 0, NULL, '2026-07-29 01:26:23', '2026-07-29 07:06:31'),
(27, 'BK027', 'Biologi XI', 'Rini Solihat, Eris Rustandi, Wandi Hepiandi, dan Zamzam Nursani', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785308760_761.jpeg', 0, NULL, '2026-07-29 01:27:36', '2026-07-29 07:06:00'),
(28, 'BK028', 'Sosiologi XI', 'Joan Hesti Gita Purwasih, dan Seli Septiana Pratiwi', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308622_298.jpeg', 0, NULL, '2026-07-29 01:28:46', '2026-07-29 07:03:42'),
(29, 'BK029', 'Berbahasa dan Bersastra Indonesia XI', 'Henry Marwati, dan K. Waskitaningtyas', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308726_605.jpeg', 0, NULL, '2026-07-29 01:30:02', '2026-07-29 07:05:26'),
(30, 'BK030', 'Prakarya dan Kewirausahaan Kerajinan XI', 'Setyawan, dan Nur Laili Munazalah', 'PT Tiga Serangkai Pustaka Mandiri', 2025, NULL, '', '', NULL, NULL, 178, 'cover_1785308701_133.jpeg', 0, NULL, '2026-07-29 01:31:42', '2026-07-29 07:05:01'),
(31, 'BK031', 'Informatika XI', 'Auzi Asfarian, Paulina H. Prima Rosa, Irya Wisnubhadra, Mustofa, dan Dean Apriana Ramadhan', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308677_122.jpeg', 0, NULL, '2026-07-29 01:33:14', '2026-07-29 07:04:37'),
(32, 'BK032', 'Sejarah XI', 'Martina Safitry, Indah Wahyu Puji Utami, dan Zein Ilyas', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785308649_383.jpeg', 0, NULL, '2026-07-29 01:34:12', '2026-07-29 07:04:09'),
(33, 'BK033', 'Matematika XII', 'Mohammad Tohir, Ahmad Choirul, dan Ibnu Taufiq', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379543_961.jpeg', 0, NULL, '2026-07-29 02:12:14', '2026-07-30 02:45:43'),
(34, 'BK034', 'Berbahasa dab Bersastra Indonesia XII', 'Bambang Trimansyah ', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379563_246.jpeg', 0, NULL, '2026-07-29 02:13:36', '2026-07-30 02:46:03'),
(35, 'BK035', 'Pendidikan Pancasila XII', 'Ida Rohayani, Hatim Gazali, dan Dwi Astuti Setiawan', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379584_507.jpeg', 0, NULL, '2026-07-29 02:14:45', '2026-07-30 02:46:24'),
(36, 'BK036', 'Pendidikan Agama Islam dan Budi Pekerti XII', 'Rohmat Chozirin , dan Untoro', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379599_789.jpeg', 0, NULL, '2026-07-29 02:15:38', '2026-07-30 02:46:39'),
(37, 'BK037', 'Biologi', 'Shilviani Dewi, Amalia Shari, Rani Elisa Purba, dan Remigus Gunawan Susilowarno', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 02:17:11', '2026-07-29 02:17:11'),
(38, 'BK038', 'Sosiologi XII', 'Joan Hesti Gita Purwasih, dan Seli Septiana Pratiwi', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379218_586.jpeg', 0, NULL, '2026-07-29 02:18:09', '2026-07-30 02:40:18'),
(39, 'BK039', 'Fisika XII', 'Lia Laela Sarah, dan Irma Rahma Suwarma', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379346_203.jpeg', 0, NULL, '2026-07-29 02:18:59', '2026-07-30 02:42:26'),
(40, 'BK040', 'Seni Musik XII', 'Melatu Budi Cahyanto', 'PT Tiga Serangkai Pustaka Mandiri', 2025, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 02:19:57', '2026-07-29 02:19:57'),
(41, 'BK041', 'Ekonomi XII', 'Aisyah Nurjanah, dan Yeni Fitriani ', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379372_351.jpeg', 0, NULL, '2026-07-29 02:20:50', '2026-07-30 02:42:52'),
(42, 'BK042', 'Cahara Basa XII', 'Ari Andriansyah, dan Taufik Faturohman', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2021, NULL, '', '', NULL, NULL, 178, 'cover_1785379404_840.jpeg', 0, NULL, '2026-07-29 02:22:59', '2026-07-30 02:43:24'),
(43, 'BK043', 'Pembelajaran Interaktif Sejarah Tingkat Lanjut XII', 'Herimanto, dan Eko Targiyatmi', 'PT Tiga Serangkai Pustaka Mandiri', 2025, NULL, '', '', NULL, NULL, 178, 'cover_1785379426_613.jpeg', 0, NULL, '2026-07-29 02:23:59', '2026-07-30 02:43:46'),
(44, 'BK044', 'Geografi XII', 'Budi Handoyo', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379475_115.jpeg', 0, NULL, '2026-07-29 02:24:40', '2026-07-30 02:44:35'),
(45, 'BK045', 'Pendidikan Jasmani, Olahraga, dan Kesehatan XII', 'Sri Wahyuni, dan Sutarmin', 'PT Tiga Serangkai Pustaka Mandiri', 2025, NULL, '', '', NULL, NULL, 178, 'cover_1785379499_606.jpeg', 0, NULL, '2026-07-29 02:25:48', '2026-07-30 02:44:59'),
(48, 'BK047', 'Matematika Tingkat Lanjut XII', 'Wikan Budi Utami, Sri Adi Widodo, dan Fitria, Sulistyowati', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2022, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 03:32:53', '2026-07-29 03:32:53'),
(52, 'BK049', 'Bahasa Inggris XII', 'Susanti Retno Hardini, Achdi Merdianto, Marjenny, Rani Nurhayati, Isry Laila Syathroh, dan Dadan', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi ', 2022, NULL, '', '', NULL, NULL, 178, 'cover_1785379522_778.jpeg', 0, NULL, '2026-07-29 04:42:05', '2026-07-30 02:45:22'),
(53, 'BK053', 'Informatika X ', 'Mushthofa, Wahyono, Auzi Asfarian, Dean Apriana Ramadhan, Hanson Prihantoro Putro, Irya Wisnubhadara', 'Badan Penelitian dan Pengembangan dan Pembukuan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:25:56', '2026-07-29 06:25:56'),
(54, 'BK054', 'Basa Sunda Urang X', 'Darpan, S.Pd., M.Pd', 'CV GEGER SUNTEN', 2017, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:28:16', '2026-07-29 06:28:16'),
(55, 'BK055', 'Seni Musik X ', 'Malatu Budi Cahyanto', 'PT Tiga Serangkai Pustaka Mandiri', 2022, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:29:18', '2026-07-29 06:29:18'),
(56, 'BK056', 'Pendidikan Jasmani, Olahraga, dan Kesehatan X ', 'Sri Wahyuni dan Sutarman ', 'PT Tiga Serangkai Pustaka Mandiri', 2022, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:30:22', '2026-07-29 06:30:22'),
(57, 'BK057', 'Pendidikan Pancasila dan Kewarganegaraan X', 'Abdul Waidl, Ali Usman, Ahmad Asroni, Hatim Gazali, dan Tedi Khholiluddin', 'Badan Penelitian dan Pengembangan dan Pembukuan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:31:57', '2026-07-29 06:31:57'),
(58, 'BK058', 'Pendidikan Agama Islam dan Budi Pekerti X', 'Ahmad Taufik, Nurwastuti Setyowati', 'Badan Penelitian dan Pengembangan dan Pembukuan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:32:58', '2026-07-29 06:32:58'),
(59, 'BK059', 'Ilmu Pengetahuan Sosial X', 'Sari Oktafiana, Efvinggo Fasya Jaya, M. Nursaban, Supardi, dan Mohammad Rizky Satria', 'Badan Penelitian dan Pengembangan dan Pembukuan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:34:39', '2026-07-29 06:34:39'),
(60, 'BK060', 'Berbahasa dan Bersastra Indonesia X', 'Fadillah Tri Aulia, dan Sefi Indra Gumilar', 'Badan Penelitian dan Pengembangan dan Pembukuan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:35:55', '2026-07-29 06:35:55'),
(61, 'BK061', 'Ilmu Pengetahuan Alam X', 'Ayuk Ratna Puspaningsih, Elizabeth Tjahjadarmawan, dan Niken Resminingpuri Krisdianti', 'Badan Penelitian dan Pengembangan dan Pembukuan Kementerian Pendidikan, Kebudayaan, Riset, dan Tekno', 2021, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:37:25', '2026-07-29 06:37:25'),
(62, 'BK062', 'Bahasa Inggris X', 'Budi Hermawan, Dwi Haryanti, dan Nining Suryaningsih', 'Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerin Pendidikan, Kebudayaan, Riset, dan Tekno', 2022, NULL, NULL, NULL, NULL, NULL, 178, NULL, 0, NULL, '2026-07-29 06:38:30', '2026-07-31 07:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) DEFAULT NULL,
  `deskripsi` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aktivitas`, `deskripsi`, `ip_address`, `created_at`) VALUES
(1, NULL, 'Sistem Mulai', 'Database diinisialisasi', 'localhost', '2026-04-25 14:57:43'),
(2, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-25 15:20:32'),
(3, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:02:38'),
(4, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:06:35'),
(5, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:07:30'),
(6, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:08:06'),
(7, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:11:09'),
(8, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:15:56'),
(9, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:22:32'),
(10, NULL, 'Login', 'User login ke sistem', '::1', '2026-04-26 10:25:32'),
(11, NULL, 'Tambah Peminjaman', 'Meminjamkan buku Artificial Intelligence kepada Dilan', NULL, '2026-04-27 05:07:42'),
(12, NULL, 'Pengembalian Buku', 'Mengembalikan buku ID: 5', NULL, '2026-04-27 05:08:37'),
(13, NULL, 'Tambah Peminjaman', 'Meminjamkan buku Artificial Intelligence kepada Siti Aminah', NULL, '2026-04-27 05:09:45'),
(14, NULL, 'Tambah Member', 'Menambahkan member baru: lion pratama (242302025)', NULL, '2026-04-27 05:17:39'),
(15, NULL, 'Tambah Peminjaman', 'Meminjamkan buku Database Sistem kepada lion pratama', NULL, '2026-04-27 05:18:31'),
(16, NULL, 'Pengembalian Buku', 'Mengembalikan buku ID: 2', NULL, '2026-04-27 05:48:25'),
(17, NULL, 'Pengembalian Buku', 'Mengembalikan buku ID: 5', NULL, '2026-04-27 05:48:45');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_denda`
--

CREATE TABLE `pembayaran_denda` (
  `id` int(11) NOT NULL,
  `peminjaman_id` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `metode_pembayaran` enum('tunai','transfer','qris') DEFAULT 'tunai',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('pending','lunas') DEFAULT 'pending',
  `tanggal_bayar` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `kode_peminjaman` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan','terlambat','hilang') DEFAULT 'dipinjam',
  `denda` decimal(10,2) DEFAULT '0.00',
  `status_pembayaran` enum('belum_bayar','lunas') DEFAULT 'belum_bayar',
  `catatan` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(11) NOT NULL,
  `peminjaman_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `denda` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `status_pembayaran` enum('belum_bayar','lunas') NOT NULL DEFAULT 'belum_bayar',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `peminjaman_id`, `user_id`, `buku_id`, `tanggal_kembali`, `denda`, `status`, `admin_id`, `status_pembayaran`, `created_at`) VALUES
(1, 31, 9, 10, '2026-05-11', '0.00', 'pending', NULL, 'belum_bayar', '2026-05-11 03:24:26'),
(2, 32, 9, 10, '2026-05-11', '0.00', 'pending', NULL, 'belum_bayar', '2026-05-11 03:25:44'),
(3, 33, 9, 3, '2026-05-11', '0.00', 'pending', NULL, 'belum_bayar', '2026-05-11 04:00:35'),
(4, 36, 3, 16, '2026-05-13', '0.00', 'pending', NULL, 'belum_bayar', '2026-05-13 13:46:13'),
(5, 39, 9, 13, '2026-07-23', '0.00', 'pending', NULL, 'belum_bayar', '2026-07-23 08:26:38'),
(6, 38, 3, 15, '2026-07-23', '128000.00', 'pending', NULL, 'belum_bayar', '2026-07-23 08:28:08'),
(7, 41, 11, 16, '2026-07-28', '0.00', 'pending', NULL, 'belum_bayar', '2026-07-28 06:13:38'),
(8, 40, 12, 15, '2026-07-29', '0.00', 'pending', NULL, 'belum_bayar', '2026-07-29 00:29:29'),
(9, 1, 12, 62, '2026-07-31', '0.00', 'pending', NULL, 'belum_bayar', '2026-07-31 07:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `nim` varchar(20) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `alamat` text,
  `foto` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_buku` (`kode_buku`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pembayaran_denda`
--
ALTER TABLE `pembayaran_denda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peminjaman_id` (`peminjaman_id`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peminjaman_id` (`peminjaman_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pembayaran_denda`
--
ALTER TABLE `pembayaran_denda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pembayaran_denda`
--
ALTER TABLE `pembayaran_denda`
  ADD CONSTRAINT `pembayaran_denda_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
