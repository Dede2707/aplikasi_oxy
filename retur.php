<?php
// Pastikan session level user sudah ada (Admin, Manajer, atau Kasir)
$level_user = isset($_SESSION['level']) ? $_SESSION['level'] : 'Admin';

// ==========================================
// 1. OTOMATIS TAMBAH KOLOM PENDUKUNG JIKA BELUM ADA
// ==========================================
$cek_kolom_status = mysqli_query($koneksi, "SHOW COLUMNS FROM retur LIKE 'status_retur'");
if (mysqli_num_rows($cek_kolom_status) == 0) {
    mysqli_query($koneksi, "ALTER TABLE retur ADD status_retur ENUM('Pending', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Pending' AFTER id_penjualan");
}

$cek_kolom_nota = mysqli_query($koneksi, "SHOW COLUMNS FROM retur LIKE 'id_penjualan'");
if (mysqli_num_rows($cek_kolom_nota) == 0) {
    mysqli_query($koneksi, "ALTER TABLE retur ADD id_penjualan INT NULL AFTER status_retur");
}

// Deteksi nama kolom id utama tabel retur
$cek_struktur = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
$col_id  = 'id';
$col_tgl = 'tgl_retur';
$col_jml = 'jumlah';
$col_ket = 'keterangan';
if ($cek_struktur) {
    while ($c = mysqli_fetch_assoc($cek_struktur)) {
        if ($c['Key'] == 'PRI') $col_id = $c['Field'];
        if (in_array(strtolower($c['Field']), ['tanggal_retur', 'tanggal', 'tgl', 'tgl_retur'])) $col_tgl = $c['Field'];
        if (in_array(strtolower($c['Field']), ['jumlah_retur', 'qty', 'qty_retur', 'jumlah'])) $col_jml = $c['Field'];
        if (in_array(strtolower($c['Field']), ['alasan', 'detail', 'note', 'catatan', 'keterangan'])) $col_ket = $c['Field'];
    }
}

// ==========================================
// 2. PROSES SIMPAN KELUHAN / TUKAR BARANG (STATUS AWAL: PENDING)
// ==========================================
if (isset($_POST['btn_simpan_retur'])) {
    $id_penjualan = (int)$_POST['id_penjualan'];
    $tgl_retur    = isset($_POST['tgl_retur']) ? mysqli_real_escape_string($koneksi, $_POST['tgl_retur']) : date('Y-m-d');
    $jumlah       = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $keterangan   = isset($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : '';

    // Validasi Nota
    $cek_nota = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE id_penjualan = $id_penjualan");
    if (mysqli_num_rows($cek_nota) == 0) {
        echo "<script>alert('Gagal Retur! ID Nota tidak ditemukan.'); window.location.href='index.php?page=retur';</script>";
        exit();
    }

    $data_nota = mysqli_fetch_assoc($cek_nota);
    if ($jumlah > (int)$data_nota['jumlah_produk']) {
        echo "<script>alert('Gagal! Qty retur melebihi jumlah pembelian asli.'); window.location.href='index.php?page=retur';</script>";
        exit();
    }

    // Masuk log keluhan dengan status 'Pending' (Stok belum berubah sebelum di-approve)
    $query_add = "INSERT INTO retur ($col_tgl, $col_jml, id_pelanggan, id_barang, id_penjualan, status_retur, $col_ket) 
                  VALUES ('$tgl_retur', $jumlah, 1, 1, $id_penjualan, 'Pending', '$keterangan')";

    if (mysqli_query($koneksi, $query_add)) {
        echo "<script>alert('Permintaan tukar barang berhasil dikirim! Menunggu persetujuan Admin/Manajer.'); window.location.href='index.php?page=retur';</script>";
    } else {
        echo "<script>alert('Gagal mencatat keluhan: " . mysqli_real_escape_string($koneksi, mysqli_error($koneksi)) . "');</script>";
    }
}

// ==========================================
// 3. PROSES APPROVAL PENUKARAN (KHUSUS ADMIN/MANAJER)
// ==========================================
if (isset($_GET['action']) && in_array($level_user, ['Admin', 'Manajer'])) {
    $id_retur = (int)$_GET['id'];
    $action   = $_GET['action'];

    $get_retur = mysqli_query($koneksi, "SELECT r.*, p.alamat_kirim FROM retur r JOIN penjualan p ON r.id_penjualan = p.id_penjualan WHERE r.$col_id = $id_retur");
    $data_retur = mysqli_fetch_assoc($get_retur);

    if ($data_retur && $data_retur['status_retur'] == 'Pending') {
        preg_match('/Produk:\s*([^|]+)/', $data_retur['alamat_kirim'], $matches);
        $nama_produk_retur = isset($matches[1]) ? trim($matches[1]) : "";
        $qty_retur = (int)$data_retur[$col_jml];

        mysqli_begin_transaction($koneksi);

        if ($action == 'setujui') {
            // Mengubah status menjadi Disetujui
            $u_status = mysqli_query($koneksi, "UPDATE retur SET status_retur = 'Disetujui' WHERE $col_id = $id_retur");

            // Sistem langsung mengesahkan transaksi tanpa memedulikan apakah format alamat nota valid atau tidak
            if ($u_status) {
                mysqli_commit($koneksi);
                echo "<script>alert('Penukaran barang DISETUJUI! Status berhasil diperbarui.'); window.location.href='index.php?page=retur';</script>";
            } else {
                mysqli_rollback($koneksi);
                echo "<script>alert('Gagal memperbarui status di database.'); window.location.href='index.php?page=retur';</script>";
            }
        }
    }
}

// ==========================================
// 4. LOGIKA HAPUS DATA ARSIP
// ==========================================
if (isset($_GET['hapus']) && in_array($level_user, ['Admin', 'Manajer'])) {
    $id_retur = (int)$_GET['hapus'];
    if (mysqli_query($koneksi, "DELETE FROM retur WHERE $col_id = $id_retur")) {
        echo "<script>alert('Data keluhan berhasil dihapus dari arsip.'); window.location.href='index.php?page=retur';</script>";
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 text-dark fw-bold"><i class="fa-solid fa-arrows-rotate me-2 text-danger"></i>Penukaran Barang (Retur)</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Otorisasi Penukaran Produk</li>
    </ol>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-dark text-white py-3">
                    <i class="fas fa-plus-circle me-1"></i> Form Tukar Barang (Kasir)
                </div>
                <div class="card-body bg-white text-dark p-4">
                    <form action="index.php?page=retur" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Penukaran</label>
                            <input type="date" name="tgl_retur" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Nomor Nota Penjualan Asli</label>
                            <input type="number" name="id_penjualan" class="form-control" placeholder="Masukkan ID nota (Contoh: 40)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Jumlah Barang Yang Ditukar (Dus)</label>
                            <input type="number" name="jumlah" class="form-control" min="1" placeholder="Contoh: 1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Alasan / Rincian Penukaran</label>
                            <textarea name="keterangan" class="form-control" rows="4" placeholder="Contoh: 1 Dus bocor di perjalanan, tukar dengan varian yang sama." required></textarea>
                        </div>
                        <button type="submit" name="btn_simpan_retur" class="btn btn-danger w-100 fw-semibold py-2">
                            <i class="fa-solid fa-paper-plane me-2"></i>Kirim Keluhan ke Manajer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-danger text-white py-3">
                    <i class="fas fa-table me-1"></i> Monitoring & Otorisasi Tukar Barang
                </div>
                <div class="card-body bg-white text-dark p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="py-3 px-3" style="width: 5%;">No</th>
                                    <th class="py-3" style="width: 15%;">Tanggal</th>
                                    <th class="py-3 text-center" style="width: 10%;">Qty</th>
                                    <th class="py-3" style="width: 45%;">Alasan Penukaran</th>
                                    <th class="py-3 text-center" style="width: 25%;">Otorisasi / Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $query_retur = mysqli_query($koneksi, "SELECT * FROM retur ORDER BY $col_id DESC");

                                while ($row = mysqli_fetch_assoc($query_retur)) {
                                    $status = $row['status_retur'];
                                    $badge_class = "bg-warning text-dark";
                                    if ($status == 'Disetujui') $badge_class = "bg-success text-white";
                                    if ($status == 'Ditolak') $badge_class = "bg-danger text-white";
                                ?>
                                    <tr>
                                        <td class="px-3 fw-bold"><?= $no++; ?></td>
                                        <td class="small"><?= date('d/m/Y', strtotime($row[$col_tgl])); ?></td>
                                        <td class="text-center fw-bold text-danger"><?= $row[$col_jml]; ?> Dus</td>
                                        <td>
                                            <div class="p-2 bg-light rounded text-dark" style="font-size: 13px; border-left: 3px solid #dc3545;">
                                                <span class="text-primary fw-bold">Nota Asli: #<?= $row['id_penjualan']; ?></span><br>
                                                <?= htmlspecialchars($row[$col_ket]); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $badge_class ?> mb-2 d-block py-1 px-2 mx-auto" style="max-width: 100px; font-size:11px;">
                                                <?= $status; ?>
                                            </span>

                                            <?php if (in_array($level_user, ['Admin', 'Manajer'])): ?>
                                                <?php if ($status == 'Pending'): ?>
                                                    <div class="btn-group btn-group-sm w-100 px-2">
                                                        <a href="index.php?page=retur&action=setujui&id=<?= $row[$col_id]; ?>" class="btn btn-success fw-bold text-white" onclick="return confirm('Setujui proses tukar barang ini?')">
                                                            <i class="fa-solid fa-check"></i> Setuju
                                                        </a>
                                                        <a href="index.php?page=retur&action=tolak&id=<?= $row[$col_id]; ?>" class="btn btn-secondary fw-bold" onclick="return confirm('Tolak permintaan ini?')">
                                                            <i class="fa-solid fa-ban"></i> Tolak
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <a href="index.php?page=retur&hapus=<?= $row[$col_id]; ?>" class="btn btn-xs text-danger small mt-1 d-inline-block" onclick="return confirm('Hapus permanen arsip log ini?')" style="font-size: 11px;">
                                                    <i class="fa-solid fa-trash-can"></i> Hapus Arsip
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted italic" style="font-size: 11px;">Kunci Otoritas</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>