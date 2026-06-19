<?php
// cek_kupon.php
require_once 'koneksi.php';

header('Content-Type: application/json');

$kode = isset($_GET['kode']) ? mysqli_real_escape_string($koneksi, strtoupper(trim($_GET['kode']))) : '';

$response = [
    'valid' => false,
    'persen_diskon' => 0,
    'pesan' => 'Kode kupon tidak ditemukan'
];

if (!empty($kode)) {
    $sql = "SELECT * FROM kupon_diskon WHERE kode_kupon = '$kode' AND status_aktif = 1 LIMIT 1";
    $query = mysqli_query($koneksi, $sql);

    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $response['valid'] = true;
        $response['persen_diskon'] = (int)$data['persen_diskon'];
        $response['pesan'] = 'Kupon Berhasil Diterapkan!';
    }
}

echo json_encode($response);
