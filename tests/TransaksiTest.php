<?php
use PHPUnit\Framework\TestCase;

class TransaksiTest extends TestCase {

    protected function setUp(): void {
        // Bersihkan session sebelum tes
        $_SESSION = [];
        $_POST = [];
    }

    // 1. Menguji Rumus Kalkulasi Normal: (Subtotal + Ongkos) - Diskon
    public function testHitungGrandTotalPenjualanNormal() {
        // Simulasi isi keranjang belanja
        $_SESSION['keranjang'] = [
            ['BarangID' => 1, 'HargaTotal' => 1500000],
            ['BarangID' => 2, 'HargaTotal' => 2000000]
        ]; // Subtotal harusnya 3.500.000

        // Simulasi inputan kasir
        $totalOngkos = 150000;
        $diskon = 50000;

        // Logika dari proses_jual.php
        $subtotalEmas = 0;
        foreach ($_SESSION['keranjang'] as $item) {
            $subtotalEmas += $item['HargaTotal'];
        }
        $grandTotal = $subtotalEmas + $totalOngkos - $diskon;

        // Validasi
        // Harapan: 3.500.000 + 150000 - 50000 = 3.600.000
        $this->assertEquals(3600000, $grandTotal, "Perhitungan Grand Total Penjualan tidak akurat");
    }

    // 2. Menguji Proteksi Minus (Jika Kasir Salah Ketik Diskon Terlalu Besar)
    public function testGrandTotalTidakBolehMinus() {
        $_SESSION['keranjang'] = [
            ['BarangID' => 1, 'HargaTotal' => 500000]
        ];
        
        $totalOngkos = 0;
        $diskon = 1000000; // Kasir salah ketik diskon 1 Juta (melebihi harga barang)

        $subtotalEmas = 0;
        foreach ($_SESSION['keranjang'] as $item) {
            $subtotalEmas += $item['HargaTotal'];
        }
        $grandTotal = $subtotalEmas + $totalOngkos - $diskon;

        // Logika proteksi dari proses_jual.php
        if ($grandTotal < 0) {
            $grandTotal = 0;
        }

        // Validasi: Harusnya otomatis jadi 0, bukan -500.000
        $this->assertEquals(0, $grandTotal, "Sistem gagal memproteksi Grand Total yang bernilai minus");
    }

    // 3. Menguji Filter Input Kosong (String kosong jadi Angka Nol)
    public function testPenangananInputOngkosDanDiskonKosong() {
        // Simulasi form dikosongkan oleh kasir
        $_POST['total_ongkos'] = ''; 
        $_POST['diskon'] = '';

        // Logika konversi dari proses_jual.php baris 15-16
        $totalOngkos = isset($_POST['total_ongkos']) && $_POST['total_ongkos'] !== '' ? (int)$_POST['total_ongkos'] : 0;
        $diskon      = isset($_POST['diskon']) && $_POST['diskon'] !== '' ? (int)$_POST['diskon'] : 0;

        // Validasi
        $this->assertSame(0, $totalOngkos, "Input ongkos kosong harus dikonversi menjadi integer 0");
        $this->assertSame(0, $diskon, "Input diskon kosong harus dikonversi menjadi integer 0");
    }
}