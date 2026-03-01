-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20260227.1ade4a5c22
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 01, 2026 at 03:10 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_emas_amanda`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang_stok`
--

CREATE TABLE `barang_stok` (
  `BarangID` int NOT NULL,
  `KodeBarang` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ProdukKatalogID` int NOT NULL,
  `SupplierID` int DEFAULT NULL,
  `BeratGram` decimal(10,2) NOT NULL,
  `HargaBeliModal` decimal(14,2) NOT NULL,
  `TanggalMasuk` date NOT NULL,
  `Status` enum('Tersedia','Terjual','Buyback') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Tersedia',
  `AsalBarang` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_stok`
--

INSERT INTO `barang_stok` (`BarangID`, `KodeBarang`, `ProdukKatalogID`, `SupplierID`, `BeratGram`, `HargaBeliModal`, `TanggalMasuk`, `Status`, `AsalBarang`) VALUES
(6, 'BRG-00001', 8, 2, 100.00, 10000000.00, '2026-02-28', 'Tersedia', 'Supplier'),
(7, 'BRG-00002', 10, 2, 1.00, 1000000.00, '2026-03-01', 'Tersedia', 'Supplier'),
(8, 'BRG-00003', 4, 2, 1.00, 580000.00, '2026-03-01', 'Tersedia', 'Buyback'),
(1001, 'BRG-10001', 1, 1, 2.50, 750000.00, '2026-02-15', 'Tersedia', 'Supplier'),
(1002, 'BRG-10002', 2, 2, 3.00, 1400000.00, '2026-02-16', 'Tersedia', 'Supplier'),
(1003, 'BRG-10003', 3, 3, 5.00, 4000000.00, '2026-02-17', 'Tersedia', 'Supplier'),
(1004, 'BRG-10004', 4, 1, 1.50, 850000.00, '2026-02-18', 'Tersedia', 'Supplier'),
(1005, 'BRG-10005', 5, 2, 4.00, 1800000.00, '2026-02-19', 'Tersedia', 'Supplier'),
(1006, 'BRG-10006', 6, 3, 10.00, 8000000.00, '2026-02-20', 'Tersedia', 'Supplier'),
(1007, 'BRG-10007', 7, 1, 8.00, 4600000.00, '2026-02-21', 'Tersedia', 'Supplier'),
(1008, 'BRG-10008', 8, 2, 2.00, 2400000.00, '2026-02-22', 'Tersedia', 'Supplier'),
(1009, 'BRG-10009', 9, 3, 1.50, 1200000.00, '2026-02-23', 'Tersedia', 'Supplier'),
(1010, 'BRG-10010', 10, 1, 5.00, 6000000.00, '2026-02-24', 'Tersedia', 'Supplier'),
(1011, 'BRG-10011', 1, 2, 2.00, 600000.00, '2026-02-10', 'Terjual', 'Supplier'),
(1012, 'BRG-10012', 3, 3, 4.00, 3200000.00, '2026-02-10', 'Terjual', 'Supplier'),
(1013, 'BRG-10013', 6, 1, 5.00, 4000000.00, '2026-02-11', 'Terjual', 'Supplier'),
(1014, 'BRG-10014', 10, 2, 10.00, 12000000.00, '2026-02-12', 'Terjual', 'Supplier'),
(1015, 'BRG-10015', 7, 3, 6.00, 3500000.00, '2026-02-15', 'Terjual', 'Supplier'),
(2001, 'BRG-20001', 2001, 2, 15.00, 12000000.00, '2026-02-05', 'Tersedia', 'Supplier'),
(2002, 'BRG-20002', 2006, 1, 0.50, 650000.00, '2026-02-06', 'Tersedia', 'Supplier'),
(2003, 'BRG-20003', 2006, 1, 1.00, 1300000.00, '2026-02-07', 'Tersedia', 'Supplier'),
(2004, 'BRG-20004', 2007, 3, 20.00, 16000000.00, '2026-02-08', 'Tersedia', 'Supplier'),
(2005, 'BRG-20005', 2010, 1, 25.00, 31000000.00, '2026-02-09', 'Tersedia', 'Supplier'),
(2006, 'BRG-20006', 2011, 2, 3.50, 2800000.00, '2026-02-10', 'Tersedia', 'Supplier'),
(2007, 'BRG-20007', 2013, 3, 4.00, 3200000.00, '2026-02-11', 'Tersedia', 'Supplier'),
(2008, 'BRG-20008', 2015, 2, 5.00, 6250000.00, '2026-02-12', 'Tersedia', 'Supplier'),
(2009, 'BRG-20009', 2015, 2, 10.00, 12500000.00, '2026-02-13', 'Tersedia', 'Supplier'),
(2010, 'BRG-20010', 2002, 3, 5.00, 2250000.00, '2026-02-14', 'Tersedia', 'Supplier'),
(2011, 'BRG-20011', 2003, 1, 6.00, 3480000.00, '2026-02-15', 'Tersedia', 'Supplier'),
(2012, 'BRG-20012', 2004, 2, 1.50, 480000.00, '2026-02-16', 'Tersedia', 'Supplier'),
(2013, 'BRG-20013', 2004, 2, 2.00, 640000.00, '2026-02-17', 'Tersedia', 'Supplier'),
(2014, 'BRG-20014', 2005, 3, 1.00, 450000.00, '2026-02-18', 'Tersedia', 'Supplier'),
(2015, 'BRG-20015', 2008, 1, 3.00, 1350000.00, '2026-02-19', 'Tersedia', 'Supplier'),
(2016, 'BRG-20016', 2009, 2, 8.00, 4640000.00, '2026-02-20', 'Tersedia', 'Supplier'),
(2017, 'BRG-20017', 2012, 3, 1.50, 480000.00, '2026-02-21', 'Tersedia', 'Supplier'),
(2018, 'BRG-20018', 2014, 1, 4.50, 2025000.00, '2026-02-22', 'Tersedia', 'Supplier'),
(2019, 'BRG-20019', 2, 2, 2.80, 1250000.00, '2026-02-23', 'Tersedia', 'Buyback'),
(2020, 'BRG-20020', 5, 2, 3.50, 1600000.00, '2026-02-24', 'Tersedia', 'Buyback'),
(2021, 'BRG-20021', 7, 1, 7.50, 4200000.00, '2026-02-25', 'Tersedia', 'Buyback'),
(2022, 'BRG-20022', 1, 2, 1.00, 320000.00, '2026-02-26', 'Tersedia', 'Supplier'),
(2023, 'BRG-20023', 1, 2, 1.50, 480000.00, '2026-02-26', 'Tersedia', 'Supplier'),
(2024, 'BRG-20024', 3, 3, 5.50, 4400000.00, '2026-02-27', 'Tersedia', 'Supplier'),
(2025, 'BRG-20025', 4, 1, 2.00, 1160000.00, '2026-02-27', 'Tersedia', 'Supplier'),
(2026, 'BRG-20026', 6, 3, 12.00, 9600000.00, '2026-02-28', 'Tersedia', 'Supplier'),
(2027, 'BRG-20027', 8, 2, 2.50, 3100000.00, '2026-02-28', 'Tersedia', 'Supplier'),
(2028, 'BRG-20028', 9, 3, 2.00, 1600000.00, '2026-03-01', 'Tersedia', 'Supplier'),
(2029, 'BRG-20029', 10, 1, 2.00, 2500000.00, '2026-03-01', 'Tersedia', 'Supplier'),
(2030, 'BRG-20030', 2004, 2, 3.00, 960000.00, '2026-03-01', 'Tersedia', 'Supplier');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi_barang`
--

CREATE TABLE `detail_transaksi_barang` (
  `DetailTransaksiID` int NOT NULL,
  `TransaksiID` int NOT NULL,
  `BarangID` int NOT NULL,
  `HargaSatuanSaatItu` decimal(14,2) NOT NULL,
  `Ongkos` decimal(14,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_transaksi_barang`
--

INSERT INTO `detail_transaksi_barang` (`DetailTransaksiID`, `TransaksiID`, `BarangID`, `HargaSatuanSaatItu`, `Ongkos`) VALUES
(5, 5, 8, 680000.00, 0.00),
(6, 11, 8, 580000.00, 0.00),
(1001, 1001, 1011, 790000.00, 0.00),
(1002, 1002, 1012, 3620000.00, 0.00),
(1003, 1003, 1013, 4600000.00, 0.00),
(1004, 1004, 1014, 13500000.00, 0.00),
(1005, 1005, 1015, 4140000.00, 0.00),
(1006, 1006, 1009, 1200000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `KaryawanID` int NOT NULL,
  `NamaKaryawan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Role` enum('Owner','Kasir') COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`KaryawanID`, `NamaKaryawan`, `Username`, `Password`, `Role`) VALUES
(1, 'Rahman', 'owner', '202cb962ac59075b964b07152d234b70', 'Owner'),
(2, 'Wawan Wahyuni', 'kasir', '202cb962ac59075b964b07152d234b70', 'Kasir'),
(3, 'Super Admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Owner'),
(6, 'Asep ', 'Asep', '3494b5bf63f99876fce7795736049cbd', 'Kasir');

-- --------------------------------------------------------

--
-- Table structure for table `log_status_barang`
--

CREATE TABLE `log_status_barang` (
  `LogID` int NOT NULL,
  `BarangID` int DEFAULT NULL,
  `KodeBarangLama` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `StatusLama` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `StatusBaru` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `WaktuPerubahan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Keterangan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metode_pembayaran`
--

CREATE TABLE `metode_pembayaran` (
  `MetodeID` int NOT NULL,
  `NamaMetode` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `metode_pembayaran`
--

INSERT INTO `metode_pembayaran` (`MetodeID`, `NamaMetode`) VALUES
(1, 'Tunai'),
(2, 'Transfer Bank'),
(3, 'QRIS');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `PelangganID` int NOT NULL,
  `KodePelanggan` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NamaPelanggan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `NoHP` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Alamat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`PelangganID`, `KodePelanggan`, `NamaPelanggan`, `NoHP`, `Email`, `Alamat`) VALUES
(1, 'PLG-00001', 'Rina Gunawan', '08123456789', 'rina@email.com', 'Jl. Merdeka No. 10'),
(2, 'PLG-00002', 'Elsa', '08567891234', 'elsanoviaa65@gmail.com', 'Jl. Sudirman No. 5'),
(3, 'PLG-00003', 'Siti Aminah', '08198765432', 'siti@email.com', 'Perumahan Elite Blok A'),
(4, 'PLG-00004', 'Budi Santoso', '08133344455', 'budi@email.com', 'Jl. Pahlawan No. 2'),
(5, 'PLG-00005', 'Dewi Persik', '08177788899', 'dewi@email.com', 'Komp. Cihideung'),
(6, NULL, 'Fazel', '08123456', 'fx.new01@gmail.com', '-'),
(1001, 'PLG-01001', 'Agus Prayitno', '08111222333', 'agus.prayitno@gmail.com', 'Kawalu, Tasikmalaya'),
(1002, 'PLG-01002', 'Lina Marlina', '08222333444', 'lina.marlina88@gmail.com', 'Cibeureum, Tasikmalaya'),
(1003, 'PLG-01003', 'Hendra Setiawan', '08333444555', 'hendra.setiawan@gmail.com', 'Indihiang, Tasikmalaya'),
(1004, 'PLG-01004', 'Sari Wulandari', '08444555666', 'sari.wulan@gmail.com', 'Mangkubumi, Tasikmalaya'),
(1005, 'PLG-01005', 'Fajar Nugraha', '08555666777', 'fajar.nugraha@gmail.com', 'Cipedes, Tasikmalaya'),
(2001, 'PLG-02001', 'Tatang Sutarman', '081223344551', 'tatang.s@gmail.com', 'Rajapolah, Tasikmalaya'),
(2002, 'PLG-02002', 'Neneng Hasanah', '081223344552', 'neneng.hsn@gmail.com', 'Ciawi, Tasikmalaya'),
(2003, 'PLG-02003', 'Asep Saepudin', '081223344553', 'asep.saepudin@gmail.com', 'Singaparna, Tasikmalaya'),
(2004, 'PLG-02004', 'Imas Rostiana', '081223344554', 'imas.rosti@gmail.com', 'Manonjaya, Tasikmalaya'),
(2005, 'PLG-02005', 'Ujang Koswara', '081223344555', 'ujang.kos@gmail.com', 'Cineam, Tasikmalaya'),
(2006, 'PLG-02006', 'Siti Maryam', '081223344556', 'maryam.siti@gmail.com', 'Salawu, Tasikmalaya'),
(2007, 'PLG-02007', 'Iwan Ridwan', '081223344557', 'iwan.ridwan@gmail.com', 'Cipatujah, Tasikmalaya'),
(2008, 'PLG-02008', 'Yanti Yulianti', '081223344558', 'yanti.yul@gmail.com', 'Sariwangi, Tasikmalaya'),
(2009, 'PLG-02009', 'Dedi Mulyadi', '081223344559', 'dedi.mul@gmail.com', 'Sukaresik, Tasikmalaya'),
(2010, 'PLG-02010', 'Euis Komariah', '081223344560', 'euis.komar@gmail.com', 'Cigalontang, Tasikmalaya');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `PembayaranID` int NOT NULL,
  `TransaksiID` int NOT NULL,
  `MetodeID` int NOT NULL,
  `JumlahBayar` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`PembayaranID`, `TransaksiID`, `MetodeID`, `JumlahBayar`) VALUES
(5, 5, 1, 630000.00),
(6, 11, 1, 580000.00),
(1001, 1001, 1, 840000.00),
(1002, 1002, 2, 3700000.00),
(1003, 1003, 3, 4675000.00),
(1004, 1004, 2, 13450000.00),
(1005, 1005, 1, 4190000.00),
(1006, 1006, 1, 1200000.00);

-- --------------------------------------------------------

--
-- Table structure for table `produk_katalog`
--

CREATE TABLE `produk_katalog` (
  `ProdukKatalogID` int NOT NULL,
  `NamaProduk` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Tipe` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Kadar` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Satuan` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'Gram'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk_katalog`
--

INSERT INTO `produk_katalog` (`ProdukKatalogID`, `NamaProduk`, `Tipe`, `Kadar`, `Satuan`) VALUES
(1, 'Cincin Bayi Polos', 'Cincin', '6K', 'Gram'),
(2, 'Cincin Permata Merah', 'Cincin', '8K', 'Gram'),
(3, 'Cincin Kawin Ukir', 'Cincin', '16K', 'Gram'),
(4, 'Cincin Solitaire', 'Cincin', '10K', 'Gram'),
(5, 'Kalung Nori', 'Kalung', '8K', 'Gram'),
(6, 'Kalung Italia', 'Kalung', '16K', 'Gram'),
(7, 'Gelang Keroncong', 'Gelang', '10K', 'Gram'),
(8, 'Anting Toge', 'Anting', '24K', 'Gram'),
(9, 'Liontin Huruf', 'Liontin', '16K', 'Gram'),
(10, 'Logam Mulia Antam', 'Logam Mulia', '24K', 'Gram'),
(2001, 'Gelang Sisik Naga', 'Gelang', '16K', 'Gram'),
(2002, 'Kalung Milor', 'Kalung', '8K', 'Gram'),
(2003, 'Cincin Stempel Pria', 'Cincin', '10K', 'Gram'),
(2004, 'Anting Desi', 'Anting', '6K', 'Gram'),
(2005, 'Liontin Lafadz', 'Liontin', '8K', 'Gram'),
(2006, 'MiniGold Reguler', 'Logam Mulia', '24K', 'Gram'),
(2007, 'Gelang Rantai Medan', 'Gelang', '16K', 'Gram'),
(2008, 'Cincin Belah Rotan', 'Cincin', '8K', 'Gram'),
(2009, 'Kalung Cassano', 'Kalung', '10K', 'Gram'),
(2010, 'Gelang Keroncong Ukir', 'Gelang', '24K', 'Gram'),
(2011, 'Anting Gantung Permata', 'Anting', '16K', 'Gram'),
(2012, 'Liontin Hati', 'Liontin', '6K', 'Gram'),
(2013, 'Cincin Kawin Polos', 'Cincin', '16K', 'Gram'),
(2014, 'Gelang Serut', 'Gelang', '8K', 'Gram'),
(2015, 'Emas Batangan UBS', 'Logam Mulia', '24K', 'Gram');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_harga`
--

CREATE TABLE `riwayat_harga` (
  `HargaID` int NOT NULL,
  `Tanggal` date NOT NULL,
  `Kadar` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `HargaJualPerGram` decimal(14,2) NOT NULL,
  `HargaBeliPerGram` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_harga`
--

INSERT INTO `riwayat_harga` (`HargaID`, `Tanggal`, `Kadar`, `HargaJualPerGram`, `HargaBeliPerGram`) VALUES
(1, '2026-01-08', '6K', 420000.00, 320000.00),
(2, '2026-01-08', '8K', 550000.00, 450000.00),
(3, '2026-01-08', '10K', 680000.00, 580000.00),
(4, '2026-01-08', '16K', 980000.00, 880000.00),
(5, '2026-01-08', '24K', 1380000.00, 1280000.00),
(6, '2026-02-28', '24K', 1000000.00, 900000.00),
(7, '2026-03-01', '24K', 1000000.00, 1000000.00),
(1001, '2026-02-23', '24K', 1310000.00, 1210000.00),
(1002, '2026-02-24', '24K', 1320000.00, 1220000.00),
(1003, '2026-02-25', '24K', 1315000.00, 1215000.00),
(1004, '2026-02-26', '24K', 1325000.00, 1225000.00),
(1005, '2026-02-27', '24K', 1335000.00, 1235000.00),
(1006, '2026-02-28', '24K', 1340000.00, 1240000.00),
(1007, '2026-03-01', '24K', 1350000.00, 1250000.00),
(1008, '2026-02-23', '16K', 880000.00, 800000.00),
(1009, '2026-02-24', '16K', 890000.00, 810000.00),
(1010, '2026-02-25', '16K', 885000.00, 805000.00),
(1011, '2026-02-26', '16K', 895000.00, 815000.00),
(1012, '2026-02-27', '16K', 905000.00, 825000.00),
(1013, '2026-02-28', '16K', 910000.00, 830000.00),
(1014, '2026-03-01', '16K', 920000.00, 840000.00),
(1015, '2026-02-23', '10K', 650000.00, 580000.00),
(1016, '2026-02-24', '10K', 660000.00, 590000.00),
(1017, '2026-02-25', '10K', 655000.00, 585000.00),
(1018, '2026-02-26', '10K', 665000.00, 595000.00),
(1019, '2026-02-27', '10K', 670000.00, 600000.00),
(1020, '2026-02-28', '10K', 680000.00, 610000.00),
(1021, '2026-03-01', '10K', 690000.00, 620000.00),
(1022, '2026-02-23', '8K', 520000.00, 460000.00),
(1023, '2026-02-24', '8K', 530000.00, 470000.00),
(1024, '2026-02-25', '8K', 525000.00, 465000.00),
(1025, '2026-02-26', '8K', 535000.00, 475000.00),
(1026, '2026-02-27', '8K', 540000.00, 480000.00),
(1027, '2026-02-28', '8K', 550000.00, 490000.00),
(1028, '2026-03-01', '8K', 560000.00, 500000.00),
(1029, '2026-02-23', '6K', 380000.00, 310000.00),
(1030, '2026-02-24', '6K', 390000.00, 320000.00),
(1031, '2026-02-25', '6K', 385000.00, 315000.00),
(1032, '2026-02-26', '6K', 395000.00, 325000.00),
(1033, '2026-02-27', '6K', 400000.00, 330000.00),
(1034, '2026-02-28', '6K', 410000.00, 340000.00),
(1035, '2026-03-01', '6K', 420000.00, 350000.00);

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `SupplierID` int NOT NULL,
  `NamaSupplier` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Kontak` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Alamat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`SupplierID`, `NamaSupplier`, `Kontak`, `Alamat`) VALUES
(1, 'PT. Antam Tbk', '021-7891011', 'Jakarta Timur'),
(2, 'CV. UBS Gold', '031-8989898', 'Surabaya'),
(3, 'PT. HWT (Hartono Wira Tanik)', '031-1234567', 'Sidoarjo');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `TransaksiID` int NOT NULL,
  `PelangganID` int DEFAULT NULL,
  `KaryawanID` int NOT NULL,
  `TanggalWaktu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TipeTransaksi` enum('Penjualan','Buyback') COLLATE utf8mb4_general_ci NOT NULL,
  `TotalOngkos` decimal(15,2) NOT NULL DEFAULT '0.00',
  `TotalDiskon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `TotalTransaksi` decimal(14,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`TransaksiID`, `PelangganID`, `KaryawanID`, `TanggalWaktu`, `TipeTransaksi`, `TotalOngkos`, `TotalDiskon`, `TotalTransaksi`) VALUES
(5, 6, 3, '2026-03-01 04:49:44', 'Penjualan', 0.00, 0.00, 630000.00),
(11, 6, 3, '2026-03-01 08:03:18', 'Buyback', 0.00, 0.00, 580000.00),
(1001, 1001, 2, '2026-02-26 03:15:00', 'Penjualan', 50000.00, 0.00, 840000.00),
(1002, 1002, 2, '2026-02-27 04:20:00', 'Penjualan', 100000.00, 20000.00, 3700000.00),
(1003, 1003, 3, '2026-03-01 07:30:00', 'Penjualan', 75000.00, 0.00, 4675000.00),
(1004, 1004, 2, '2026-03-01 02:00:00', 'Penjualan', 0.00, 50000.00, 13450000.00),
(1005, 1005, 3, '2026-03-01 03:45:00', 'Penjualan', 50000.00, 0.00, 4190000.00),
(1006, 1001, 2, '2026-03-01 06:10:00', 'Buyback', 0.00, 0.00, 1200000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang_stok`
--
ALTER TABLE `barang_stok`
  ADD PRIMARY KEY (`BarangID`),
  ADD UNIQUE KEY `KodeBarang` (`KodeBarang`),
  ADD KEY `ProdukKatalogID` (`ProdukKatalogID`),
  ADD KEY `SupplierID` (`SupplierID`);

--
-- Indexes for table `detail_transaksi_barang`
--
ALTER TABLE `detail_transaksi_barang`
  ADD PRIMARY KEY (`DetailTransaksiID`),
  ADD KEY `TransaksiID` (`TransaksiID`),
  ADD KEY `BarangID` (`BarangID`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`KaryawanID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `log_status_barang`
--
ALTER TABLE `log_status_barang`
  ADD PRIMARY KEY (`LogID`);

--
-- Indexes for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  ADD PRIMARY KEY (`MetodeID`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`PelangganID`),
  ADD UNIQUE KEY `KodePelanggan` (`KodePelanggan`),
  ADD UNIQUE KEY `NoHP` (`NoHP`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`PembayaranID`),
  ADD KEY `TransaksiID` (`TransaksiID`),
  ADD KEY `MetodeID` (`MetodeID`);

--
-- Indexes for table `produk_katalog`
--
ALTER TABLE `produk_katalog`
  ADD PRIMARY KEY (`ProdukKatalogID`);

--
-- Indexes for table `riwayat_harga`
--
ALTER TABLE `riwayat_harga`
  ADD PRIMARY KEY (`HargaID`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`SupplierID`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`TransaksiID`),
  ADD KEY `PelangganID` (`PelangganID`),
  ADD KEY `KaryawanID` (`KaryawanID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang_stok`
--
ALTER TABLE `barang_stok`
  MODIFY `BarangID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2031;

--
-- AUTO_INCREMENT for table `detail_transaksi_barang`
--
ALTER TABLE `detail_transaksi_barang`
  MODIFY `DetailTransaksiID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1007;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `KaryawanID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `log_status_barang`
--
ALTER TABLE `log_status_barang`
  MODIFY `LogID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  MODIFY `MetodeID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `PelangganID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2011;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `PembayaranID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1007;

--
-- AUTO_INCREMENT for table `produk_katalog`
--
ALTER TABLE `produk_katalog`
  MODIFY `ProdukKatalogID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2016;

--
-- AUTO_INCREMENT for table `riwayat_harga`
--
ALTER TABLE `riwayat_harga`
  MODIFY `HargaID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1036;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `SupplierID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `TransaksiID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1007;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_stok`
--
ALTER TABLE `barang_stok`
  ADD CONSTRAINT `barang_stok_ibfk_1` FOREIGN KEY (`ProdukKatalogID`) REFERENCES `produk_katalog` (`ProdukKatalogID`),
  ADD CONSTRAINT `barang_stok_ibfk_2` FOREIGN KEY (`SupplierID`) REFERENCES `supplier` (`SupplierID`);

--
-- Constraints for table `detail_transaksi_barang`
--
ALTER TABLE `detail_transaksi_barang`
  ADD CONSTRAINT `detail_transaksi_barang_ibfk_1` FOREIGN KEY (`TransaksiID`) REFERENCES `transaksi` (`TransaksiID`),
  ADD CONSTRAINT `detail_transaksi_barang_ibfk_2` FOREIGN KEY (`BarangID`) REFERENCES `barang_stok` (`BarangID`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`TransaksiID`) REFERENCES `transaksi` (`TransaksiID`),
  ADD CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`MetodeID`) REFERENCES `metode_pembayaran` (`MetodeID`);

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`PelangganID`) REFERENCES `pelanggan` (`PelangganID`),
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`KaryawanID`) REFERENCES `karyawan` (`KaryawanID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
