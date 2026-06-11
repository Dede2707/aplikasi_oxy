<?php
// Koneksi ke database wajib disertakan di paling atas
include "koneksi.php";

if (!isset($_GET['id'])) {
    echo "ID Transaksi tidak ditemukan.";
    exit;
}

$id_penjualan = (int)$_GET['id'];
$mode = $_GET['mode'] ?? 'faktur'; // default ke mode faktur jika tidak ditentukan

// Ambil data transaksi berdasarkan ID
$query = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE id_penjualan = $id_penjualan");
$data  = mysqli_fetch_assoc($query);

if (!$data) {
    echo "Data transaksi tidak ditemukan di database.";
    exit;
}

// Karena tabel penjualan Anda tidak menyimpan nama produk, kita buat teks default representatif
$nama_produk_default = "Oxywater Air Minum Kesehatan";
$harga_satuan_perkiraan = $data['total_harga'] / $data['jumlah_produk'];

// --- JALANKAN LOGIKA PERFORMA PRINT OTOMATIS SAAT HALAMAN DIBUKA ---
echo "<script>window.onload = function() { window.print(); window.onafterprint = function() { window.close(); } }</script>";

if ($mode == 'struk') {
    // ==========================================================
    // TEMPLATE 1: STRUK KECIL (UNTUK PRINTER THERMAL KASIR 58mm/80mm)
    // ==========================================================
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <title>Stok Penjualan - Struk #<?= $data['id_penjualan'] ?></title>
        <style>
            @page {
                size: auto;
                margin: 0mm;
            }

            body {
                font-family: 'Courier New', Courier, monospace;
                font-size: 12px;
                color: #000;
                width: 220px;
                margin: 10px;
                padding: 0;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .clear {
                clear: both;
            }

            .line {
                border-bottom: 1px dashed #000;
                margin: 5px 0;
            }

            .info-toko h2 {
                margin: 0;
                font-size: 16px;
            }

            .info-toko p {
                margin: 2px 0;
                font-size: 10px;
            }

            .meta-transaksi {
                font-size: 10px;
                margin-bottom: 5px;
            }

            .tabel-item {
                width: 100%;
                font-size: 11px;
                border-collapse: collapse;
            }

            .tabel-item td {
                padding: 3px 0;
                vertical-align: top;
            }

            .totalan {
                font-size: 12px;
                font-weight: bold;
                margin-top: 5px;
            }
        </style>
    </head>

    <body>
        <div class="info-toko text-center">
            <h2>OXYWATER APP</h2>
            <p>Sistem Manajemen Penjualan Resmi</p>
            <p>Depot Utama Distribusi Oxywater</p>
        </div>

        <div class="line"></div>

        <div class="meta-transaksi">
            <div>No. Nota : #<?= $data['id_penjualan'] ?></div>
            <div>Tanggal : <?= date('d/m/Y H:i', strtotime($data['tgl_penjualan'])) ?></div>
            <div>Kasir : Admin Gudang</div>
            <div>Pelanggan: <?= htmlspecialchars($data['nama_pelanggan']) ?></div>
        </div>

        <div class="line"></div>

        <table class="tabel-item">
            <tr>
                <td colspan="3"><?= $nama_produk_default ?></td>
            </tr>
            <tr>
                <td><?= $data['jumlah_produk'] ?> Dus x</td>
                <td class="text-right">Rp <?= number_format($harga_satuan_perkiraan, 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
            </tr>
        </table>

        <div class="line"></div>

        <table class="tabel-item totalan">
            <tr>
                <td>TOTAL AKHIR :</td>
                <td class="text-right">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="text-center" style="font-size: 10px; margin-top: 10px;">
            Terima Kasih Atas Kunjungan Anda<br>
            Periksa Kembali Barang Sebelum Pergi
        </div>
    </body>

    </html>
<?php
} else {
    // ==========================================================
    // TEMPLATE 2: FAKTUR DETAIL ORDER BESAR (UNTUK KERTAS A4 / LETTER)
    // ==========================================================
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <title>Faktur Penjualan - #<?= $data['id_penjualan'] ?></title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                font-size: 14px;
                margin: 30px;
            }

            .header-faktur {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 3px solid #0d6efd;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }

            .logo-area h1 {
                margin: 0;
                color: #0d6efd;
                font-size: 28px;
                font-weight: bold;
            }

            .logo-area p {
                margin: 4px 0 0 0;
                color: #666;
                font-size: 12px;
            }

            .judul-faktur {
                text-align: right;
            }

            .judul-faktur h2 {
                margin: 0;
                color: #333;
                font-size: 22px;
            }

            .info-billing {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
            }

            .info-billing p {
                margin: 4px 0;
            }

            .tabel-faktur {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }

            .tabel-faktur th {
                background-color: #343a40;
                color: #fff;
                text-align: left;
                padding: 12px;
                font-size: 13px;
                text-transform: uppercase;
            }

            .tabel-faktur td {
                padding: 12px;
                border-bottom: 1px solid #dee2e6;
            }

            .tabel-faktur tr:nth-child(even) {
                background-color: #fdfdfd;
            }

            .total-box {
                display: flex;
                justify-content: flex-end;
            }

            .total-table {
                width: 300px;
                font-size: 16px;
            }

            .total-table td {
                padding: 8px 0;
            }

            .grand-total {
                font-size: 18px;
                font-weight: bold;
                color: #0d6efd;
                border-top: 2px solid #dee2e6;
            }

            .Tanda-tangan {
                margin-top: 5px;
                display: flex;
                justify-content: dashed;
                text-align: center;
                justify-content: space-between;
                padding: 0 40px;
            }

            .space-ttd {
                height: 70px;
            }
        </style>
    </head>

    <body>

        <div class="header-faktur">
            <div class="logo-area">
                <h1>OXYWATER APP</h1>
                <p>Sistem Aplikasi Management Pergudangan & Penjualan Air Oxywater</p>
            </div>
            <div class="judul-faktur">
                <h2>FAKTUR PENJUALAN</h2>
                <span style="color:#666">ID Transaksi: #<?= $data['id_penjualan'] ?></span>
            </div>
        </div>

        <div class="info-billing">
            <div>
                <strong style="color:#0d6efd;">Diterbitkan Kepada:</strong>
                <p class="fw-bold" style="margin-top:5px; font-size:16px;"><b><?= htmlspecialchars($data['nama_pelanggan']) ?></b></p>
                <p>Pelanggan Setia Oxywater</p>
            </div>
            <div style="text-align: right;">
                <strong>Rincian Waktu:</strong>
                <p>Tanggal Transaksi: <?= date('d F Y', strtotime($data['tgl_penjualan'])) ?></p>
                <p>Status: <span style="color:green; font-weight:bold;">LUNAS</span></p>
            </div>
        </div>

        <table class="tabel-faktur">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Deskripsi Barang / Layanan</th>
                    <th style="width: 150px; text-align: right;">Harga Satuan</th>
                    <th style="width: 120px; text-align: center;">Kuantitas</th>
                    <th style="width: 180px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong><?= $nama_produk_default ?></strong><br>
                        <span style="font-size:12px; color:#777;">Pasokan Resmi Segar dari Gudang Pusat</span>
                    </td>
                    <td style="text-align: right;">Rp <?= number_format($harga_satuan_perkiraan, 0, ',', '.') ?></td>
                    <td style="text-align: center;"><?= $data['jumlah_produk'] ?> Dus</td>
                    <td style="text-align: right; font-weight: bold;">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <table class="total-table">
                <tr>
                    <td>Subtotal Barang:</td>
                    <td style="text-align: right;">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Potongan Harga:</td>
                    <td style="text-align: right;">Rp 0</td>
                </tr>
                <tr class="grand-total">
                    <td>Total Bayar:</td>
                    <td style="text-align: right;">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
                </tr>
            </table>
        </div>

        <div class="space-ttd"></div>

        <div class="Tanda-tangan">
            <div>
                <p>Penerima / Pelanggan</p>
                <div class="space-ttd"></div>
                <p>( ____________________ )</p>
            </div>
            <div>
                <p>Hormat Kami, Hormat Gudang</p>
                <div class="space-ttd"></div>
                <p>( Admin Oxywater )</p>
            </div>
        </div>

    </body>

    </html>
<?php
}
?>