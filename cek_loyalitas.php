<?php
// cek_loyalitas.php
require_once 'koneksi.php';

header('Content-Type: application/json');

$nama = isset($_GET['nama']) ? mysqli_real_escape_string($koneksi, $_GET['nama']) : '';

$response = [
    'status_loyal' => false,
    'potongan_persen' => 0,
    'total_transaksi' => 0
];

if (!empty($nama)) {
    // 1. Hitung total riwayat transaksi pelanggan berdasarkan nama
    $sql_cek = "SELECT COUNT(*) as total FROM penjualan WHERE LOWER(nama_pelanggan) = LOWER('$nama')";
    $query_cek = mysqli_query($koneksi, $sql_cek);

    if ($query_cek) {
        $data = mysqli_fetch_assoc($query_cek);
        $total_belanja = (int)$data['total'];
        $response['total_transaksi'] = $total_belanja;

        // 2. AMBIL ATURAN DISKON RESPONSIVE DARI DATABASE (Tabel setting_diskon)
        $get_diskon = mysqli_query($koneksi, "SELECT persen_diskon FROM setting_diskon WHERE id = 1");
        $data_diskon = mysqli_fetch_assoc($get_diskon);
        $diskon_database = $data_diskon['persen_diskon'] ?? 5; // Default 5% jika query gagal

        // Jika sudah belanja lebih dari 3 kali, berikan status loyal dan nominal diskon dari database
        if ($total_belanja > 3) {
            $response['status_loyal'] = true;
            $response['potongan_persen'] = $diskon_database;
        }
    }
}

echo json_encode($response);
