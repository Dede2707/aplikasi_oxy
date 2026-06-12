<?php
require_once 'koneksi.php';

$nama = isset($_GET['nama']) ? trim(mysqli_real_escape_string($koneksi, $_GET['nama'])) : '';

$response = [
    'status_loyal' => false,
    'total_transaksi' => 0,
    'potongan_persen' => 0
];

if (!empty($nama)) {
    // 1. Hitung jumlah transaksi pelanggan ini sebelumnya
    $q_hitung = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penjualan WHERE nama_pelanggan = '$nama'");
    $res_hitung = mysqli_fetch_assoc($q_hitung);
    $total_transaksi = (int)$res_hitung['total'];

    $response['total_transaksi'] = $total_transaksi;

    // 2. Jika transaksi lebih dari 3 kali, ambil data diskon dari setting_diskon
    if ($total_transaksi > 3) {
        $q_diskon = mysqli_query($koneksi, "SELECT persen_diskon FROM setting_diskon WHERE id = 1 LIMIT 1");
        $res_diskon = mysqli_fetch_assoc($q_diskon);

        $response['status_loyal'] = true;
        $response['potongan_persen'] = (int)($res_diskon['persen_diskon'] ?? 5);
    }
}

// Kembalikan dalam bentuk JSON agar dibaca oleh JavaScript di penjualan.php
header('Content-Type: application/json');
echo json_encode($response);
