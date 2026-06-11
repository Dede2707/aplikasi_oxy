<?php
// Proteksi: Mencegah file diakses langsung tanpa login/session admin Anda
if (!isset($level_user) || $level_user !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
    exit();
}

// Tangkap parameter tahun dari URL (Default ke tahun berjalan jika kosong)
$filter_tahun = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, $_GET['tahun']) : date('Y');

// QUERY UTAMA: Mengelompokkan total penjualan per bulan berdasarkan tahun yang dipilih
$query_sql = "SELECT 
                MONTH(tgl_penjualan) as angka_bulan,
                SUM(jumlah_produk) as total_qty_bulan,
                SUM(total_harga) as total_duit_bulan,
                COUNT(id_penjualan) as total_transaksi
              FROM penjualan 
              WHERE YEAR(tgl_penjualan) = '$filter_tahun'
              GROUP BY MONTH(tgl_penjualan)
              ORDER BY MONTH(tgl_penjualan) ASC";

$pembelian_grup = mysqli_query($koneksi, $query_sql);

// Array bantuan untuk mengubah angka bulan ke Bahasa Indonesia
$nama_bulan_indo = [
    1 => "Januari",
    2 => "Februari",
    3 => "Maret",
    4 => "April",
    5 => "Mei",
    6 => "Juni",
    7 => "Juli",
    8 => "Agustus",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4 bg-white text-dark">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">
                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>Rekapitulasi Omset Bulanan - Tahun <?= $filter_tahun ?>
                    </h5>
                    <p class="text-muted small mb-0">Menampilkan rincian performa penjualan setiap bulan pada tahun <?= $filter_tahun ?>.</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-sm btn-secondary fw-semibold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                    <button onclick="window.print()" class="btn btn-sm btn-dark fw-semibold">
                        <i class="fa-solid fa-print me-1"></i> Cetak Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-dark bg-white rounded">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th width="10%">No</th>
                                <th>Periode Bulan</th>
                                <th class="text-center" width="20%">Jumlah Transaksi</th>
                                <th class="text-center" width="20%">Volume Penjualan</th>
                                <th class="text-end" width="25%">Total Omset Penjualan</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            $no = 1;
                            $grand_total_transaksi = 0;
                            $grand_total_qty = 0;
                            $grand_total_duit = 0;

                            // Kita buat penampung data dari database agar mudah di-mapping 12 bulan penuh
                            $data_per_bulan = [];
                            while ($row = mysqli_fetch_assoc($pembelian_grup)) {
                                $data_per_bulan[$row['angka_bulan']] = $row;
                            }

                            // Looping wajib 1-12 supaya bulan yang kosong/belum ada penjualan tetap tampil Rp 0
                            for ($b = 1; $b <= 12; $b++) {
                                $ada_data = isset($data_per_bulan[$b]);

                                $qty = $ada_data ? $data_per_bulan[$b]['total_qty_bulan'] : 0;
                                $duit = $ada_data ? $data_per_bulan[$b]['total_duit_bulan'] : 0;
                                $trx = $ada_data ? $data_per_bulan[$b]['total_transaksi'] : 0;

                                $grand_total_transaksi += $trx;
                                $grand_total_qty += $qty;
                                $grand_total_duit += $duit;
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="fw-bold text-dark"><?= $nama_bulan_indo[$b] ?> <?= $filter_tahun ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= number_format($trx) ?> Transaksi</span>
                                    </td>
                                    <td class="text-center fw-semibold"><?= number_format($qty) ?> Dus</td>
                                    <td class="text-end fw-bold text-success">Rp <?= number_format($duit, 0, ',', '.') ?></td>
                                </tr>
                            <?php
                            }
                            ?>

                            <tr class="table-dark fw-bold fs-6">
                                <td colspan="2" class="text-end text-white">TOTAL AKUMULASI TAHUNAN:</td>
                                <td class="text-center text-warning"><?= number_format($grand_total_transaksi) ?> Trx</td>
                                <td class="text-center text-warning"><?= number_format($grand_total_qty) ?> Dus</td>
                                <td class="text-end text-warning">Rp <?= number_format($grand_total_duit, 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>