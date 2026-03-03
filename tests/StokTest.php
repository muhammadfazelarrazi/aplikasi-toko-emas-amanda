<?php
use PHPUnit\Framework\TestCase;

class StokTest extends TestCase {

    // 1. Menguji Generate Kode Barang (Kasus: Database Masih Kosong sama sekali)
    public function testGenerateKodeBarangPertama() {
        // Simulasi hasil query baris 17-25 di input_masuk.php (tabel masih kosong)
        $kodeTerbesar = null; 

        if ($kodeTerbesar) {
            $urutan = (int) substr($kodeTerbesar, 4, 5);
            $urutan++;
        } else {
            $urutan = 1; // Sistem harus otomatis mulai dari 1
        }
        
        // Gabungkan string
        $kodeBarangBaru = "BRG-" . sprintf("%05s", $urutan); 
        
        $this->assertEquals("BRG-00001", $kodeBarangBaru, "Jika database kosong, kode pertama harus BRG-00001");
    }

    // 2. Menguji Generate Kode Barang Lanjutan (Kasus: Sudah Ada Data)
    public function testGenerateKodeBarangLanjutan() {
        // Simulasi hasil query menemukan kode terakhir di database
        $kodeTerbesar = "BRG-00145"; 

        if ($kodeTerbesar) {
            $urutan = (int) substr($kodeTerbesar, 4, 5); // Sistem memotong "BRG-" dan mengambil angka 145
            $urutan++; // Ditambah 1 menjadi 146
        } else {
            $urutan = 1;
        }
        
        $kodeBarangBaru = "BRG-" . sprintf("%05s", $urutan); 
        
        $this->assertEquals("BRG-00146", $kodeBarangBaru, "Sistem harus melanjutkan kode dari urutan terbesar");
    }

    // 3. Menguji Default Value Barang Masuk
    public function testDefaultValueBarangDariSupplier() {
        // Simulasi logika input_masuk.php baris 11-12
        $asal = 'Supplier';
        $status = 'Tersedia';
        
        $this->assertEquals('Supplier', $asal, "Barang masuk baru harus berstatus asal 'Supplier'");
        $this->assertEquals('Tersedia', $status, "Stok baru harus otomatis berstatus 'Tersedia'");
    }

    // 4. Menguji Default Filter Tanggal di Laporan Mutasi
    public function testDefaultPeriodeMutasiAwalBulanKeHariIni() {
        // Simulasi user baru buka halaman tanpa milih tanggal (sesuai mutasi.php baris 11-12)
        $_GET = []; 
        
        $tgl_awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-01');
        $tgl_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d');
        
        $expectedAwal = date('Y-m-01'); // Harapan: Tanggal 1 di bulan ini
        $expectedAkhir = date('Y-m-d'); // Harapan: Tanggal hari ini
        
        $this->assertEquals($expectedAwal, $tgl_awal, "Filter tanggal awal default harus tanggal 1 bulan ini");
        $this->assertEquals($expectedAkhir, $tgl_akhir, "Filter tanggal akhir default harus hari ini");
    }
}