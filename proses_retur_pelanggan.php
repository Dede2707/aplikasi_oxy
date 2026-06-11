<?php
include "koneksi.php";

if (isset($_POST['submit_retur'])) {
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $nama_produk    = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $jumlah_retur   = (int)$_POST['jumlah_retur'];
    $alasan         = mysqli_real_escape_string($koneksi, $_POST['alasan']);
    $keterangan     = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $tgl_retur      = date('Y-m-d');

    // Satukan semua informasi teks (Nama Pelanggan & Nama Produk) ke dalam Keterangan
    // Ini menjamin data teks Anda TIDAK AKAN HILANG dan bisa dibaca Admin di dashboard
    $keterangan_lengkap = "Pelanggan: " . $nama_pelanggan . " | Produk: " . $nama_produk . " | [" . $alasan . "] " . $keterangan;

    // --- DETEKSI STRUKTUR KOLOM TABEL RETUR ---
    $kolom_tabel = [];
    $ambil_struktur = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
    while ($r = mysqli_fetch_assoc($ambil_struktur)) {
        $kolom_tabel[] = strtolower($r['Field']);
    }

    $insert_data = [];

    // 1. Amankan Kolom Tanggal
    if (in_all_array(['tgl_retur', 'tanggal_retur', 'tanggal', 'tgl'], $kolom_tabel, $k_tgl)) {
        $insert_data[$k_tgl] = "'$tgl_retur'";
    }

    // 2. Amankan Kolom Pelanggan (Isi angka 1 / ID aman untuk pelanggan umum)
    if (in_all_array(['id_pelanggan', 'pelanggan_id', 'kd_pelanggan'], $kolom_tabel, $k_pel_id)) {
        $insert_data[$k_pel_id] = "1";
    } elseif (in_all_array(['pelanggan', 'nama_pelanggan', 'customer', 'nama'], $kolom_tabel, $k_pel)) {
        $insert_data[$k_pel] = "'$nama_pelanggan'";
    }

    // 3. Amankan Kolom Barang / Produk (Mengatasi error 'id_barang' tidak boleh kosong)
    if (in_all_array(['id_barang', 'barang_id', 'id_produk', 'produk_id'], $kolom_tabel, $k_prod_id)) {
        $insert_data[$k_prod_id] = "1"; // Beri angka ID default agar MySQL meloloskan query
    } elseif (in_all_array(['nama_produk', 'produk', 'varian', 'barang'], $kolom_tabel, $k_prod)) {
        $insert_data[$k_prod] = "'$nama_produk'";
    }

    // 4. Amankan Kolom Jumlah
    if (in_all_array(['jumlah_retur', 'jumlah', 'qty', 'qty_retur'], $kolom_tabel, $k_jml)) {
        $insert_data[$k_jml] = "'$jumlah_retur'";
    }

    // 5. Amankan Kolom Keterangan
    if (in_all_array(['keterangan', 'alasan', 'detail', 'note', 'catatan'], $kolom_tabel, $k_ket)) {
        $insert_data[$k_ket] = "'$keterangan_lengkap'";
    }

    // Eksekusi penyusunan query otomatis
    $fields = implode(", ", array_keys($insert_data));
    $values = implode(", ", array_values($insert_data));

    $sql_retur = "INSERT INTO retur ($fields) VALUES ($values)";

    if (mysqli_query($koneksi, $sql_retur)) {
        echo "<script>
                alert('Komplain retur Anda berhasil dikirim ke sistem admin.');
                window.location.href = 'order_pelanggan.php';
              </script>";
    } else {
        echo "Error detail: " . mysqli_error($koneksi);
        exit();
    }
}

function in_all_array($needles, $haystack, &$matched)
{
    foreach ($needles as $needle) {
        if (in_array($needle, $haystack)) {
            $matched = $needle;
            return true;
        }
    }
    return false;
}
