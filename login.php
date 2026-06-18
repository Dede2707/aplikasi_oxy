<?php
// Pastikan session dimulai di paling atas file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "koneksi.php";

if (isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
if (isset($_POST['pelanggan'])) {
    header("Location: order_pelanggan.php");
    exit();
}

// --- OTOMATIS MENCARI TABEL LOGIN YANG ADA ---
$tabel_valid = "";
$kemungkinan_tabel = ['admin', 'user', 'users', 'tbl_user', 'tbl_admin', 'tb_user', 'tb_admin', 'pengguna'];

foreach ($kemungkinan_tabel as $bukan_tabel) {
    $cek = mysqli_query($koneksi, "SHOW TABLES LIKE '$bukan_tabel'");
    if (mysqli_num_rows($cek) > 0) {
        $tabel_valid = $bukan_tabel;
        break;
    }
}

if (empty($tabel_valid)) {
    echo "<div style='background:#fff; color:#000; padding:25px; border:3px solid #dc3545; font-family:sans-serif; max-width:500px; margin:50px auto; border-radius:10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>";
    echo "<h3 style='color:#dc3545; margin-top:0;'><i class='fa-solid fa-triangle-exclamation'></i> Tabel Login Tidak Terdeteksi!</h3>";
    echo "<p>Sistem tidak menemukan tabel bernama <code>admin</code> atau <code>user</code> di database <b>db_oxy</b> Anda.</p>";
    echo "<p>Berikut adalah daftar seluruh tabel yang <b>AKTIF</b> di database Anda saat ini:</p>";
    echo "<table border='1' style='border-collapse:collapse; width:100%; text-align:left;'>";
    echo "<tr style='background:#f4f4f4;'><th style='padding:8px;'>Nama Tabel di Database Anda</th></tr>";

    $ambil_semua_tabel = mysqli_query($koneksi, "SHOW TABLES");
    while ($t = mysqli_fetch_array($ambil_semua_tabel)) {
        echo "<tr><td style='padding:8px; font-weight:bold; color:#0d6efd;'>" . $t[0] . "</td></tr>";
    }

    echo "</table><br><p style='font-size:13px; color:#666;'>*Silakan beri tahu saya nama tabel mana yang digunakan untuk menyimpan data Username & Password Admin Anda.</p></div>";
    exit();
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM `$tabel_valid`");
    $col_user = 'username';
    $col_pass = 'password';
    while ($c = mysqli_fetch_assoc($cek_kolom)) {
        $f = strtolower($c['Field']);
        if (in_array($f, ['username', 'user', 'nama_user', 'email', 'id_user'])) $col_user = $c['Field'];
        if (in_array($f, ['password', 'pass', 'sandi'])) $col_pass = $c['Field'];
    }

    $query = mysqli_query($koneksi, "SELECT * FROM `$tabel_valid` WHERE `$col_user`='$username' AND (`$col_pass`='$password' OR `$col_pass`='" . md5($password) . "')");

    if ($query && mysqli_num_rows($query) === 1) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['admin'] = $data;
        echo "<script>alert('Login Berhasil! Selamat Datang.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Username atau Password salah!'); window.location.href='login.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Oxywater App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            /* 1. GANTI URL DI BAWAH INI DENGAN GAMBAR ANDA */
            background-image: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), url('https://images.unsplash.com/photo-1562016600-ece13e8ba570');
            background-size: cover;
            /* Gambar otomatis memenuhi layar */
            background-position: center;
            /* Gambar selalu di tengah */
            background-repeat: no-repeat;
            /* Gambar tidak mengulang */
            background-attachment: fixed;
            /* Background mengunci tidak ikut bergeser */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Desain kartu login transparan (Glassmorphism) */
        .card-login {
            background: rgba(255, 255, 255, 0.12);
            /* Transparansi putih latar belakang */
            backdrop-filter: blur(12px);
            /* Efek blur kaca di belakang kartu */
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            /* Garis tepi tipis transparan */
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        /* Penyesuaian warna teks agar kontras dengan gambar latar belakang */
        .text-light-custom {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            /* Sedikit bayangan teks agar makin terbaca */
        }

        .text-muted-custom {
            color: #f0f0f0 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Membuat form input sedikit transparan */
        .form-control-custom {
            background: rgba(255, 255, 255, 0.9) !important;
            border: none;
        }

        .input-group-text-custom {
            background: rgba(255, 255, 255, 0.8) !important;
            border: none;
            color: #333 !important;
        }

        /* Tombol masuk kustom */
        .btn-custom {
            background: #ffffff;
            color: #2b5876;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background: #2b5876;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body>
    <div class="card card-login p-4">
        <div class="text-center my-3">
            <h3 class="fw-bold text-light-custom"><i class="fa-solid fa-droplet me-2 text-info"></i>Oxywater App</h3>
            <p class="text-muted-custom small">Silakan masuk aplikasi</p>
        </div>
        <form action="" method="POST" class="mt-2">
            <div class="mb-3">
                <label class="form-label fw-semibold text-light-custom">Username</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control form-control-custom" placeholder="Masukkan username Anda" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold text-light-custom">Password</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control form-control-custom" placeholder="Masukkan password Anda" required>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-custom w-100 py-2 fw-bold rounded-3 shadow-sm">
                </i>Masuk
            </button><br><br>
            <a href="order_pelanggan.php" class="btn btn-custom w-100 py-2 fw-bold rounded-3 shadow-sm">
                Order Now
            </a>
        </form>
    </div>
</body>

</html>