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
        /* CSS STRUKTUR UTAMA RESPONSIVE SIDEBAR COLLAPSIBLE */
        #wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
            overflow-x: hidden;
        }

        #sidebar-wrapper {
            min-width: 260px;
            max-width: 260px;
            min-height: 100vh;
            transition: all 0.25s ease-out;
            z-index: 1000;
        }

        #page-content-wrapper {
            width: 100%;
            min-height: 100vh;
            transition: all 0.25s ease-out;
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

        /* LOGIKA TAMPILAN DESKTOP (PC) */
        @media (min-width: 992px) {
            #wrapper.toggled #sidebar-wrapper {
                margin-left: -260px;
                /* Sembunyikan ke kiri saat dicollapse */
            }
        }

        /* LOGIKA TAMPILAN MOBILE (HP) */
        @media (max-width: 991.98px) {
            #sidebar-wrapper {
                margin-left: -260px;
                /* Default bersembunyi di luar layar HP */
                position: fixed;
                height: 100vh;
            }

            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
                /* Muncul bergeser dari kiri saat tombol ditekan */
            }
        }
    </style>
</head>

<body class="bg-light">

    <div id="wrapper">

        <nav id="sidebar-wrapper" class="bg-dark text-white p-3 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-droplet me-2"></i>Oxywater App</h5>
                        <small class="text-muted d-block" style="font-size: 11px;">PT. NANOPLEX INDONESIA</small>
                        <span class="badge bg-secondary mt-1" style="font-size: 10px;"><?= htmlspecialchars($level_user_raw); ?></span>
                    </div>
                    <button class="btn btn-sm btn-outline-light d-lg-none" id="sidebar-close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

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
                        <li class="nav-item">
                            <a class="nav-link text-white <?= ($_GET['page'] == 'kupon') ? 'active bg-primary' : '' ?>" href="index.php?page=kupon">
                                <i class="fa-solid fa-ticket me-2"></i> Kupon Promo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?page=retur" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'retur' ? 'active' : '' ?>">
                                <i class="fa-solid fa-right-left me-3"></i>Retur Produk
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($level_user === 'admin' || $level_user === 'gudang'): ?>
                        <li class="nav-item">
                            <a href="index.php?page=stok" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'stok' ? 'active' : '' ?>">
                                <i class="fa-solid fa-boxes-stacked me-3"></i>Data Stok
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?page=produksi" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'produksi' ? 'active' : '' ?>">
                                <i class="fa-solid fa-industry me-3"></i>Data Produksi
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="index.php?page=laporan" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'laporan' ? 'active' : '' ?>">
                                <i class="fa-solid fa-file-invoice-dollar me-3"></i>Laporan
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($level_user === 'admin'): ?>
                        <li class="nav-item">
                            <a href="index.php?page=users" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'users' ? 'active' : '' ?>">
                                <i class="fa-solid fa-user-gear me-3"></i>Tambah User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?page=rekening" class="nav-link px-3 py-2.5 <?= ($_GET['page'] ?? '') === 'rekening' ? 'active' : '' ?>">
                                <i class="fa-solid fa-credit-card me-3"></i>Rekening Perusahaan
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div>
                <ul class="nav flex-column">
                    <li class="nav-item border-top pt-3">
                        <a href="index.php?page=logout" class="nav-link text-danger px-3 py-2.5">
                            <i class="fa-solid fa-right-from-bracket me-3"></i>Keluar
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <div id="page-content-wrapper" class="d-flex flex-column">

            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2 shadow-sm">
                <button class="btn btn-primary" id="menu-toggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="ms-3 fw-semibold text-dark d-none d-sm-inline">Oxywater Management System</span>
            </nav>

            <main class="p-3 p-md-4">
                <?php
                $page = $_GET['page'] ?? 'dashboard';

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

                    case 'produksi':
                        if ($level_user !== 'admin' && $level_user !== 'gudang') {
                            echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
                            exit();
                        }
                        include 'produksi.php';
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

                    // Cari baris pemanggilan halaman lain, lalu selipkan baris kupon ini:
                    case 'kupon':
                        include "kupon.php";
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
                        // =========================================================================
                        // INTEGRASI KURS HARGA PRODUK DAN REKENING DATA ADMIN
                        // =========================================================================
                        $total_penjualan_berhasil = 0;
                        $res_sales = mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM penjualan");
                        if ($res_sales) {
                            $get_s = mysqli_fetch_assoc($res_sales);
                            $total_penjualan_berhasil = $get_s['total'] ?? 0;
                        }

                        // ---- DATA GRAFIK 1: 7 RETUR TERAKHIR -----
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

                        // ---- DATA GRAFIK 2: 7 PENJUALAN TERAKHIR -----
                        $label_penjualan = [];
                        $data_penjualan = [];
                        $sql_penjualan = "SELECT tgl_penjualan, SUM(jumlah_produk) as total_qty FROM penjualan GROUP BY tgl_penjualan ORDER BY tgl_penjualan ASC LIMIT 7";
                        $query_p = mysqli_query($koneksi, $sql_penjualan);
                        if ($query_p) {
                            while ($row = mysqli_fetch_assoc($query_p)) {
                                $label_penjualan[] = date('d/m', strtotime($row['tgl_penjualan']));
                                $data_penjualan[]  = (int)$row['total_qty'];
                            }
                        }

                        // ---- DATA GRAFIK 3: GRAFIK RETUR BULANAN -
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

                        // ---- DATA GRAFIK 4: GRAFIK RETUR TAHUNAN -
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

                        if (empty($label_tanggal)) {
                            $label_tanggal = [date('d/m')];
                            $data_jumlah = [0];
                        }
                        if (empty($label_penjualan)) {
                            $label_penjualan = [date('d/m')];
                            $data_penjualan = [0];
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
                                    <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Dashboard Utama</h5>
                                    <p class="text-muted small mb-0">Selamat datang di Oxywater Management System, PT. Nanoplex Indonesia.</p>
                                    <div class="mt-2">
                                        <span class="badge bg-success py-2 px-3 fs-6">Total Pendapatan: Rp <?= number_format($total_penjualan_berhasil, 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-cart-shopping text-primary me-2"></i>Tren Penjualan (7 Hari Terakhir)</h6>
                                        <div style="position: relative; height: 300px; width: 100%;"><canvas id="chartPenjualanUtama"></canvas></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-8">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-3 p-sm-4">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-line text-danger me-2"></i>Tren Produk Retur (7 Hari Terakhir)</h6>
                                        <div style="position: relative; height: 300px; width: 100%;"><canvas id="chartReturUtama"></canvas></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-3 p-sm-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-shield text-success me-2"></i>Laporan Tabel Berkala</h6>
                                            <p class="text-muted small">Ingin melihat rincian angka laporan retur bulanan (Januari - Desember) dan tahunan dari tahun aktif ke depan?</p>
                                        </div>
                                        <?php if ($level_user === 'admin' || $level_user === 'gudang'): ?>
                                            <a href="index.php?page=laporan&tab=penjualan" class="btn btn-primary"><i class="fa fa-list"></i> Lihat Laporan Penjualan</a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100 fw-semibold py-2 mt-3" disabled><i class="fa-solid fa-lock me-2"></i>Laporan Terkunci</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Grafik Bulanan Retur (<?= $tahun_sekarang ?>)</h6>
                                        <div style="position: relative; height: 260px;"><canvas id="chartBulanan"></canvas></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-minus text-warning me-2"></i>Grafik Tahunan Retur</h6>
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
                                        borderColor: '#dc3545',
                                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
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
                            new Chart(document.getElementById('chartPenjualanUtama').getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: <?= json_encode($label_penjualan); ?>,
                                    datasets: [{
                                        label: 'Jumlah Produk Terjual (Qty)',
                                        data: <?= json_encode($data_penjualan); ?>,
                                        backgroundColor: '#0d6efd',
                                        borderRadius: 4
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
    <script>
        // Logika Klik tombol buka/tutup menu samping
        const menuToggle = document.getElementById('menu-toggle');
        const sidebarClose = document.getElementById('sidebar-close');
        const wrapper = document.getElementById('wrapper');

        if (menuToggle) {
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                wrapper.classList.toggle('toggled');
            });
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', function(e) {
                e.preventDefault();
                wrapper.classList.remove('toggled');
            });
        }
    </script>
</body>

</html>