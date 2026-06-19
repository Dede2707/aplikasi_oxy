<?php
// Hubungkan ke database
require_once 'koneksi.php';

// Ambil data penjualan khusus hari ini dengan menggabungkan (JOIN) data retur jika ada
$sql = "SELECT p.*, r.jumlah AS qty_retur 
        FROM penjualan p 
        LEFT JOIN retur r ON p.id_penjualan = r.id_penjualan 
        WHERE DATE(p.tgl_penjualan) = CURDATE() 
        ORDER BY p.id_penjualan DESC";

$query = mysqli_query($koneksi, $sql);
$no = 1;

if (mysqli_num_rows($query) == 0) {
    echo "<tr><td colspan='7' class='text-center text-muted py-3'>Belum ada transaksi penjualan masuk hari ini.</td></tr>";
} else {
    while ($row = mysqli_fetch_assoc($query)) {
        // --- LOGIKA NOTIFIKASI BARANG RETUR ---
        if (!empty($row['qty_retur']) && $row['qty_retur'] > 0) {
            $notif_retur = "<div class='mt-1'><span class='badge bg-danger d-inline-flex align-items-center' style='font-size: 11px;'>
                                <i class='fa-solid fa-rotate-left me-1'></i> Diretur (" . $row['qty_retur'] . " Dus)
                            </span></div>";
        } else {
            $notif_retur = "<div class='mt-1'><span class='badge bg-light text-success border border-success-subtle d-inline-flex align-items-center' style='font-size: 10px;'>
                                <i class='fa-solid fa-circle-check me-1'></i> Aman (Tidak Diretur)
                            </span></div>";
        }

        echo "<tr>";
        echo "<td class='text-center'>" . $no++ . "</td>";
        echo "<td class='text-center'><span class='badge bg-dark'>#OXY-" . $row['id_penjualan'] . "</span></td>";
        echo "<td class='text-center'>" . date('d/m/Y H:i', strtotime($row['tgl_penjualan'])) . " WIB</td>";
        echo "<td><strong class='text-dark'>" . htmlspecialchars($row['nama_pelanggan']) . "</strong></td>";

        // Memasukkan Notifikasi Tepat Di Bawah Alamat/Rincian Order
        echo "<td>";
        echo "<span class='text-secondary'>" . htmlspecialchars($row['alamat_kirim']) . "</span>";
        echo $notif_retur;
        echo "</td>";

        echo "<td class='text-center fw-bold'>" . $row['jumlah_produk'] . " Dus</td>";
        echo "<td class='text-end text-success fw-bold'>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
}
