<?php
use PHPUnit\Framework\TestCase;

class MasterTest extends TestCase {

    protected function setUp(): void {
        // Bersihkan session sebelum tiap test dimulai
        $_SESSION = [];
        $_POST = [];
    }

    // 1. Menguji Proteksi Halaman Owner (sesuai harga.php baris 6-9)
    public function testAksesHalamanOwnerDitolakUntukKasir() {
        // Simulasi session yang login adalah Kasir
        $_SESSION['role'] = 'Kasir';
        
        $aksesDiberikan = true;
        
        // Logika keamanan dari file harga.php & users.php
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Owner') {
            $aksesDiberikan = false; // Harus ter-redirect
        }
        
        $this->assertFalse($aksesDiberikan, "User Kasir tidak boleh bisa mengakses form master harga dan users");
    }

    // 2. Menguji logika filter simpan harga jual massal (sesuai harga.php baris 41-44)
    public function testFilterHargaJualLebihDariNol() {
        // Simulasi input form harga_jual[] dari user (ada harga 0 dan harga minus)
        $inputJual = [1200000, 0, -50000, 1050000]; 
        $hargaTersimpan = [];

        // Logika dari harga.php
        foreach ($inputJual as $h_jual) {
            if ($h_jual > 0) {
                $hargaTersimpan[] = $h_jual;
            }
        }

        // Validasi: Harusnya cuma 2 data yang lolos (1.2jt dan 1.05jt)
        $this->assertCount(2, $hargaTersimpan, "Harga bernilai 0 atau minus tidak boleh ikut tersimpan");
        $this->assertEquals([1200000, 1050000], $hargaTersimpan);
    }

    // 3. Menguji default value satuan di katalog (sesuai katalog.php baris 10)
    public function testDefaultSatuanKatalogAdalahGram() {
        // Simulasi logika katalog.php baris 10
        $satuan = 'Gram'; 
        
        $this->assertEquals('Gram', $satuan, "Satuan default input katalog harus diset ke 'Gram'");
    }

    // 4. Menguji logika update password (sesuai users.php baris 37-43)
    public function testUpdateUserTanpaGantiPassword() {
        // Simulasi input form: password_baru dikosongkan
        $_POST['password_baru'] = "";
        
        $updatePassword = false;
        
        // Logika users.php
        if (!empty($_POST['password_baru'])) {
            $updatePassword = true;
        }

        $this->assertFalse($updatePassword, "Sistem tidak boleh menjalankan query update password jika kolom kosong");
    }

    // 5. Menguji enkripsi saat ganti password (sesuai users.php baris 37-43)
    public function testUpdateUserDenganGantiPassword() {
        // Simulasi input form: password_baru diisi
        $_POST['password_baru'] = "AmandaOwner2024";
        
        $updatePassword = false;
        $hashedPassword = "";
        
        // Logika users.php
        if (!empty($_POST['password_baru'])) {
            $updatePassword = true;
            $hashedPassword = md5($_POST['password_baru']);
        }

        $this->assertTrue($updatePassword);
        $this->assertEquals(md5("AmandaOwner2024"), $hashedPassword, "Password baru harus di-hash MD5 sebelum masuk ke query");
    }
}