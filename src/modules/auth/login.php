<?php
session_start();
// Pastikan path database benar (mundur 2 langkah dari modules/auth)
include '../../config/database.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

// --- PROSES LOGIN ASINKRON (AJAX) ---
if (isset($_POST['ajax_login'])) {
    header('Content-Type: application/json');
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']); 

    $query = "SELECT * FROM karyawan WHERE Username='$username' AND Password='$password'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $data['KaryawanID'];
        $_SESSION['username'] = $data['NamaKaryawan'];
        $_SESSION['role'] = $data['Role']; 
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kredensial tidak valid. Periksa kembali Username dan Password Anda.']);
    }
    exit; // Hentikan script agar tidak merender HTML di bawahnya
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Emas Amanda</title>
    
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* =========================================
           GLOBAL UI: FINPAY PREMIUM DESIGN SYSTEM
           ========================================= */
        :root {
            --font-sf: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Helvetica, Arial, sans-serif;
            --fin-bg: #f8fafc;
            --fin-surface: #ffffff;
            --fin-primary: #3b82f6;
            --fin-primary-dark: #2563eb;
            --fin-success: #10b981;
            --fin-danger: #ef4444;
            --fin-text-main: #0f172a;
            --fin-text-muted: #64748b;
            --fin-border: #e2e8f0;
            --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.04);
            --shadow-focus: 0 0 0 4px rgba(59, 130, 246, 0.15);
            --shadow-danger: 0 0 0 4px rgba(239, 68, 68, 0.15);
        }

        body {
            font-family: var(--font-sf);
            background-color: var(--fin-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.2px;
            overflow-x: hidden;
        }

        /* --- ANIMASI HALAMAN & CHECKLIST --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        @keyframes popCheck {
            0% { transform: scale(0); opacity: 0; }
            80% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .check-anim { animation: popCheck 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }

        /* --- ANIMASI TOMBOL GETAR (SHAKE) --- */
        @keyframes buttonShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        .btn-shake { animation: buttonShake 0.4s ease-in-out; }

        /* --- LOADING BAR --- */
        #page-loader-bar {
            position: fixed; top: 0; left: 0; height: 4px; 
            background: var(--fin-primary);
            z-index: 9999; width: 0%; opacity: 0;
            transition: width 0.4s ease-out, opacity 0.3s ease, background-color 0.3s ease;
        }
        .loader-success { background-color: var(--fin-success) !important; }

        /* CONTAINER CARD UTAMA */
        .card-login {
            background-color: var(--fin-surface);
            width: 100%;
            max-width: 1000px; 
            min-height: 600px; 
            border-radius: 24px; 
            box-shadow: var(--shadow-soft); 
            border: 1px solid rgba(0,0,0,0.02);
            display: flex;
            overflow: hidden;
            opacity: 0; /* Untuk animasi awal */
        }

        /* BAGIAN KIRI (GAMBAR LOKAL) */
        .left-side {
            width: 50%;
            padding: 16px; 
            display: flex;
            background-color: var(--fin-surface);
        }

        .left-image-box {
            width: 100%;
            height: 100%; 
            border-radius: 20px; 
            background-image: url('../../assets/img/login.png');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover; 
            background-color: #f1f5f9; /* Cadangan */
            position: relative;
        }

        .left-image-box::after {
            content: ''; position: absolute; inset: 0;
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(0,0,0,0) 50%, rgba(0,0,0,0.1) 100%);
        }

        /* BAGIAN KANAN (FORM) */
        .right-side {
            width: 50%;
            padding: 40px 60px; 
            display: flex;
            flex-direction: column;
            justify-content: center; 
            background-color: var(--fin-surface);
            position: relative;
        }

        /* TIPOGRAFI LOGIN */
        .login-title { font-size: 1.75rem; font-weight: 800; color: var(--fin-text-main); letter-spacing: -0.5px; margin-bottom: 8px; }
        .login-subtitle { font-size: 0.95rem; font-weight: 500; color: var(--fin-text-muted); margin-bottom: 32px; }

        /* STYLING INPUT FORM PREMIUM */
        .form-label {
            font-weight: 700; font-size: 0.75rem; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 24px;
        }

        .input-icon-left {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 1.1rem; z-index: 10; pointer-events: none;
            transition: color 0.3s;
        }

        .form-control {
            border-radius: 12px;
            padding: 14px 16px 14px 44px;
            border: 1px solid var(--fin-border);
            font-size: 0.95rem; font-weight: 600; color: var(--fin-text-main);
            background-color: #f8fafc;
            height: 52px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-control::placeholder { color: #cbd5e1; font-weight: 500; }
        
        .form-control:focus {
            background-color: var(--fin-surface);
            border-color: var(--fin-primary);
            box-shadow: var(--shadow-focus);
        }
        .form-control:focus ~ .input-icon-left { color: var(--fin-primary); }

        /* ERROR STATE INPUT */
        .input-error {
            border-color: var(--fin-danger) !important;
            box-shadow: var(--shadow-danger) !important;
            background-color: #fef2f2 !important;
        }
        .input-error ~ .input-icon-left { color: var(--fin-danger) !important; }

        .password-toggle {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%); 
            cursor: pointer; color: #94a3b8; z-index: 10; font-size: 1.1rem;
            transition: color 0.2s; padding: 4px;
        }
        .password-toggle:hover { color: var(--fin-text-main); }

        /* TOMBOL LOGIN */
        .btn-fin-primary {
            background-color: var(--fin-primary); 
            color: #ffffff; border: none; border-radius: 12px;
            padding: 14px; font-weight: 700; font-size: 0.95rem;
            width: 100%; height: 52px; margin-top: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; justify-content: center; align-items: center; gap: 8px;
            position: relative; overflow: hidden;
        }
        .btn-fin-primary:hover:not(:disabled) {
            background-color: var(--fin-primary-dark); 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }
        .btn-fin-primary:active:not(:disabled) { transform: translateY(0); }
        .btn-fin-primary:disabled { opacity: 0.8; cursor: not-allowed; }

        /* TOAST ALERT ERROR */
        .custom-toast { 
            position: fixed; top: 24px; left: 50%; transform: translate(-50%, -20px); 
            opacity: 0; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); z-index: 10001; pointer-events: none;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(225, 29, 72, 0.15); border: 1px solid #fecaca;
            border-radius: 16px; padding: 12px 20px; display: flex; align-items: center; gap: 12px;
        }
        .custom-toast.show { transform: translate(-50%, 0); opacity: 1; }

        /* HELPER FOOTER */
        .help-box {
            margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--fin-border);
            text-align: center;
        }

        /* RESPONSIF */
        @media (max-width: 992px) { .right-side { padding: 40px; } }
        @media (max-width: 768px) {
            body { padding: 16px; align-items: flex-start; }
            .card-login { flex-direction: column; min-height: auto; }
            .left-side { width: 100%; height: 260px; padding: 12px; } 
            .right-side { width: 100%; padding: 32px 24px; }
        }
    </style>
</head>
<body>

    <div id="page-loader-bar"></div>

    <div id="toastError" class="custom-toast">
        <div class="rounded-full d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #fef2f2; border: 1px solid #fee2e2;">
            <i class="bi bi-shield-lock-fill text-danger fs-6"></i>
        </div>
        <span id="toastErrorMsg" style="font-size: 0.85rem; font-weight: 700; color: #0f172a; padding-right: 8px;">Gagal!</span>
    </div>

    <div class="card-login animate-fade-in">
        
        <div class="left-side">
            <div class="left-image-box"></div>
        </div>

        <div class="right-side">
            
            <div class="mb-2"> 
                <h2 class="login-title">Selamat Datang</h2>
                <p class="login-subtitle">Silakan masuk ke akun Anda untuk melanjutkan.</p>
            </div>

            <form id="loginForm" novalidate>
                
                <div class="mb-1">
                    <label class="form-label">Username</label>
                    <div class="input-group-custom">
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="username">
                        <i class="bi bi-person-fill input-icon-left"></i>
                    </div>
                </div>

                <div class="mb-2"> 
                    <div class="d-flex justify-content-between align-items-end">
                        <label class="form-label">Password</label>
                    </div>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <i class="bi bi-lock-fill input-icon-left"></i>
                        <i class="bi bi-eye-slash-fill password-toggle" id="togglePassword" title="Tampilkan/Sembunyikan Password"></i>
                    </div>
                </div>
                
                <button type="submit" id="loginBtn" class="btn-fin-primary mt-3 shadow-sm">
                    <span>Log In</span> <i class="bi bi-arrow-right-short fs-5 transition-transform" id="btnIcon"></i>
                </button>

            </form>

            <div class="help-box"> 
                <p class="mb-1" style="font-size: 0.8rem; font-weight: 600; color: #94a3b8;">Lupa Password?</p>
                <a href="https://wa.me/6281234567890" target="_blank" class="text-decoration-none d-inline-flex align-items-center gap-2" style="font-size: 0.9rem; font-weight: 700; color: var(--fin-text-main); transition: color 0.2s;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px; background: #ecfdf5; border: 1px solid #d1fae5;">
                        <i class="bi bi-whatsapp text-success" style="font-size: 0.85rem;"></i>
                    </div>
                    Hubungi Administrator
                </a>
            </div>

        </div>
    </div>

    <script>
        // Fitur Tampil/Sembunyi Password
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#passwordInput');

        togglePassword.addEventListener('click', function (e) {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            this.classList.toggle('bi-eye-fill');
            this.classList.toggle('bi-eye-slash-fill');
        });

        // Fitur Validasi Custom & AJAX Login
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const toastError = document.getElementById('toastError');
        const toastMsg = document.getElementById('toastErrorMsg');
        const loader = document.getElementById('page-loader-bar');
        
        const inputUsername = document.querySelector('input[name="username"]');
        const inputPassword = document.querySelector('input[name="password"]');

        // Menghapus status error saat user mulai mengetik lagi
        inputUsername.addEventListener('input', () => inputUsername.classList.remove('input-error'));
        inputPassword.addEventListener('input', () => inputPassword.classList.remove('input-error'));

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Mencegah form reload bawaan browser
            
            const userVal = inputUsername.value.trim();
            const passVal = inputPassword.value.trim();

            // 1. VALIDASI CUSTOM (JIKA KOSONG)
            if (!userVal || !passVal) {
                // Beri class error warna merah pada input yang kosong
                if (!userVal) inputUsername.classList.add('input-error');
                if (!passVal) inputPassword.classList.add('input-error');

                // Efek Tombol Bergetar (Shake)
                loginBtn.classList.remove('btn-shake');
                void loginBtn.offsetWidth; // Trigger reflow untuk reset animasi
                loginBtn.classList.add('btn-shake');

                // Munculkan Toast Error Premium
                toastMsg.innerText = "Harap lengkapi Username dan Password Anda.";
                toastError.classList.add('show');
                setTimeout(() => { toastError.classList.remove('show'); }, 4000);
                
                return; // Stop proses AJAX
            }

            // 2. STATE: LOADING (Proses Verifikasi)
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memverifikasi...';
            
            // Jalankan Top Loading Bar
            loader.classList.remove('loader-success');
            loader.style.opacity = '1';
            loader.style.width = '40%';
            
            // Ambil data form dan siapkan untuk AJAX
            const formData = new FormData(this);
            formData.append('ajax_login', '1'); // Trigger PHP

            // 3. FETCH DATA KE SERVER
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 4. STATE: SUCCESS (Checklist Reveal)
                    loader.style.width = '100%';
                    loader.classList.add('loader-success');
                    
                    loginBtn.style.backgroundColor = '#10b981'; // Ubah warna tombol jadi hijau
                    loginBtn.style.boxShadow = '0 8px 20px rgba(16, 185, 129, 0.3)';
                    loginBtn.innerHTML = '<i class="bi bi-check-circle-fill fs-5 me-2 check-anim"></i> Berhasil Log In';
                    
                    // KEMBALI MENGGUNAKAN .php
                    setTimeout(() => {
                        window.location.href = '../dashboard/index.php';
                    }, 1000);
                    
                } else {
                    // 5. STATE: ERROR (Kredensial Salah)
                    loader.style.opacity = '0';
                    setTimeout(() => { loader.style.width = '0%'; }, 300); // Reset loading bar

                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<span>Log In</span> <i class="bi bi-arrow-right-short fs-5"></i>';
                    
                    // Highlight input dengan merah (efek salah pass/username)
                    inputUsername.classList.add('input-error');
                    inputPassword.classList.add('input-error');
                    
                    // Getarkan tombol
                    loginBtn.classList.remove('btn-shake');
                    void loginBtn.offsetWidth;
                    loginBtn.classList.add('btn-shake');

                    // Tampilkan Toast Error Premium
                    toastMsg.innerText = data.message;
                    toastError.classList.add('show');
                    setTimeout(() => { toastError.classList.remove('show'); }, 4000);
                }
            })
            .catch(error => {
                // Failsafe jika terjadi error jaringan/server
                loader.style.opacity = '0';
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<span>Log In</span> <i class="bi bi-arrow-right-short fs-5"></i>';
                
                toastMsg.innerText = "Terjadi kesalahan sistem/jaringan. Silakan coba lagi.";
                toastError.classList.add('show');
                setTimeout(() => { toastError.classList.remove('show'); }, 4000);
            });
        });
    </script>

</body>
</html>