<?php
// Pastikan path ke file koneksi database sudah benar sesuai project kamu
require_once 'koneksi.php';

// Mengabaikan error php jika ada data kosong sementara
error_reporting(0);

$nama = isset($_GET['nama']) ? trim($_GET['nama']) : '';

$response = [
    'status_loyal' => false,
    'total_transaksi' => 0,
    'potongan_persen' => 0
];

if (!empty($nama)) {
    // Escaping string untuk keamanan SQL Injection
    $nama_db = mysqli_real_escape_string($koneksi, $nama);

    // 1. Hitung jumlah transaksi sukses pelanggan ini sebelumnya
    $q_hitung = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penjualan WHERE nama_pelanggan = '$nama_db'");
    $res_hitung = mysqli_fetch_assoc($q_hitung);
    $total_transaksi = (int)$res_hitung['total'];

    $response['total_transaksi'] = $total_transaksi;

    // 2. Jika transaksi sudah LEBIH DARI 3 KALI (> 3)
    if ($total_transaksi > 3) {
        $q_diskon = mysqli_query($koneksi, "SELECT persen_diskon FROM setting_diskon WHERE id = 1 LIMIT 1");
        $res_diskon = mysqli_fetch_assoc($q_diskon);

        $response['status_loyal'] = true;
        // Ambil nilai diskon dari database, jika belum diset otomatis beri 5%
        $response['potongan_persen'] = $res_diskon['persen_diskon'] ? (int)$res_diskon['persen_diskon'] : 5;
    }
}

// Set header response berupa JSON resmi
header('Content-Type: application/json');
echo json_encode($response);
exit();
