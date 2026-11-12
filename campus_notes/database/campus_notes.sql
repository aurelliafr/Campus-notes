-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2026 at 12:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campus_notes`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_kuis`
--

CREATE TABLE `ai_kuis` (
  `id_kuis` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `data_soal` longtext NOT NULL COMMENT 'JSON array soal pilihan ganda',
  `jumlah_soal` int(11) NOT NULL DEFAULT 0,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_ringkasan`
--

CREATE TABLE `ai_ringkasan` (
  `id_ringkasan` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `isi_ringkasan` text NOT NULL,
  `poin_penting` text DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catatan`
--

CREATE TABLE `catatan` (
  `id_catatan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `mata_kuliah` varchar(100) NOT NULL,
  `tahun_kuliah` year(4) NOT NULL,
  `nama_file_asli` varchar(255) NOT NULL,
  `nama_file_enkripsi` varchar(255) NOT NULL,
  `ekstensi` varchar(10) NOT NULL DEFAULT 'pdf',
  `tipe_file` enum('pdf','gambar','video','doc') NOT NULL DEFAULT 'pdf',
  `ukuran_file` bigint(20) NOT NULL DEFAULT 0,
  `jumlah_download` int(11) DEFAULT 0,
  `jumlah_upvote` int(11) DEFAULT 0,
  `jumlah_report` int(11) DEFAULT 0,
  `status` enum('aktif','ditangguhkan') DEFAULT 'aktif',
  `tanggal_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `komentar`
--

CREATE TABLE `komentar` (
  `id_komentar` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `isi_komentar` text NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id_report` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `upvotes`
--

CREATE TABLE `upvotes` (
  `id_vote` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_catatan` int(11) NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') DEFAULT 'mahasiswa',
  `foto_profil` varchar(255) NOT NULL DEFAULT 'default.png',
  `poin_reputasi` int(11) DEFAULT 0,
  `badge` varchar(50) NOT NULL DEFAULT 'Pemula',
  `bebas_delay_hingga` date DEFAULT '2000-01-01',
  `download_hari_ini` int(11) DEFAULT 0,
  `terakhir_download` date DEFAULT '2000-01-01',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `nim`, `email`, `password`, `role`, `foto_profil`, `poin_reputasi`, `badge`, `bebas_delay_hingga`, `download_hari_ini`, `terakhir_download`, `created_at`) VALUES
(1, 'Administrator', '0000000000', 'admin@campusnotes.id', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uAtbqpTFi', 'admin', 'default.png', 999, 'Master', '2000-01-01', 0, '2000-01-01', '2026-07-12 10:23:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_kuis`
--
ALTER TABLE `ai_kuis`
  ADD PRIMARY KEY (`id_kuis`),
  ADD UNIQUE KEY `uq_catatan_kuis` (`id_catatan`);

--
-- Indexes for table `ai_ringkasan`
--
ALTER TABLE `ai_ringkasan`
  ADD PRIMARY KEY (`id_ringkasan`),
  ADD UNIQUE KEY `uq_catatan_ringkasan` (`id_catatan`);

--
-- Indexes for table `catatan`
--
ALTER TABLE `catatan`
  ADD PRIMARY KEY (`id_catatan`),
  ADD KEY `fk_catatan_user` (`id_user`),
  ADD KEY `idx_mata_kuliah` (`mata_kuliah`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `komentar`
--
ALTER TABLE `komentar`
  ADD PRIMARY KEY (`id_komentar`),
  ADD KEY `fk_komentar_catatan` (`id_catatan`),
  ADD KEY `fk_komentar_user` (`id_user`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id_report`),
  ADD UNIQUE KEY `uq_user_report` (`id_user`,`id_catatan`),
  ADD KEY `fk_report_catatan` (`id_catatan`);

--
-- Indexes for table `upvotes`
--
ALTER TABLE `upvotes`
  ADD PRIMARY KEY (`id_vote`),
  ADD UNIQUE KEY `uq_user_vote` (`id_user`,`id_catatan`),
  ADD KEY `fk_vote_catatan` (`id_catatan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uq_nim` (`nim`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_kuis`
--
ALTER TABLE `ai_kuis`
  MODIFY `id_kuis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_ringkasan`
--
ALTER TABLE `ai_ringkasan`
  MODIFY `id_ringkasan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catatan`
--
ALTER TABLE `catatan`
  MODIFY `id_catatan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `komentar`
--
ALTER TABLE `komentar`
  MODIFY `id_komentar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id_report` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `upvotes`
--
ALTER TABLE `upvotes`
  MODIFY `id_vote` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_kuis`
--
ALTER TABLE `ai_kuis`
  ADD CONSTRAINT `fk_kuis_catatan` FOREIGN KEY (`id_catatan`) REFERENCES `catatan` (`id_catatan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ai_ringkasan`
--
ALTER TABLE `ai_ringkasan`
  ADD CONSTRAINT `fk_ringkasan_catatan` FOREIGN KEY (`id_catatan`) REFERENCES `catatan` (`id_catatan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `catatan`
--
ALTER TABLE `catatan`
  ADD CONSTRAINT `fk_catatan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `komentar`
--
ALTER TABLE `komentar`
  ADD CONSTRAINT `fk_komentar_catatan` FOREIGN KEY (`id_catatan`) REFERENCES `catatan` (`id_catatan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_komentar_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_catatan` FOREIGN KEY (`id_catatan`) REFERENCES `catatan` (`id_catatan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `upvotes`
--
ALTER TABLE `upvotes`
  ADD CONSTRAINT `fk_vote_catatan` FOREIGN KEY (`id_catatan`) REFERENCES `catatan` (`id_catatan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vote_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
