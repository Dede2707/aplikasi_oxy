<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4 border-start border-warning border-4">
            <h5 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-crown text-warning me-2"></i>Peringkat Pelanggan Terloyal (Member)
            </h5>
            <p class="text-muted small mb-0">Daftar pelanggan otomatis dihitung dari rekam jejak jumlah transaksi terbanyak di database.</p>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 8%;">Rank</th>
                            <th>Nama Pelanggan / Mitra</th>
                            <th class="text-center">Total Frekuensi Order</th>
                            <th class="text-end">Total Akumulasi Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query mengambil peringkat loyalitas dari tabel penjualan
                        $q_loyal = "SELECT nama_pelanggan, 
                                    COUNT(*) as total_order, 
                                    SUM(total_harga) as total_omset 
                                    FROM penjualan 
                                    GROUP BY nama_pelanggan 
                                    ORDER BY total_order DESC, total_omset DESC";

                        $res_loyal = mysqli_query($koneksi, $q_loyal);
                        $rank = 1;

                        if ($res_loyal && mysqli_num_rows($res_loyal) > 0) {
                            while ($l = mysqli_fetch_assoc($res_loyal)) {
                                $nama_raw = $l['nama_pelanggan'];
                                // Membersihkan tag [LANGGANAN TETAP] jika ada agar tampilan nama rapi
                                $nama_bersih = str_replace('[LANGGANAN TETAP] ', '', $nama_raw);

                                $badge_rank = "<span class='fw-bold text-muted'>#" . $rank . "</span>";
                                if ($rank == 1) $badge_rank = "<span class='badge bg-warning text-dark fw-bold'><i class='fa-solid fa-trophy text-danger'></i> 1</span>";
                                if ($rank == 2) $badge_rank = "<span class='badge bg-secondary fw-bold'>2</span>";
                                if ($rank == 3) $badge_rank = "<span class='badge bg-danger fw-bold'>3</span>";
                        ?>
                                <tr>
                                    <td class="text-center"><?php echo $badge_rank; ?></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($nama_bersih); ?></span>
                                        <?php if (strpos($nama_raw, '[LANGGANAN TETAP]') !== false): ?>
                                            <span class="ms-2 badge bg-info text-dark" style="font-size: 10px;">Mitra Grosir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-primary"><?php echo $l['total_order']; ?> Transaksi</td>
                                    <td class="text-end fw-bold text-success">Rp <?php echo number_format($l['total_omset'], 0, ',', '.'); ?></td>
                                </tr>
                        <?php
                                $rank++;
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-muted py-4'>Belum ada data riwayat transaksi penjualan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>