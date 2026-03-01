<?php
session_start();
// Pastikan path database benar (mundur 2 langkah dari modules/auth)
include '../../config/database.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

// Proses Login
$error = "";
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']); 

    $query = "SELECT * FROM karyawan WHERE Username='$username' AND Password='$password'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $data['KaryawanID'];
        $_SESSION['username'] = $data['NamaKaryawan'];
        $_SESSION['role'] = $data['Role']; 

        header("Location: ../dashboard/index.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Emas Amanda</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eef5ff; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* CONTAINER CARD UTAMA */
        .card-login {
            background-color: #fff;
            width: 100%;
            max-width: 950px; 
            min-height: 600px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.05); 
            overflow: hidden; 
            display: flex;
        }

        /* BAGIAN KIRI (CONTAINER UNTUK GAMBAR) */
        .left-side {
            width: 50%;
            padding: 15px; 
            display: flex;
            background-color: #fff;
        }

        /* KOTAK GAMBAR ROUNDED */
        .left-image-box {
            width: 100%;
            height: 100%; 
            border-radius: 15px; 
            
            /* --- PATH GAMBAR LOKAL --- */
            /* Pastikan file login.png ada di folder assets/img/ */
            background-image: url('../../assets/img/login.png');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover; 
            
            /* Warna cadangan jika gambar tidak termuat */
            background-color: #f0f0f0; 
            
            overflow: hidden;
            /* Flex dan padding dihapus karena tidak ada teks lagi */
        }

        /* Overlay gradient dihapus sesuai permintaan agar gambar terlihat jelas */

        /* BAGIAN KANAN (FORM) */
        .right-side {
            width: 50%;
            padding: 50px; 
            display: flex;
            flex-direction: column;
            justify-content: center; 
            background-color: #ffffff;
            position: relative;
        }

        /* ALERT MELAYANG */
        .alert-floating {
            position: absolute;
            top: 20px;
            left: 40px;
            right: 40px;
            z-index: 100;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
            animation: fadeInDown 0.4s ease-out;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Styling Input */
        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            font-size: 14px;
            background-color: #fff;
            height: 48px; 
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .input-group-custom { position: relative; }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%); 
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            font-size: 18px;
        }

        .btn-primary {
            background-color: #0d6efd; 
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            height: 48px; 
            margin-top: 10px; 
            transition: 0.3s;
        }
        .btn-primary:hover { background-color: #0b5ed7; }

        /* Responsif HP */
        @media (max-width: 768px) {
            .card-login { flex-direction: column; height: auto; }
            .left-side { width: 100%; height: 300px; padding: 15px; } 
            .right-side { width: 100%; padding: 40px 30px; }
        }
    </style>
</head>
<body>

    <div class="card-login">
        
        <div class="left-side">
            <div class="left-image-box">
                </div>
        </div>

        <div class="right-side">
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-floating py-2 small rounded-3 mb-0 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i> 
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <div class="mb-5"> 
                <h2 class="fw-bold mb-1 text-dark">Selamat Datang</h2>
                <p class="text-muted small">Silakan login untuk mengakses dashboard.</p>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>

                <div class="mb-4"> 
                    <label class="form-label">Password</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Masukkan password" required>
                        <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary shadow-sm">Masuk Dashboard</button>
            </form>

            <div class="text-center mt-5"> 
                <p class="small text-muted mb-1">Butuh bantuan akses?</p>
                <a href="https://wa.me/6281234567890" target="_blank" class="text-decoration-none fw-bold text-dark d-inline-flex align-items-center">
                    <i class="bi bi-whatsapp text-success me-2 fs-5"></i> Hubungi Owner
                </a>
            </div>

        </div>

    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#passwordInput');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>

</body>
</html>