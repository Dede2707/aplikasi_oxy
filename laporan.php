<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Tangkap tahun dari URL, jika tidak ada, gunakan tahun digital saat ini (2026)
$tahun_pilihan = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, $_GET['tahun']) : date('Y');
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-file-export text-success me-2"></i>Tabel Laporan Berkala</h5>
            <p class="text-muted small mb-0">Menampilkan rincian data retur kuantitas produk berdasarkan rentang waktu.</p>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-calendar text-primary me-2"></i>Rincian Bulanan Periode Tahun <?= $tahun_pilihan ?></h6>
                <form action="index.php" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="page" value="laporan">
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
                    <thead class="table-primary text-center">
                        <tr>
                            <th>Bulan</th>
                            <th>Total Qty Retur</th>
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

                        // Looping mutlak dari bulan 1 sampai 12
                        for ($b = 1; $b <= 12; $b++) {
                            $q_bln = mysqli_query($koneksi, "SELECT SUM(jumlah) as qty FROM retur WHERE MONTH(tgl_retur) = $b AND YEAR(tgl_retur) = '$tahun_pilihan'");
                            $dt_bln = mysqli_fetch_assoc($q_bln);
                            $qty_tampil = $dt_bln['qty'] ?? 0;
                        ?>
                            <tr>
                                <td class="fw-semibold px-3"><?= $nama_bulan_lengkap[$b] ?></td>
                                <td class="text-center fw-bold <?= $qty_tampil > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= $qty_tampil ?> Pcs
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
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
                            <th>Total Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tahun_mulai = date('Y'); // Mengambil tahun berjalan saat ini secara otomatis
                        $jangka_tahun = 5;       // Menampilkan hingga 5 tahun ke depan

                        for ($t = 0; $t < $jangka_tahun; $t++) {
                            $loop_tahun = $tahun_mulai + $t;

                            $q_thn = mysqli_query($koneksi, "SELECT SUM(jumlah) as qty FROM retur WHERE YEAR(tgl_retur) = '$loop_tahun'");
                            $dt_thn = mysqli_fetch_assoc($q_thn);
                            $qty_thn = $dt_thn['qty'] ?? 0;
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-secondary"><?= $loop_tahun ?></td>
                                <td class="text-center fw-bold <?= $qty_thn > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= $qty_thn ?> Pcs
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>