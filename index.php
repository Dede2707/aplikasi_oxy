<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Menyertakan file koneksi database di awal agar bisa dipakai untuk registrasi otomatis
require_once 'koneksi.php';

// =========================================================================
// SCRIPT OTOMATIS: MEMBUAT AKUN ADMIN JIKA BELUM ADA DI DATABASE (FIXED NAMA_LENGKAP)
// =========================================================================
$check_admin = mysqli_query($koneksi, "SELECT * FROM users WHERE LOWER(level) = 'admin' LIMIT 1");
if ($check_admin && mysqli_num_rows($check_admin) == 0) {
    $username_auto = 'admin';
    $password_auto = md5('admin123');
    $nama_auto     = 'Administrator Utama';
    $level_auto    = 'Admin';

    mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, level) VALUES ('$username_auto', '$password_auto', '$nama_auto', '$level_auto')");
}
// =========================================================================

// Proteksi Keamanan: Jika tidak ada session admin (belum login), paksa tendang balik ke halaman login.php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Ambil data level hak akses user dan paksa jadi huruf kecil semua agar tidak sensitif huruf besar/kecil
$level_user_raw = $_SESSION['admin']['level'] ?? '';
$level_user     = strtolower(trim($level_user_raw));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxywater Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @media (min-width: 992px) {
            .sidebar-sticky {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 100;
                width: 260px;
                overflow-y: auto;
            }

            .main-content-offset {
                margin-left: 260px;
            }
        }

        .nav-link {
            color: #adb5bd;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid p-0">
        <div class="row g-0">

            <nav class="col-12 col-lg-3 col-xl-2 bg-dark text-white sidebar-sticky p-3">
                <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-droplet me-2"></i>Oxywater App</h5>
                        <small class="text-muted d-block" style="font-size: 11px;">PT. NANOPLEX INDONESIA</small>
                        <span class="badge bg-secondary mt-1" style="font-size: 10px;"><?= htmlspecialchars($level_user_raw); ?></span>
                    </div>
                    <button class="btn btn-outline-light d-lg-none p-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>

                <div class="collapse d-lg-block" id="menuUtama">
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item">
                            <a href="index.php?page=dashboard" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
                                <i class="fa-solid fa-chart-pie me-3"></i>Dashboard
                            </a>
                        </li>

                        <?php if ($level_user === 'admin' || $level_user === 'staff kasir' || $level_user === 'kasir'): ?>
                            <li class="nav-item">
                                <a href="index.php?page=penjualan" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'penjualan' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-cart-shopping me-3"></i>Data Penjualan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="index.php?page=pelanggan_loyal" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'pelanggan_loyal' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-crown text-warning me-3"></i>Pelanggan Loyal
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($level_user === 'admin' || $level_user === 'gudang'): ?>
                            <li class="nav-item">
                                <a href="index.php?page=stok" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'stok' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-boxes-stacked me-3"></i>Stok Penjualan
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($level_user === 'admin' || $level_user === 'staff kasir' || $level_user === 'kasir'): ?>
                            <li class="nav-item">
                                <a href="index.php?page=retur" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'retur' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-right-left me-3"></i>Retur Produk
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($level_user === 'admin' || $level_user === 'gudang'): ?>
                            <li class="nav-item">
                                <a href="index.php?page=laporan" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'laporan' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-file-invoice-dollar me-3"></i>Laporan Retur
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($level_user === 'admin'): ?>
                            <li class="nav-item">
                                <a href="index.php?page=users" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'users' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-user-gear me-3"></i>Manajemen User
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="index.php?page=rekening" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'rekening' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-credit-card me-3"></i>Rekening Perusahaan
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item mt-4 border-top pt-3">
                            <a href="index.php?page=logout" class="nav-link text-danger px-3 py-2.5">
                                <i class="fa-solid fa-right-from-bracket me-3"></i>Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-12 col-lg-9 col-xl-10 main-content-offset p-3 p-md-4">
                <?php
                $page = $_GET['page'] ?? 'dashboard';

                // --- PROTEKSI DAN ROUTING HALAMAN ---
                switch ($page) {
                    case 'penjualan':
                        if ($level_user !== 'admin' && $level_user !== 'staff kasir' && $level_user !== 'kasir') {
                            echo "<script>alert('Akses Ditolak! Menu ini hanya untuk Admin / Staff Kasir.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'penjualan.php';
                        break;

                    case 'pelanggan_loyal':
                        if ($level_user !== 'admin' && $level_user !== 'staff kasir' && $level_user !== 'kasir') {
                            echo "<script>alert('Akses Ditolak! Menu ini hanya untuk Admin / Staff Kasir.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'pelanggan_loyal.php';
                        break;

                    case 'stok':
                        if ($level_user !== 'admin' && $level_user !== 'gudang') {
                            echo "<script>alert('Akses Ditolak! Menu ini hanya untuk Admin / Staff Gudang.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'stok.php';
                        break;

                    case 'retur':
                        if ($level_user !== 'admin' && $level_user !== 'staff kasir' && $level_user !== 'kasir') {
                            echo "<script>alert('Akses Ditolak! Menu ini hanya untuk Admin / Staff Kasir.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'retur.php';
                        break;

                    case 'laporan':
                        if ($level_user !== 'admin' && $level_user !== 'gudang') {
                            echo "<script>alert('Akses Ditolak! Menu ini hanya untuk Admin / Staff Gudang.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'laporan.php';
                        break;

                    case 'users':
                        if ($level_user !== 'admin') {
                            echo "<script>alert('Akses Ditolak! Manajemen User dikunci khusus Admin.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'users.php';
                        break;

                    case 'rekening':
                        if ($level_user !== 'admin') {
                            echo "<script>alert('Akses Ditolak! Pengaturan Rekening dikunci khusus Admin.'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'rekening.php';
                        break;

                    case 'logout':
                        include 'logout.php';
                        break;

                    case 'dashboard':
                    default:
                        // ==========================================
                        // ---- DATA GRAFIK 1: 7 DATA TERAKHIR ------
                        // ==========================================
                        $label_tanggal = [];
                        $data_jumlah = [];
                        $sql_chart = "SELECT tgl_retur, SUM(jumlah) as total_qty FROM retur GROUP BY tgl_retur ORDER BY tgl_retur ASC LIMIT 7";
                        $query_chart = mysqli_query($koneksi, $sql_chart);
                        if ($query_chart) {
                            while ($row = mysqli_fetch_assoc($query_chart)) {
                                $label_tanggal[] = date('d/m', strtotime($row['tgl_retur']));
                                $data_jumlah[]   = (int)$row['total_qty'];
                            }
                        }

                        // ==========================================
                        // ---- DATA GRAFIK 2: GRAFIK BULANAN -------
                        // ==========================================
                        $bulan_labels = [];
                        $bulan_data = [];
                        $tahun_sekarang = date('Y');
                        $sql_bulan = "SELECT MONTH(tgl_retur) as bulan, SUM(jumlah) as total_qty FROM retur WHERE YEAR(tgl_retur) = '$tahun_sekarang' GROUP BY MONTH(tgl_retur) ORDER BY MONTH(tgl_retur) ASC";
                        $query_bulan = mysqli_query($koneksi, $sql_bulan);
                        $nama_bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                        if ($query_bulan) {
                            while ($row = mysqli_fetch_assoc($query_bulan)) {
                                $bulan_labels[] = $nama_bulan[$row['bulan']];
                                $bulan_data[]   = (int)$row['total_qty'];
                            }
                        }

                        // ==========================================
                        // ---- DATA GRAFIK 3: GRAFIK TAHUNAN -------
                        // ==========================================
                        $tahun_labels = [];
                        $tahun_data = [];
                        $sql_tahun = "SELECT YEAR(tgl_retur) as tahun, SUM(jumlah) as total_qty FROM retur GROUP BY YEAR(tgl_retur) ORDER BY YEAR(tgl_retur) ASC LIMIT 5";
                        $query_tahun = mysqli_query($koneksi, $sql_tahun);
                        if ($query_tahun) {
                            while ($row = mysqli_fetch_assoc($query_tahun)) {
                                $tahun_labels[] = $row['tahun'];
                                $tahun_data[]   = (int)$row['total_qty'];
                            }
                        }

                        // Backup jika database kosong
                        if (empty($label_tanggal)) {
                            $label_tanggal = ['Belum Ada'];
                            $data_jumlah = [0];
                        }
                        if (empty($bulan_labels)) {
                            $bulan_labels = ['Belum Ada'];
                            $bulan_data = [0];
                        }
                        if (empty($tahun_labels)) {
                            $tahun_labels = [date('Y')];
                            $tahun_data = [0];
                        }
                ?>

                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm p-4">
                                    <h5 class="fw-bold text-dark mb-1">
                                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>Dashboard Utama
                                    </h5>
                                    <p class="text-muted small mb-0">Selamat datang di Oxywater Management System, PT. Nanoplex Indonesia.</p>
                                </div>
                            </div>

                            <div class="col-12 col-xl-8">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-3 p-sm-4">
                                        <h6 class="fw-bold text-dark mb-3">
                                            <i class="fa-solid fa-chart-line text-success me-2"></i>Tren Retur Produk (7 Data Terakhir)
                                        </h6>
                                        <div style="position: relative; height: 300px; width: 100%;">
                                            <canvas id="chartReturUtama"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-3 p-sm-4 d-flex flex-column justify-content-between">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body p-3 p-sm-4 d-flex flex-column justify-content-between">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-3">Laporan Tabel Berkala</h6>
                                                    <p class="text-muted small">Ingin melihat rincian angka laporan retur bulanan (Januari - Desember) dan tahunan dari tahun aktif ke depan?</p>
                                                </div>
                                                <?php if ($level_user === 'admin' || $level_user === 'gudang'): ?>
                                                    <a href="index.php?page=laporan&tahun=<?= $tahun_sekarang ?>" class="btn btn-success w-100 fw-semibold py-2">
                                                        <i class="fa-solid fa-file-invoice-dollar me-2"></i>Buka Tabel Laporan
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary w-100 fw-semibold py-2" disabled>
                                                        <i class="fa-solid fa-lock me-2"></i>Laporan Terkunci
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Grafik Retur Bulanan (<?= $tahun_sekarang ?>)</h6>
                                        <div style="position: relative; height: 260px;"><canvas id="chartBulanan"></canvas></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-minus text-warning me-2"></i>Grafik Retur Tahunan</h6>
                                        <div style="position: relative; height: 260px;"><canvas id="chartTahunan"></canvas></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            new Chart(document.getElementById('chartReturUtama').getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: <?= json_encode($label_tanggal); ?>,
                                    datasets: [{
                                        label: 'Jumlah Produk Diretur (Qty)',
                                        data: <?= json_encode($data_jumlah); ?>,
                                        borderColor: '#0d6efd',
                                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                        borderWidth: 3,
                                        tension: 0.3,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            });

                            new Chart(document.getElementById('chartBulanan').getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: <?= json_encode($bulan_labels); ?>,
                                    datasets: [{
                                        label: 'Total Qty Retur',
                                        data: <?= json_encode($bulan_data); ?>,
                                        backgroundColor: '#3b82f6',
                                        borderRadius: 4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            });

                            new Chart(document.getElementById('chartTahunan').getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: <?= json_encode($tahun_labels); ?>,
                                    datasets: [{
                                        label: 'Total Qty Retur',
                                        data: <?= json_encode($tahun_data); ?>,
                                        borderColor: '#f59e0b',
                                        tension: 0.2,
                                        fill: false
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            });
                        </script>
                <?php
                        break;
                }
                ?>
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>