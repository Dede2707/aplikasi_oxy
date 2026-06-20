<?php
session_start();
include "koneksi.php";

$tampilan = "form_order";


// Cek jika ingin melihat riwayat keluhan pelanggan
if (isset($_GET['page']) && $_GET['page'] == 'riwayat') {
    $tampilan = "riwayat_keluhan";
}
// Halaman form keluhan untuk pelanggan
if (isset($_GET['page']) && $_GET['page'] == 'keluhan') {
    $tampilan = "form_keluhan";
}



// [PROSES TAHAP 1] - Lanjut ke Pembayaran Produk
if (isset($_POST['proses_checkout'])) {
    // Ambil kode promo dari input
    $kode_promo = strtoupper(trim($_POST['kode_promo']));
    $diskon = 0;

    // Logika Diskon (Contoh: KODE 'HEMAT10' = 10%)
    if ($kode_promo == "HEMAT10") {
        $diskon = 0.10;
    }

    $_SESSION['temp_order'] = [
        'nama'          => mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']),
        'telp'          => mysqli_real_escape_string($koneksi, $_POST['no_telp']),
        'alamat'        => mysqli_real_escape_string($koneksi, $_POST['alamat']),
        'nama_produk'   => mysqli_real_escape_string($koneksi, $_POST['nama_produk']),
        'qty'           => intval($_POST['qty_beli']),
        'kode_promo'    => $kode_promo,
        'diskon_persen' => $diskon
    ];

    $nama_p = $_SESSION['temp_order']['nama_produk'];
    $cek_p = mysqli_query($koneksi, "SELECT * FROM stok WHERE nama_produk='$nama_p'");

    if ($cek_p && mysqli_num_rows($cek_p) > 0) {
        $dp = mysqli_fetch_assoc($cek_p);
        $harga_asli = (int)$dp['harga_per_dus'];
        $total_awal = $harga_asli * $_SESSION['temp_order']['qty'];

        // Hitung total akhir
        $total_akhir = $total_awal - ($total_awal * $diskon);
        $_SESSION['temp_order']['total_harga'] = $total_akhir;

        $tampilan = "instruksi_bayar";
    } else {
        echo "<script>alert('Produk tidak ditemukan!'); window.location.href='order_pelanggan.php';</script>";
        exit();
    }
}

// [PROSES TAHAP 2] - Simpan Pesanan Produk ke Database
if (isset($_POST['konfirmasi_final'])) {
    $order = $_SESSION['temp_order'];
    date_default_timezone_set('Asia/Jakarta');
    $tgl   = date('Y-m-d H:i:s');

    $n     = $order['nama'];
    $hp    = $order['telp'];
    $a     = $order['alamat'];
    $q     = $order['qty'];
    $t     = $order['total_harga'];

    $alamat_dan_produk = "Produk: " . $order['nama_produk'] . " | Alamat: " . $a . " | [STATUS: Pending]";

    $sql = "INSERT INTO penjualan (tgl_penjualan, nama_pelanggan, no_telpon, alamat_kirim, jumlah_produk, total_harga) 
            VALUES ('$tgl', '$n', '$hp', '$alamat_dan_produk', '$q', '$t')";

    if (mysqli_query($koneksi, $sql)) {
        // AMBIL ID BARU: Menangkap ID Penjualan yang baru saja tersimpan untuk link download faktur
        $id_baru = mysqli_insert_id($koneksi);

        mysqli_query($koneksi, "UPDATE stok SET jumlah_stok = jumlah_stok - $q WHERE nama_produk = '" . $order['nama_produk'] . "'");
        unset($_SESSION['temp_order']);

        // Alihkan halaman dengan membawa ID baru agar bisa diunduh
        header("Location: order_pelanggan.php?page=sukses&id=" . $id_baru);
        exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}

// Tangkap status sukses dari redirect parameter URL
if (isset($_GET['page']) && $_GET['page'] == 'sukses') {
    $tampilan = "sukses_order";
    $id_penjualan_unduh = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

// [PROSES PELANGGAN] - Kirim Laporan / Keluhan Baru
if (isset($_POST['kirim_keluhan'])) {
    $nama_pelapor = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor']);
    $no_wa        = mysqli_real_escape_string($koneksi, $_POST['no_wa']);
    $id_pesanan   = intval($_POST['id_pesanan']);
    $isi_keluhan  = mysqli_real_escape_string($koneksi, $_POST['isi_keluhan']);

    // Validasi: Pastikan pesanan tersebut memang milik pelanggan tersebut
    $cek_history = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE id_penjualan='$id_pesanan' AND nama_pelanggan='$nama_pelapor' AND no_telpon='$no_wa'");

    if (mysqli_num_rows($cek_history) == 0) {
        echo "<script>alert('Data pesanan tidak ditemukan atau tidak sesuai dengan nama/nomor yang diinput.'); window.location.href='order_pelanggan.php?page=keluhan';</script>";
        exit();
    }

    $tgl = date('Y-m-d H:i:s');
    $format_keluhan = "[PROSES] [ID PESANAN: $id_pesanan] [WA: $no_wa] " . $isi_keluhan;

    $sql_keluhan = "INSERT INTO penjualan (tgl_penjualan, nama_pelanggan, no_telpon, alamat_kirim, jumlah_produk, total_harga) 
                    VALUES ('$tgl', '$nama_pelapor', '$no_wa', '$format_keluhan', 0, 0)";

    if (mysqli_query($koneksi, $sql_keluhan)) {
        echo "<script>alert('Laporan Keluhan berhasil dikirim!'); window.location.href='order_pelanggan.php';</script>";
    }
}

// [PERBAIKAN] - Mengubah status keluhan menjadi SELESAI alih-alih menghapusnya
if (isset($_GET['action']) && $_GET['action'] == 'selesai') {
    $id_selesai = intval($_GET['id']);
    $get_old = mysqli_query($koneksi, "SELECT alamat_kirim FROM penjualan WHERE id_penjualan='$id_selesai'");

    if (mysqli_num_rows($get_old) > 0) {
        $d_old = mysqli_fetch_assoc($get_old);
        // Mengganti teks tag [PROSES] menjadi [SELESAI]
        $alamat_baru = str_replace("[PROSES]", "[SELESAI]", $d_old['alamat_kirim']);

        mysqli_query($koneksi, "UPDATE penjualan SET alamat_kirim='$alamat_baru' WHERE id_penjualan='$id_selesai'");
        echo "<script>alert('Laporan Keluhan berhasil diselesaikan & diarsipkan!'); window.location.href='order_pelanggan.php?page=riwayat';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxywater System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #0f2027;
            background: linear-gradient(to right, #2c5364, #203a43, #0f2027);
            color: white;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 25px 0;
        }

        .card-custom {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 25px;
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #333 !important;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="text-end mb-3">
            <?php if ($tampilan == "form_order"): ?>
                <a href="order_pelanggan.php?page=keluhan" class="btn btn-sm btn-danger fw-bold me-1"><i class="fa-solid fa-bullhorn me-1"></i> Laporan Keluhan</a>
                <a href="order_pelanggan.php?page=riwayat" class="btn btn-sm btn-warning text-dark fw-bold"><i class="fa-solid fa-clipboard-list me-1"></i> Laporan Keluhan & Masalah</a>
            <?php else: ?>
                <a href="order_pelanggan.php" class="btn btn-sm btn-info fw-bold"><i class="fa-solid fa-shopping-cart me-1"></i> Kembali ke Form Order</a>
            <?php endif; ?>
        </div>

        <div class="row justify-content-center mx-1">
            <div class="col-12 col-md-11 col-lg-9">

                <?php if ($tampilan == "form_order"): ?>
                    <div class="card card-custom shadow-lg">
                        <h3 class="text-center mb-4 fw-bold"><i class="fa-solid fa-droplet text-info me-1"></i> Pesanan Oxywater</h3>
                        <form action="order_pelanggan.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_pelanggan" class="form-control" placeholder="Nama penerima" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                                <input type="number" name="no_telp" class="form-control" placeholder="Contoh: 08123456xxx" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Alamat Lengkap Pengiriman</label>
                                <textarea name="alamat" class="form-control" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan" required></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-8">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Pilih Produk</label>
                                        <select name="nama_produk" class="form-select" required>
                                            <option value="" disabled selected>-- Pilih Produk --</option>
                                            <?php
                                            $res = mysqli_query($koneksi, "SELECT nama_produk, harga_per_dus, jumlah_stok FROM stok WHERE jumlah_stok > 0");
                                            while ($row = mysqli_fetch_assoc($res)) {
                                                echo "<option value='" . $row['nama_produk'] . "'>" . $row['nama_produk'] . " (Rp " . number_format($row['harga_per_dus'], 0, ',', '.') . ") - Stok: " . $row['jumlah_stok'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Jumlah</label>
                                        <input type="number" name="qty_beli" class="form-control text-center" value="1" min="1" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Kode Promo (Opsional)</label>
                                <input type="text" name="kode_promo" class="form-control" placeholder="Masukkan kode hemat (misal: HEMAT10)">
                            </div>
                            <button type="submit" name="proses_checkout" class="btn btn-info w-100 fw-bold mt-4 text-uppercase">Lanjut Ke Pembayaran <i class="fa-solid fa-chevron-right small ms-1"></i></button>
                        </form>
                    </div>

                <?php elseif ($tampilan == "instruksi_bayar"): $d = $_SESSION['temp_order']; ?>
                    <li class="list-group-item bg-transparent text-white border-secondary">
                        Status Promo: <strong><?= !empty($d['kode_promo']) ? $d['kode_promo'] . " (" . ($d['diskon_persen'] * 100) . "%)" : "Tidak ada"; ?></strong>
                    </li>
                    <div class="card card-custom shadow-lg">
                        <h3 class="text-center mb-4 text-warning fw-bold"><i class="fa-solid fa-credit-card me-1"></i> Instruksi Bayar</h3>
                        <div class="bg-dark bg-opacity-50 p-3 rounded-3 mb-3 border border-secondary text-center">
                            <p class="mb-1 small opacity-75">Total yang harus ditransfer:</p>
                            <h2 class="text-info fw-bold mb-0">Rp <?= number_format($d['total_harga'], 0, ',', '.'); ?></h2>
                        </div>
                        <ul class="list-group list-group-flush rounded-3 mb-4 bg-transparent text-white small">
                            <li class="list-group-item bg-transparent text-white border-secondary">Penerima: <strong><?= $d['nama']; ?></strong></li>
                            <li class="list-group-item bg-transparent text-white border-secondary">Produk: <strong><?= $d['nama_produk']; ?> (<?= $d['qty']; ?> Pcs)</strong></li>
                            <li class="list-group-item bg-transparent text-white border-secondary">Alamat: <strong><?= $d['alamat']; ?></strong></li>
                        </ul>
                        <div class="bg-light text-dark p-3 rounded-3 mb-4 text-center">
                            <p class="mb-1 small text-uppercase fw-bold text-muted">Transfer Ke Rekening BCA</p>
                            <h3 class="fw-bold text-primary mb-1"></h3>
                            <p class="small mb-0 text-secondary">A/N PT. Oxywater Indonesia</p>
                        </div>
                        <form action="order_pelanggan.php" method="POST">
                            <button type="submit" name="konfirmasi_final" class="btn btn-success w-100 fw-bold py-3 text-uppercase"><i class="fa-solid fa-circle-check me-1"></i> Saya Sudah Transfer</button>
                        </form>
                    </div>

                <?php elseif ($tampilan == "sukses_order"): ?>
                    <div class="card card-custom p-4 text-center shadow-lg">
                        <div class="mb-3">
                            <i class="fa-solid fa-circle-check text-success fa-4x"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2">Pesanan Berhasil Masuk!</h3>
                        <p class="small opacity-75 mb-4">Silakan simpan atau cetak berkas lampiran transaksi Anda di bawah ini.</p>

                        <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary mb-4">
                            <h6 class="fw-semibold text-warning mb-3"><i class="fa-solid fa-file-arrow-down me-1"></i> Pilih Opsi Unduh File Dokumen:</h6>
                            <div class="row g-2 justify-content-center">
                                <div class="col-6">
                                    <a href="faktur.php?id=<?= $id_penjualan_unduh; ?>&mode=struk" target="_blank" class="btn btn-success fw-bold w-100 py-2 btn-sm">
                                        <i class="fa-solid fa-receipt me-1"></i> Unduh Struk Kecil
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="faktur.php?id=<?= $id_penjualan_unduh; ?>&mode=faktur" target="_blank" class="btn btn-primary fw-bold w-100 py-2 btn-sm">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Unduh Faktur A4
                                    </a>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" onclick="unduhKeduaNota(<?= $id_penjualan_unduh; ?>)" class="btn btn-link btn-sm text-info text-decoration-none p-0" style="font-size: 12px;">
                                    <i class="fa-solid fa-copy me-1"></i> Buka Kedua Mode Sekaligus
                                </button>
                            </div>
                        </div>

                        <a href="order_pelanggan.php" class="btn btn-outline-light w-100 fw-bold py-2">Kembali ke Beranda</a>
                    </div>

                    <script>
                        function unduhKeduaNota(id) {
                            window.open('faktur.php?id=' + id + '&mode=struk', '_blank');
                            window.open('faktur.php?id=' + id + '&mode=faktur', '_blank');
                        }
                    </script>

                <?php elseif ($tampilan == "form_keluhan"): ?>
                    <div class="card card-custom shadow-lg">
                        <h3 class="text-center mb-1 text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Form Pengaduan & Keluhan</h3>
                        <p class="text-center text-white-50 small mb-4">Pilih pesanan Anda yang mengalami kendala.</p>

                        <form action="order_pelanggan.php?page=keluhan" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_pelapor" class="form-control" placeholder="Nama yang digunakan saat order" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                                <input type="number" name="no_wa" class="form-control" placeholder="Nomor yang digunakan saat order" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Pilih Pesanan Terkait</label>
                                <select name="id_pesanan" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Pesanan Anda --</option>
                                    <?php
                                    $res_order = mysqli_query($koneksi, "SELECT id_penjualan, tgl_penjualan, alamat_kirim FROM penjualan WHERE jumlah_produk > 0 ORDER BY tgl_penjualan DESC");
                                    while ($p = mysqli_fetch_assoc($res_order)) {
                                        echo "<option value='" . $p['id_penjualan'] . "'>" . $p['tgl_penjualan'] . " - " . substr($p['alamat_kirim'], 0, 30) . "...</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Isi Keluhan</label>
                                <textarea name="isi_keluhan" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="kirim_keluhan" class="btn btn-danger w-100 fw-bold text-uppercase mt-2">Kirim Laporan</button>
                        </form>
                    </div>

                <?php elseif ($tampilan == "riwayat_keluhan"): ?>
                    <div class="card card-custom shadow-lg mb-4 border border-warning">
                        <h4 class="fw-bold text-warning mb-3"><i class="fa-solid fa-scale-balanced me-2"></i>Laporan Keluhan & Masalah Pelanggan</h4>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover small align-middle">
                                <thead class="table-warning text-dark">
                                    <tr>
                                        <th>Waktu Masuk</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Isi Laporan & Detail Kendala</th>
                                        <th class="text-center">Status / Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_keluhan = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE jumlah_produk = 0 AND total_harga = 0 ORDER BY tgl_penjualan DESC");
                                    if (mysqli_num_rows($q_keluhan) == 0) {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-3'>Tidak ada data keluhan yang tercatat.</td></tr>";
                                    }
                                    while ($r_klh = mysqli_fetch_assoc($q_keluhan)) {
                                        $is_selesai = (strpos($r_klh['alamat_kirim'], '[SELESAI]') !== false);
                                    ?>
                                        <tr class="<?= $is_selesai ? 'opacity-50 text-secondary' : ''; ?>">
                                            <td><small><?= date('d/m/y H:i', strtotime($r_klh['tgl_penjualan'])); ?></small></td>
                                            <td class="fw-bold <?= $is_selesai ? 'text-secondary' : 'text-danger'; ?>">
                                                <?= htmlspecialchars($r_klh['nama_pelanggan']); ?>
                                            </td>
                                            <td>
                                                <?php if ($is_selesai): ?>
                                                    <span class="badge bg-success me-1">SELESAI</span>
                                                    <span class="text-muted"><del><?= htmlspecialchars($r_klh['alamat_kirim']); ?></del></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark me-1">PROSES</span>
                                                    <span class="text-white"><?= htmlspecialchars($r_klh['alamat_kirim']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!$is_selesai): ?>
                                                    <a href="order_pelanggan.php?page=riwayat&action=selesai&id=<?= $r_klh['id_penjualan']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Tandai laporan ini sebagai SELESAI?')"><i class="fa-solid fa-check-double"></i> Selesaikan</a>
                                                <?php else: ?>
                                                    <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> Diarsipkan</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>