<?php
// 1. MULAI SESI JIKA BELUM DIMULAI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. GEMBOK KEAMANAN UTAMA (Wajib Login)
if (!isset($_SESSION['role'])) {
    // KEMBALI MENGGUNAKAN .php
    header("Location: ../auth/login.php");
    exit; 
}

// 3. PENGHAPUS JEJAK CACHE PHP (Lapis Pertama)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title>Dashboard Toko Emas Amanda</title>
    
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd; 
            --bg-content: #F5F7FA;   
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-content);
            font-size: 0.95rem;
            margin: 0; 
        }

        /* =========================================
           GLOBAL SPINNER OVERLAY (SMART LOADING)
           ========================================= */
        #global-spinner-overlay {
            position: fixed;
            top: 0; left: 0; 
            width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 999999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            
            /* DEFAULT: Disembunyikan total */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        /* Class eksekusi untuk memunculkan spinner */
        #global-spinner-overlay.show-spinner {
            opacity: 1;
            visibility: visible;
        }

        /* Animasi Lingkaran Berputar */
        .fin-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #eff6ff; 
            border-top: 4px solid var(--primary-color); 
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.1);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animasi Teks Berkedip Halus */
        .spinner-text {
            font-family: 'Poppins', sans-serif;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            animation: pulseText 1.5s infinite ease-in-out;
        }

        @keyframes pulseText {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* --- KODE SIDEBAR CSS LAMA --- */
        .sidebar {
            width: 260px;
            background: #ffffff;
            min-height: 100vh;
            border-right: 1px solid #eee;
            position: fixed;
            padding: 20px;
        }
        
        .sidebar-logo {
            font-weight: 700;
            font-size: 1.4rem;
            color: #111;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
        }

        .nav-link {
            color: #7d84ab;
            font-weight: 500;
            padding: 12px 15px;
            border-radius: 12px; 
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background-color: #f0f2f5;
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .nav-title {
            font-size: 0.75rem;
            color: #aab0c6;
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .main-content {
            margin-left: 260px; 
            padding: 30px;
        }

        .card-custom {
            border: none;
            border-radius: 20px; 
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03); 
            padding: 20px;
            height: 100%;
        }

        .icon-box {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .bg-light-blue { background: #e7f1ff; color: #0d6efd; }
        .bg-light-red { background: #ffe7e7; color: #dc3545; }
        .bg-light-green { background: #e7fff0; color: #198754; }
        .bg-light-orange { background: #fff4e7; color: #fd7e14; }

    </style>
</head>
<body>

    <div id="global-spinner-overlay">
        <div class="fin-spinner"></div>
        <div class="spinner-text">Memuat sistem...</div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const overlay = document.getElementById("global-spinner-overlay");
            let spinnerTimeout;

            // 1. Animasi saat KLIK LINK (Logika Natural/Jujur)
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    const target = this.getAttribute('target');

                    // Abaikan jika link kosong, link anchor (#), javascript, atau buka di tab baru (_blank)
                    if (!href || href.startsWith('#') || href.startsWith('javascript:') || target === '_blank') {
                        return;
                    }

                    // Tunda pemunculan spinner selama 200ms.
                    // Jika halaman berpindah sangat cepat (kurang dari 200ms), spinner tidak akan pernah muncul!
                    spinnerTimeout = setTimeout(() => {
                        overlay.classList.add("show-spinner");
                    }, 200); 
                });
            });

            // 2. Mengatasi Bug Cache Browser (Tombol Back/Forward Browser)
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    // Batalkan timer jika nyangkut, sembunyikan spinner, lalu muat ulang
                    clearTimeout(spinnerTimeout);
                    overlay.classList.remove("show-spinner");
                    window.location.reload(); 
                }
            });
        });
    </script>