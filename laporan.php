<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Tambahkan proteksi hak akses
// Asumsi: Anda menyimpan role/level di session, misal $_SESSION['level']
// Sesuaikan nama index session dengan yang Anda gunakan untuk menyimpan level user
if (isset($_SESSION['level']) && $_SESSION['level'] == 'Gudang') {
    echo "<script>
            alert('Akses Ditolak! Anda tidak memiliki izin untuk melihat laporan.');
            window.location.href='index.php';
          </script>";
    exit();
}

// Tangkap filter dari URL, jika tidak ada gunakan default
$tahun_pilihan   = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, $_GET['tahun']) : date('Y');
$bulan_pilihan   = isset($_GET['bulan']) ? mysqli_real_escape_string($koneksi, $_GET['bulan']) : date('m');
$periode_pilihan = isset($_GET['periode']) ? mysqli_real_escape_string($koneksi, $_GET['periode']) : 'hari_ini';
$tab_aktif       = isset($_GET['tab']) ? mysqli_real_escape_string($koneksi, $_GET['tab']) : 'penjualan';

// Deteksi struktur primary key tabel retur secara otomatis agar tidak error Unknown Column
$cek_struktur = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
$col_id  = 'id';
if ($cek_struktur) {
    while ($c = mysqli_fetch_assoc($cek_struktur)) {
        if ($c['Key'] == 'PRI') $col_id = $c['Field'];
    }
}

// ==========================================
// QUERY HITUNG CARD SUMMARY UTK RETUR (KHUSUS HARI INI)
// ==========================================
$q_hari_ini = mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM retur WHERE DATE(tgl_retur) = CURDATE() AND status_retur = 'Disetujui'");
$r_hari_ini = mysqli_fetch_assoc($q_hari_ini);
$total_hari_ini = $r_hari_ini['total'] ?? 0;
?>

<meta http-equiv="refresh" content="60">

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-file-export text-success me-2"></i>Tabel Laporan Berkala</h5>
            <p class="text-muted small mb-0">Menampilkan rincian data transaksi berkala berdasarkan kategori dan rentang waktu.</p>

            <ul class="nav nav-tabs mt-4 mb-0 border-bottom-0">
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $tab_aktif == 'penjualan' ? 'active text-primary border-bottom-0' : 'text-muted' ?>" href="index.php?page=laporan&tab=penjualan&periode=<?= $periode_pilihan ?>&tahun=<?= $tahun_pilihan ?>">
                        <i class="fa-solid fa-cart-shopping me-2"></i>Laporan Penjualan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $tab_aktif == 'retur' ? 'active text-danger border-bottom-0' : 'text-muted' ?>" href="index.php?page=laporan&tab=retur&bulan=<?= $bulan_pilihan ?>&tahun=<?= $tahun_pilihan ?>">
                        <i class="fa-solid fa-rotate-left me-2"></i>Laporan Retur Barang
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <?php if ($tab_aktif == 'penjualan'): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-list text-primary me-2"></i>Data Riwayat Penjualan</h6>
                    <form action="index.php" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="hidden" name="page" value="laporan">
                        <input type="hidden" name="tab" value="penjualan">
                        <select name="periode" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="hari_ini" <?= $periode_pilihan == 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                            <option value="bulan_ini" <?= $periode_pilihan == 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                            <option value="tahun_ini" <?= $periode_pilihan == 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
                        </select>
                        <?php if ($periode_pilihan == 'bulan_ini'): ?>
                            <select name="bulan" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <?php
                                $nama_bulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                foreach ($nama_bulan as $m => $b) {
                                    $sel = ($m == $bulan_pilihan) ? 'selected' : '';
                                    echo "<option value='$m' $sel>$b</option>";
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                        <?php if ($periode_pilihan == 'bulan_ini' || $periode_pilihan == 'tahun_ini'): ?>
                            <select name="tahun" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <?php
                                $thn_skrg = date('Y');
                                for ($i = $thn_skrg - 3; $i <= $thn_skrg + 2; $i++) {
                                    $sel = ($i == $tahun_pilihan) ? 'selected' : '';
                                    echo "<option value='$i' $sel>$i</option>";
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </form>
                </div>
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
                            if ($periode_pilihan == 'hari_ini') {
                                $sql_p = "SELECT * FROM penjualan WHERE DATE(tgl_penjualan) = CURDATE() ORDER BY tgl_penjualan DESC";
                            } elseif ($periode_pilihan == 'bulan_ini') {
                                $sql_p = "SELECT * FROM penjualan WHERE MONTH(tgl_penjualan) = '$bulan_pilihan' AND YEAR(tgl_penjualan) = '$tahun_pilihan' ORDER BY tgl_penjualan DESC";
                            } else {
                                $sql_p = "SELECT * FROM penjualan WHERE YEAR(tgl_penjualan) = '$tahun_pilihan' ORDER BY tgl_penjualan DESC";
                            }
                            $query_p = mysqli_query($koneksi, $sql_p);
                            if (mysqli_num_rows($query_p) > 0) {
                                $no = 1;
                                $grand_total_harga = 0;
                                $grand_total_qty = 0;
                                while ($row = mysqli_fetch_assoc($query_p)) {
                                    $grand_total_qty += $row['jumlah_produk'];
                                    $grand_total_harga += $row['total_harga'];
                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $no++ . "</td>";
                                    echo "<td class='text-center'>" . date('d/m/Y', strtotime($row['tgl_penjualan'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['alamat_kirim']) . "</td>";
                                    echo "<td class='text-center fw-bold'>" . $row['jumlah_produk'] . "</td>";
                                    echo "<td class='text-end fw-bold'>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
                                    echo "</tr>";
                                }
                                echo "<tr class='table-light'>";
                                echo "<td colspan='4' class='text-end fw-bold'>TOTAL AKUMULASI:</td>";
                                echo "<td class='text-center fw-bold text-success'>" . $grand_total_qty . "</td>";
                                echo "<td class='text-end fw-bold text-success'>Rp " . number_format($grand_total_harga, 0, ',', '.') . "</td>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-3'>Belum ada data penjualan pada periode ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="col-12 col-md-4 mx-auto">
            <div class="card border-0 shadow-sm bg-danger text-white" style="border-radius: 8px;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="small text-white-50 mb-1 fw-semibold">Retur Hari Ini (Disetujui)</h6>
                        <h3 class="fw-bold mb-0"><?= $total_hari_ini ?> <span style="font-size: 13px; font-weight: normal;"></span></h3>
                    </div>
                    <i class="fa-solid fa-calendar-day fa-2xl text-white-50"></i>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Rincian Retur Berdasarkan Periode</h6>

                    <form action="index.php" method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="page" value="laporan">
                        <input type="hidden" name="tab" value="retur">

                        <select name="bulan" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <?php
                            $nama_bulan_retur = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                            foreach ($nama_bulan_retur as $m_key => $b_val) {
                                $selected_m = ($m_key == $bulan_pilihan) ? 'selected' : '';
                                echo "<option value='$m_key' $selected_m>$b_val</option>";
                            }
                            ?>
                        </select>

                        <select name="tahun" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <?php
                            $thn_skrg = date('Y');
                            for ($i = $thn_skrg - 2; $i <= $thn_skrg + 2; $i++) {
                                $selected_y = ($i == $tahun_pilihan) ? 'selected' : '';
                                echo "<option value='$i' $selected_y>$i</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Tanggal Retur</th>
                                <th width="15%">No Nota Asli</th>
                                <th width="15%">Qty Retur</th>
                                <th>Alasan / Keterangan</th>
                                <th width="15%">Status Keluhan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Mengambil data berdasarkan Dropdown Bulan & Tahun yang dipilih
                            $q_detail = mysqli_query($koneksi, "SELECT * FROM retur WHERE MONTH(tgl_retur) = '$bulan_pilihan' AND YEAR(tgl_retur) = '$tahun_pilihan' ORDER BY $col_id DESC");

                            if (mysqli_num_rows($q_detail) > 0) {
                                $no_r = 1;
                                $tot_r_periode = 0;
                                while ($r_data = mysqli_fetch_assoc($q_detail)) {
                                    $tot_r_periode += $r_data['jumlah'];

                                    $st = $r_data['status_retur'];
                                    $bg = "bg-warning text-dark";
                                    if ($st == 'Disetujui') $bg = "bg-success text-white";
                                    if ($st == 'Ditolak') $bg = "bg-danger text-white";

                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $no_r++ . "</td>";
                                    echo "<td class='text-center'>" . date('d/m/Y', strtotime($r_data['tgl_retur'])) . "</td>";
                                    echo "<td class='text-center fw-bold text-primary'>#" . $r_data['id_penjualan'] . "</td>";
                                    echo "<td class='text-center fw-bold text-danger'>" . $r_data['jumlah'] . " Dus</td>";
                                    echo "<td>" . htmlspecialchars($r_data['keterangan']) . "</td>";
                                    echo "<td class='text-center'><span class='badge $bg px-2 py-1' style='font-size:11px;'>$st</span></td>";
                                    echo "</tr>";
                                }
                                echo "<tr class='table-light fw-bold'>";
                                echo "<td colspan='3' class='text-end'>TOTAL RETUR PERIODE INI:</td>";
                                echo "<td class='text-center text-danger'>" . $tot_r_periode . " Dus</td>";
                                echo "<td colspan='2'></td>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-4'>Tidak ada aktivitas retur barang pada periode bulan " . $nama_bulan_retur[$bulan_pilihan] . " " . $tahun_pilihan . ".</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>