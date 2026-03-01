<?php
// FILE: library/mailer.php (Versi HTML Email + Link Aman)

// 1. PANGGIL LIBRARY PHPMAILER
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function kirimSuratEmas($emailPenerima, $idTransaksi, $namaPelanggan) {
    
    global $koneksi; 
    if(!$koneksi) { include __DIR__ . '/../config/database.php'; }
    global $base_url; // Ambil base URL dari config/database.php

    // 1. BUAT TOKEN AMAN (ENKRIPSI BASE64)
    $tokenAman = base64_encode($idTransaksi . "|" . time()); 
    $linkSurat = $base_url . "modules/transaksi/surat_email.php?token=" . urlencode($tokenAman);

    // 2. AMBIL DATA HEADER
    $qHead = mysqli_query($koneksi, "SELECT t.*, p.NamaPelanggan, k.NamaKaryawan FROM transaksi t JOIN pelanggan p ON t.PelangganID = p.PelangganID JOIN karyawan k ON t.KaryawanID = k.KaryawanID WHERE t.TransaksiID = '$idTransaksi'");
    $header = mysqli_fetch_assoc($qHead);

    // 3. SUSUN EMAIL (Simple HTML + Inline CSS)
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // --- KREDENSIAL (TIDAK BERUBAH) ---
        $mail->Username   = 'emasamandatoko@gmail.com';
        $mail->Password   = 'zmcf awgn llna vpbg';
        // ----------------------------------

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

        $mail->setFrom('emasamandatoko@gmail.com', 'Toko Emas Amanda');
        $mail->addAddress($emailPenerima, $namaPelanggan);

        $mail->isHTML(true);
        $mail->Subject = 'Surat Emas Digital Anda - #' . $idTransaksi;
        
        $bodyContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; margin: auto;'>
                <div style='background-color: #0d6efd; padding: 20px; text-align: center; color: white;'>
                    <h2 style='margin: 0; font-size: 1.5em;'>TOKO EMAS AMANDA</h2>
                    <p style='margin: 5px 0 0 0; font-size: 0.8em;'>Bukti Transaksi Resmi Digital</p>
                </div>

                <div style='padding: 30px; background-color: #ffffff;'>
                    <h3 style='color: #333; margin-top: 0;'>Halo, $namaPelanggan!</h3>
                    <p style='color: #555; line-height: 1.6;'>Transaksi Anda dengan nomor <b>#TRX-$idTransaksi</b> berhasil dicatat.</p>

                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>
                        <p style='margin: 5px 0; font-size: 0.9em; color: #333;'>
                            <b>Total Bayar:</b> Rp " . number_format($header['TotalTransaksi'], 0, ',', '.') . "
                        </p>
                        <p style='margin: 5px 0; font-size: 0.9em; color: #555;'>
                            Tanggal: " . date('d F Y H:i', strtotime($header['TanggalWaktu'])) . " WIB
                        </p>
                    </div>

                    <p style='margin-top: 20px; color: #555;'>Untuk melihat detail item, kadar, berat, dan menyimpan salinan resmi surat jaminan Anda, silakan klik tautan di bawah ini:</p>
                    
                    <table role='presentation' cellspacing='0' cellpadding='0' border='0' align='center' style='margin-top: 25px;'>
                        <tr>
                            <td style='border-radius: 50px; background: #0d6efd; text-align: center;'>
                                <a href='$linkSurat' target='_blank' style='background: #0d6efd; border: 1px solid #0d6efd; font-size: 15px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; padding: 10px 25px; border-radius: 50px; display: inline-block; font-weight: bold;'>
                                    LIHAT SURAT LENGKAP
                                </a>
                            </td>
                        </tr>
                    </table>
                    
                    <p style='margin-top: 20px; font-size: 0.8em; color: #888;'><i>Link ini hanya berlaku untuk satu kali akses keamanan.</i></p>
                </div>
            </div>
        ";
        
        $mail->Body = $bodyContent;
        $mail->AltBody = "Transaksi berhasil! Gunakan link ini untuk melihat surat emas: $linkSurat";

        $mail->send();
        return true;

    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/email_error_log.txt', date('Y-m-d H:i:s') . " Error: " . $mail->ErrorInfo . "\n", FILE_APPEND);
        return false;
    }
}
?>