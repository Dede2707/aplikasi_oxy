<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Tangkap tahun dari URL, jika tidak ada gunakan tahun saat ini
$tahun_pilihan = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, $_GET['tahun']) : date('Y');

// Tangkap tab aktif dari URL, defaultnya ke 'penjualan'
$tab_aktif = isset($_GET['tab']) ? mysqli_real_escape_string($koneksi, $_GET['tab']) : 'penjualan';
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-file-export text-success me-2"></i>Tabel Laporan Berkala</h5>
            <p class="text-muted small mb-0">Menampilkan rincian data transaksi berkala berdasarkan kategori dan rentang waktu.</p>

            <ul class="nav nav-tabs mt-4 mb-0 border-bottom-0">
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $tab_aktif == 'penjualan' ? 'active text-primary border-bottom-0' : 'text-muted' ?>" href="index.php?page=laporan&tab=penjualan&tahun=<?= $tahun_pilihan ?>">
                        <i class="fa-solid fa-cart-shopping me-2"></i>Laporan Penjualan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $tab_aktif == 'retur' ? 'active text-danger border-bottom-0' : 'text-muted' ?>" href="index.php?page=laporan&tab=retur&tahun=<?= $tahun_pilihan ?>">
                        <i class="fa-solid fa-rotate-left me-2"></i>Laporan Retur Barang
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <?php if ($tab_aktif == 'penjualan'): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list text-primary me-2"></i>Semua Riwayat Penjualan Terkini</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary text-center">
                            <tr>
                                <th width="5%">No</th>
                                <th>Tanggal Penjualan</th>
                                <th>Nama Pelanggan</th>
                                <th>Alamat Kirim</th>
                                <th>Jumlah Produk</th>
                                <th>Total Harga</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_p = "SELECT * FROM penjualan ORDER BY tgl_penjualan DESC";
                            $query_p = mysqli_query($koneksi, $sql_p);

                            if (mysqli_num_rows($query_p) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($query_p)) {
                                    $badge = 'bg-secondary';
                                    if ($row['status_order'] == 'Pending') $badge = 'bg-warning text-dark';
                                    elseif ($row['status_order'] == 'Diproses Gudang') $badge = 'bg-info text-dark';
                                    elseif ($row['status_order'] == 'Selesai') $badge = 'bg-success';

                                    // Pastikan nama di dalam $row['...'] sama persis dengan kolom di phpMyAdmin
                                    $nama_pelanggan = isset($row['nama_pelanggan']) ? $row['nama_pelanggan'] : '-';
                                    $alamat_kirim   = isset($row['alamat_kirim']) ? $row['alamat_kirim'] : '-';
                                    $tgl_penjualan  = isset($row['tgl_penjualan']) ? $row['tgl_penjualan'] : date('Y-m-d');
                                    $jumlah_produk  = isset($row['jumlah_produk']) ? $row['jumlah_produk'] : 0;
                                    $total_harga    = isset($row['total_harga']) ? $row['total_harga'] : 0;

                                    // Jika di database ternyata string kosong, kita ubah jadi tanda strip agar ketahuan
                                    if (trim($alamat_kirim) == '') {
                                        $alamat_kirim = '-';
                                    }

                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $no++ . "</td>";
                                    echo "<td class='text-center'>" . date('d/m/Y', strtotime($tgl_penjualan)) . "</td>";
                                    echo "<td>" . htmlspecialchars($nama_pelanggan) . "</td>";
                                    echo "<td>" . htmlspecialchars($alamat_kirim) . "</td>";
                                    echo "<td class='text-center fw-bold'>" . $jumlah_produk . "</td>";
                                    echo "<td class='text-end fw-bold'>Rp " . number_format($total_harga, 0, ',', '.') . "</td>";

                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center text-muted py-3'>Belum ada data penjualan.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-calendar text-primary me-2"></i>Rincian Bulanan Periode Tahun <?= $tahun_pilihan ?></h6>
                    <form action="index.php" method="GET" class="d-flex gap-2">
                        <input type="hidden" name="page" value="laporan">
                        <input type="hidden" name="tab" value="retur">
                        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php
                            $thn_skrg = date('Y');
                            for ($i = $thn_skrg; $i <= $thn_skrg + 5; $i++) {
                                $sel = ($i == $tahun_pilihan) ? 'selected' : '';
                                echo "<option value='$i' $sel>$i</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-danger text-center">
                            <tr>
                                <th>Bulan</th>
                                <th>Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $nama_bulan_lengkap = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];

                            for ($b = 1; $b <= 12; $b++) {
                                $q_bln = mysqli_query($koneksi, "SELECT SUM(jumlah) as qty FROM retur WHERE MONTH(tgl_retur) = $b AND YEAR(tgl_retur) = '$tahun_pilihan'");
                                $dt_bln = mysqli_fetch_assoc($q_bln);
                                $qty_tampil = $dt_bln['qty'] ?? 0;
                            ?>
                                <tr>
                                    <td class="fw-semibold px-3"><?= $nama_bulan_lengkap[$b] ?></td>
                                    <td class="text-center fw-bold <?= $qty_tampil > 0 ? 'text-danger' : 'text-muted' ?>">
                                        <?= $qty_tampil ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-layer-group text-warning me-2"></i>Akumulasi Laporan Tahunan</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-warning text-center">
                            <tr>
                                <th>Tahun</th>
                                <th>Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tahun_mulai = date('Y');
                            $jangka_tahun = 5;

                            for ($t = 0; $t < $jangka_tahun; $t++) {
                                $loop_tahun = $tahun_mulai + $t;
                                $q_thn = mysqli_query($koneksi, "SELECT SUM(jumlah) as qty FROM retur WHERE YEAR(tgl_retur) = '$loop_tahun'");
                                $dt_thn = mysqli_fetch_assoc($q_thn);
                                $qty_thn = $dt_thn['qty'] ?? 0;
                            ?>
                                <tr>
                                    <td class="text-center fw-bold text-secondary"><?= $loop_tahun ?></td>
                                    <td class="text-center fw-bold <?= $qty_thn > 0 ? 'text-danger' : 'text-muted' ?>">
                                        <?= $qty_thn ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>