-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Agu 2025 pada 04.01
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sirna`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `kode_menu` varchar(10) NOT NULL,
  `kuantitas` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `kode_menu`, `kuantitas`, `catatan`, `status`) VALUES
(1, 1, 'MC003', 3, '', 'completed'),
(2, 2, 'MC003', 2, '', 'pending'),
(3, 3, 'MC003', 1, '', 'pending'),
(4, 3, 'MC002', 2, '', 'pending'),
(5, 4, 'MC003', 1, '', 'pending'),
(6, 4, 'MC002', 1, '', 'pending'),
(7, 5, 'MC003', 6, '', 'pending'),
(8, 6, 'MC001', 1, '', 'pending'),
(9, 6, 'MC002', 1, '', 'pending'),
(10, 6, 'MC005', 1, '', 'pending'),
(11, 6, 'MC006', 1, '', 'pending'),
(12, 6, 'MC003', 1, '', 'pending'),
(13, 7, 'MC006', 1, '', 'pending'),
(14, 7, 'MC005', 1, '', 'pending'),
(15, 7, 'MC001', 1, '', 'pending'),
(16, 7, 'MC002', 1, '', 'pending'),
(17, 7, 'DR003', 1, '', 'pending'),
(18, 7, 'DR004', 1, '', 'pending'),
(19, 7, 'DR006', 2, '', 'pending'),
(20, 8, 'MC003', 1, '', 'pending'),
(21, 9, 'MC002', 2, '', 'pending'),
(22, 10, 'MC003', 2, '', 'pending'),
(23, 11, 'MC003', 2, '', 'pending'),
(24, 12, 'MC003', 2, '', 'pending'),
(25, 13, 'MC003', 1, '234', 'pending'),
(26, 14, 'MC002', 2, 'kangan', 'pending'),
(27, 14, 'MC003', 2, 'jangan', 'pending'),
(28, 15, 'MC003', 2, 'anjay', 'pending'),
(29, 16, 'MC003', 1, '', 'completed'),
(30, 16, 'DR002', 1, 'jangan pake es', 'completed'),
(31, 16, 'DS001', 2, '', 'completed'),
(32, 17, 'MC003', 1, 'anjay', 'completed'),
(33, 17, 'DR002', 1, '', 'completed'),
(34, 18, 'MC003', 3, '', 'completed'),
(35, 18, 'MC002', 2, '', 'completed'),
(36, 18, 'MC001', 1, '', 'completed'),
(37, 19, 'MC003', 3, 'jangan digoreng', 'completed');

-- --------------------------------------------------------

--
-- Struktur dari tabel `meja`
--

CREATE TABLE `meja` (
  `nomor_meja` int(11) NOT NULL,
  `kapasitas` int(11) NOT NULL,
  `status` enum('available','reserved','occupied') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `meja`
--

INSERT INTO `meja` (`nomor_meja`, `kapasitas`, `status`) VALUES
(1, 4, 'occupied'),
(2, 4, 'occupied'),
(3, 4, 'available'),
(4, 4, 'available'),
(5, 4, 'available'),
(6, 4, 'reserved'),
(7, 4, 'available'),
(8, 4, 'available'),
(9, 4, 'reserved'),
(10, 4, 'occupied'),
(11, 6, 'occupied'),
(12, 4, 'reserved'),
(13, 6, 'reserved');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu`
--

CREATE TABLE `menu` (
  `kode_menu` varchar(10) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `harga_menu` decimal(10,2) NOT NULL,
  `kategori` enum('Main Course','Drinks','Dessert') NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto_menu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menu`
--

INSERT INTO `menu` (`kode_menu`, `nama_menu`, `harga_menu`, `kategori`, `deskripsi`, `foto_menu`) VALUES
('DR001', 'Es Teh Manis', 5000.00, 'Drinks', 'Es teh dengan gula asli', NULL),
('DR002', 'Es Jeruk', 10000.00, 'Drinks', 'Es jeruk peras segar', NULL),
('DR003', 'Jus Alpukat', 18000.00, 'Drinks', 'Jus alpukat creamy dengan coklat', NULL),
('DR004', 'Jus Mangga', 17000.00, 'Drinks', 'Jus mangga segar', NULL),
('DR005', 'Teh Tarik', 14000.00, 'Drinks', 'Minuman teh dengan buih khas', NULL),
('DR006', 'Kopi Susu', 15000.00, 'Drinks', 'Kopi dengan susu creamy', NULL),
('DS001', 'Puding Coklat', 18000.00, 'Dessert', 'Puding coklat dengan saus vla', NULL),
('DS002', 'Roti Bakar', 15000.00, 'Dessert', 'Roti bakar dengan keju dan coklat', NULL),
('DS003', 'Pisang Goreng', 12000.00, 'Dessert', 'Pisang goreng krispi', NULL),
('MC001', 'Nasi Goreng', 25000.00, 'Main Course', 'Nasi goreng dengan bumbu spesial', NULL),
('MC002', 'Mie Ayam', 20000.00, 'Main Course', 'Mie ayam dengan toping ayam cincang', NULL),
('MC003', 'Ayam Bakar', 35000.00, 'Main Course', 'Ayam bakar dengan bumbu kecap', NULL),
('MC004', 'Sate Maranggi', 30000.00, 'Main Course', 'Sate daging sapi dengan sambal kecap', NULL),
('MC005', 'Sate Ayam', 28000.00, 'Main Course', 'Sate ayam dengan bumbu kacang', NULL),
('MC006', 'Sate Kambing', 35000.00, 'Main Course', 'Sate kambing dengan bumbu kecap pedas', NULL),
('MC007', 'Sate Padang', 32000.00, 'Main Course', 'Sate sapi dengan bumbu khas Padang', NULL),
('MC008', 'Sate Lilit', 29000.00, 'Main Course', 'Sate khas Bali dari daging cincang', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id_pegawai` int(11) NOT NULL,
  `nama_pegawai` varchar(100) NOT NULL,
  `jabatan` enum('owner','kasir','pelayan','koki','admin') NOT NULL,
  `kontak` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`id_pegawai`, `nama_pegawai`, `jabatan`, `kontak`, `alamat`, `username`, `password`) VALUES
(1, 'pelayan', 'pelayan', NULL, NULL, 'pelayan', '$2y$10$2Zrsrj5ZujYnk9X5qpMYIev4TUF5T9HrCR7RqmLHJcr7VF/7uKsYK'),
(2, 'pelayan12', 'pelayan', NULL, NULL, 'pelayan12', '$2y$10$y11vAMdBESB3uMpbtZRWrOXY70e2/Ar5JwMN6bPlVekygQW2H6pXe'),
(3, 'koki123', 'koki', NULL, NULL, 'koki123', '$2y$10$DuX9Z6XpvvgeIEg/ogInr.URfERP3GBpdvn1MIAJT8gJn26iK/OVq'),
(4, 'pelayan1', 'pelayan', NULL, NULL, 'pelayan1', '$2y$10$X5AsPumIuI69BW1VVqKcYeEr2vtxED6pGrbb/KcPbxuMHnkpRXjuS'),
(5, 'kasir123', 'kasir', NULL, NULL, 'kasir123', '$2y$10$exdak3y4UbpF.58zHTgl5uL9l9OuivFrD9qHQLKl0.EybmBTMrXZ2'),
(6, 'owner123', 'owner', NULL, NULL, 'owner123', '$2y$10$KWXPo7s.ExZ.lpD6v9qerOwtMxoGO3SsDVPsUZv3Ia//ksewTYuj2'),
(7, 'owner1', 'owner', NULL, NULL, 'owner1', '$2y$10$6lkPqbUSlXvbd9B0kVicZeL8//xfXKUKI.aAEegoq.V7vWAa.vkmW');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kontak` varchar(20) DEFAULT NULL,
  `tgl_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `nomor_meja` int(11) NOT NULL,
  `id_pegawai` int(11) NOT NULL COMMENT 'ID pelayan yang membuat pesanan',
  `tgl_pesanan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','paid') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `nomor_meja`, `id_pegawai`, `tgl_pesanan`, `status`) VALUES
(1, 1, 4, '2025-08-05 22:23:58', 'paid'),
(2, 2, 4, '2025-08-05 22:29:42', 'paid'),
(3, 15, 4, '2025-08-05 22:33:41', 'paid'),
(4, 1, 4, '2025-08-05 22:47:25', 'paid'),
(5, 2, 4, '2025-08-05 22:47:33', 'paid'),
(6, 3, 4, '2025-08-05 22:47:45', 'paid'),
(7, 4, 4, '2025-08-05 22:48:02', 'paid'),
(8, 1, 4, '2025-08-05 23:18:18', 'paid'),
(9, 2, 4, '2025-08-05 23:18:25', 'paid'),
(10, 1, 4, '2025-08-05 23:22:13', 'paid'),
(11, 15, 4, '2025-08-05 23:22:41', 'paid'),
(12, 11, 4, '2025-08-05 23:53:54', 'paid'),
(13, 1, 4, '2025-08-06 00:18:20', 'paid'),
(14, 12, 4, '2025-08-06 00:18:35', 'paid'),
(15, 9, 4, '2025-08-06 00:35:30', 'paid'),
(16, 10, 4, '2025-08-06 00:36:21', 'paid'),
(17, 1, 4, '2025-08-06 00:38:26', 'paid'),
(18, 11, 4, '2025-08-06 00:40:43', 'paid'),
(19, 2, 4, '2025-08-06 01:25:49', 'paid');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reservasi`
--

CREATE TABLE `reservasi` (
  `id_reservasi` int(11) NOT NULL,
  `id_pelanggan` int(11) DEFAULT NULL COMMENT 'ID pelanggan jika terdaftar, NULL untuk tamu',
  `nama_pelanggan` varchar(100) NOT NULL,
  `kontak_pelanggan` varchar(20) NOT NULL,
  `jumlah_orang` int(11) NOT NULL,
  `tgl_reservasi` date NOT NULL,
  `waktu_reservasi` time NOT NULL,
  `nomor_meja` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `tgl_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reservasi`
--

INSERT INTO `reservasi` (`id_reservasi`, `id_pelanggan`, `nama_pelanggan`, `kontak_pelanggan`, `jumlah_orang`, `tgl_reservasi`, `waktu_reservasi`, `nomor_meja`, `status`, `tgl_dibuat`) VALUES
(1, NULL, 'Rikza Danan Irdian 2', '(+62) 821 2341 9510', 4, '0205-02-21', '12:32:00', 6, 'confirmed', '2025-08-06 00:59:48'),
(2, NULL, 'Dan Voice Test 1', '(+62) 821 2341 9510', 5, '2005-02-23', '12:33:00', 6, 'confirmed', '2025-08-06 01:01:28'),
(3, NULL, 'FUTURE ENTREPRENEUR SUMMIT ', '(+62) 821 2341 9510', 4, '0004-05-21', '12:34:00', 13, 'confirmed', '2025-08-06 01:03:05'),
(4, NULL, 'iya', '(+62) 821 2341 9510', 4, '2025-03-21', '12:23:00', 13, 'confirmed', '2025-08-06 01:06:52'),
(5, NULL, 'anjay', '(+62) 821 2341 9510', 4, '2025-08-06', '12:00:00', 13, 'confirmed', '2025-08-06 01:11:23'),
(6, NULL, 'dandi', '(+62) 821 2341 9510', 4, '2025-08-07', '13:00:00', 12, 'confirmed', '2025-08-06 01:24:05'),
(7, NULL, 'asdasd', '(+62) 821 2341 9510', 4, '2025-08-06', '12:00:00', 9, 'confirmed', '2025-08-06 01:25:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_pegawai` int(11) NOT NULL COMMENT 'ID kasir yang memproses',
  `tgl_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_bayar` decimal(10,2) NOT NULL,
  `metode_bayar` enum('cash','card','qris') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_pesanan`, `id_pegawai`, `tgl_transaksi`, `total_bayar`, `metode_bayar`) VALUES
(1, 1, 5, '2025-08-05 22:59:35', 105000.00, 'cash'),
(2, 3, 5, '2025-08-05 23:01:00', 75000.00, 'cash'),
(3, 2, 5, '2025-08-05 23:05:41', 70000.00, 'card'),
(4, 4, 5, '2025-08-05 23:11:37', 55000.00, 'cash'),
(5, 5, 5, '2025-08-05 23:11:41', 210000.00, 'cash'),
(6, 6, 5, '2025-08-05 23:12:07', 143000.00, 'cash'),
(7, 6, 5, '2025-08-05 23:12:14', 143000.00, 'cash'),
(8, 7, 5, '2025-08-05 23:16:43', 173000.00, 'cash'),
(9, 8, 5, '2025-08-05 23:19:01', 35000.00, 'cash'),
(10, 9, 5, '2025-08-05 23:19:06', 40000.00, 'cash'),
(11, 10, 5, '2025-08-05 23:23:28', 70000.00, 'cash'),
(12, 11, 5, '2025-08-05 23:23:48', 70000.00, 'cash'),
(13, 12, 5, '2025-08-06 00:19:29', 70000.00, 'card'),
(14, 13, 5, '2025-08-06 00:19:34', 35000.00, 'qris'),
(15, 14, 5, '2025-08-06 00:19:42', 110000.00, 'qris'),
(16, 15, 5, '2025-08-06 01:26:29', 70000.00, 'card'),
(17, 16, 5, '2025-08-06 01:26:34', 81000.00, 'card'),
(18, 17, 5, '2025-08-06 01:26:39', 45000.00, 'cash'),
(19, 18, 5, '2025-08-06 01:26:41', 170000.00, 'cash'),
(20, 19, 5, '2025-08-06 01:26:44', 105000.00, 'cash');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `kode_menu` (`kode_menu`);

--
-- Indeks untuk tabel `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`nomor_meja`);

--
-- Indeks untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`kode_menu`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id_pegawai`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `nomor_meja` (`nomor_meja`),
  ADD KEY `id_pegawai` (`id_pegawai`);

--
-- Indeks untuk tabel `reservasi`
--
ALTER TABLE `reservasi`
  ADD PRIMARY KEY (`id_reservasi`),
  ADD KEY `nomor_meja` (`nomor_meja`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_pegawai` (`id_pegawai`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id_pegawai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `reservasi`
--
ALTER TABLE `reservasi`
  MODIFY `id_reservasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`kode_menu`) REFERENCES `menu` (`kode_menu`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`nomor_meja`) REFERENCES `meja` (`nomor_meja`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reservasi`
--
ALTER TABLE `reservasi`
  ADD CONSTRAINT `reservasi_ibfk_1` FOREIGN KEY (`nomor_meja`) REFERENCES `meja` (`nomor_meja`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `reservasi_ibfk_2` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
