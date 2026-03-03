<?php
use PHPUnit\Framework\TestCase;

class LaporanTest extends TestCase {

    // 1. Menguji Filter Tanggal Default (sesuai harian.php baris 5-6)
    public function testDefaultFilterTanggalAdalahHariIni() {
        // Simulasi jika user tidak memilih tanggal (akses menu langsung)
        $_GET = []; 

        $tgl_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : date('Y-m-d');
        $tgl_selesai = isset($_GET['selesai']) ? $_GET['selesai'] : date('Y-m-d');

        $hariIni = date('Y-m-d');

        $this->assertEquals($hariIni, $tgl_mulai, "Tanggal mulai default harus hari ini");
        $this->assertEquals($hariIni, $tgl_selesai, "Tanggal selesai default harus hari ini");
    }

    // 2. Menguji Kalkulasi Omzet Bersih (sesuai harian.php baris 64-70)
    public function testKalkulasiGrandTotalOmzetBersih() {
        // Simulasi data dari database (gabungan jual dan beli balik)
        $dataTransaksi = [
            ['TipeTransaksi' => 'Penjualan', 'TotalTransaksi' => 5000000],
            ['TipeTransaksi' => 'Penjualan', 'TotalTransaksi' => 2000000],
            ['TipeTransaksi' => 'Buyback', 'TotalTransaksi' => 1500000] // Toko keluar uang
        ];

        // Logika dari harian.php
        $grandTotal = 0;
        foreach ($dataTransaksi as $row) {
            if ($row['TipeTransaksi'] == 'Penjualan') {
                $grandTotal += $row['TotalTransaksi'];
            } else {
                $grandTotal -= $row['TotalTransaksi'];
            }
        }

        // Validasi: Harapan omzet bersih adalah 5jt + 2jt - 1.5jt = 5.500.000
        $this->assertEquals(5500000, $grandTotal, "Rumus kalkulasi Omzet Bersih (Penjualan dikurangi Buyback) tidak akurat");
    }

    // 3. Menguji Format Teks Periode Laporan Cetak (sesuai cetak_laporan_pdf.php baris 15-18)
    public function testFormatTeksPeriodeSatuHari() {
        // Simulasi user mencetak laporan untuk hari yang sama
        $tgl_mulai = '2023-10-15';
        $tgl_selesai = '2023-10-15';

        $format_mulai = date('d F Y', strtotime($tgl_mulai));
        $format_selesai = date('d F Y', strtotime($tgl_selesai));

        $teks_periode = ($tgl_mulai == $tgl_selesai) ? $format_mulai : "$format_mulai s/d $format_selesai";

        // Harapannya tidak ada embel-embel "s/d" jika tanggalnya sama
        $this->assertEquals('15 October 2023', $teks_periode, "Jika periode hanya 1 hari, format teks harus tunggal tanpa kata 's/d'");
    }
}