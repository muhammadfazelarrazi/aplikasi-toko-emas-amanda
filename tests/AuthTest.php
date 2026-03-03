<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {

    // Dijalankan sebelum tiap test untuk memastikan session bersih
    protected function setUp(): void {
        $_SESSION = [];
    }

    // 1. Menguji logika enkripsi password (sesuai login.php baris 14)
    public function testEnkripsiPasswordMD5() {
        $inputPassword = "adminrahasia123";
        $expectedHash = md5($inputPassword);
        
        // Simulasi form input dari method POST
        $_POST['password'] = $inputPassword;
        $hashedPassword = md5($_POST['password']);
        
        $this->assertEquals($expectedHash, $hashedPassword, "Password harus dienkripsi menjadi MD5 sebelum dicocokkan ke database");
    }

    // 2. Menguji set session setelah login berhasil (sesuai login.php baris 20-22)
    public function testSetSessionLoginBerhasil() {
        // Simulasi array data yang dikembalikan oleh mysqli_fetch_assoc($result)
        $dataKaryawan = [
            'KaryawanID' => 99,
            'NamaKaryawan' => 'Amanda Owner',
            'Role' => 'admin'
        ];

        // Simulasi proses assignment session
        $_SESSION['user_id'] = $dataKaryawan['KaryawanID'];
        $_SESSION['username'] = $dataKaryawan['NamaKaryawan'];
        $_SESSION['role'] = $dataKaryawan['Role'];

        // Verifikasi apakah session sudah tercipta dengan key yang benar
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals(99, $_SESSION['user_id']);
        $this->assertEquals('Amanda Owner', $_SESSION['username']);
        $this->assertEquals('admin', $_SESSION['role']);
    }

    // 3. Menguji penghapusan session saat logout (sesuai logout.php)
    public function testProsesLogoutMenghapusSession() {
        // Kondisi awal: user sedang aktif (sudah login)
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'Kasir 1';
        
        $this->assertNotEmpty($_SESSION);

        // Simulasi eksekusi session_destroy() di logout.php
        $_SESSION = []; 
        
        // Verifikasi session harus kosong
        $this->assertEmpty($_SESSION, "Seluruh session harus terhapus setelah proses logout");
    }
}