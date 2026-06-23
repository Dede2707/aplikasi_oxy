<?php
// Cek akses
if ($level_user !== 'admin' && $level_user !== 'gudang') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
    exit();
}

// ==========================================
// 1. PROSES SIMPAN DATA PRODUKSI
// ==========================================
if (isset($_POST['simpan'])) {
    $tgl = date('Y-m-d'); // Otomatis tanggal hari ini
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $jml = (int)$_POST['jumlah_masuk'];
    $ket = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // Format keterangan baku untuk memudahkan pelacakan produk saat dihapus
    $keterangan_lengkap = "Produk: " . $nama_produk . " | " . $ket;

    mysqli_begin_transaction($koneksi);

    // 1. Simpan ke tabel riwayat produksi 
    // Pastikan $nama_produk sudah didefinisikan sebelumnya (sudah ada di baris 10)
    $query_produksi = mysqli_query($koneksi, "INSERT INTO produksi (jumlah_masuk, tgl_produksi, keterangan, varian_produk) VALUES ('$jml', '$tgl', '$keterangan_lengkap', '$nama_produk')");

    // 2. UPDATE STOK: Otomatis bertambah
    $query_stok = mysqli_query($koneksi, "UPDATE stok SET jumlah_stok = jumlah_stok + $jml, tgl_update = NOW() WHERE nama_produk = '$nama_produk'");

    if ($query_produksi && $query_stok) {
        mysqli_commit($koneksi);
        echo "<script>alert('Data produksi berhasil disimpan dan Stok " . $nama_produk . " otomatis bertambah!'); window.location='index.php?page=produksi';</script>";
    } else {
        mysqli_rollback($koneksi);
        echo "<script>alert('Gagal memproses data produksi!'); window.location='index.php?page=produksi';</script>";
    }
}

// ==========================================
// 2. PROSES HAPUS RIWAYAT PRODUKSI (POTONG STOK KEMBALI)
// ==========================================
if (isset($_GET['hapus'])) {
    $tgl_hapus = mysqli_real_escape_string($koneksi, $_GET['tgl']);
    $ket_hapus = mysqli_real_escape_string($koneksi, $_GET['ket']);
    $jml_hapus = (int)$_GET['jml'];

    // Cari teks nama produk di antara kata "Produk: " dan tanda batasan " |"
    preg_match('/Produk:\s*([^|]+)/', $ket_hapus, $matches);
    $nama_produk_hapus = isset($matches[1]) ? trim($matches[1]) : "";

    mysqli_begin_transaction($koneksi);

    // Jika nama produk berhasil dideteksi dari log keterangan, kurangi stoknya
    if (!empty($nama_produk_hapus)) {
        $back_stok = mysqli_query($koneksi, "UPDATE stok SET jumlah_stok = jumlah_stok - $jml_hapus, tgl_update = NOW() WHERE nama_produk = '$nama_produk_hapus'");
    } else {
        $back_stok = true; // Set true jika data lama tak berformat agar log tetap bisa terhapus aman
    }

    // Hapus baris log produksi terkait
    $hapus_log = mysqli_query($koneksi, "DELETE FROM produksi WHERE tgl_produksi = '$tgl_hapus' AND keterangan = '$ket_hapus' AND jumlah_masuk = $jml_hapus LIMIT 1");

    if ($back_stok && $hapus_log) {
        mysqli_commit($koneksi);
        echo "<script>alert('Riwayat produksi berhasil dihapus! Stok telah disesuaikan kembali.'); window.location='index.php?page=produksi';</script>";
    } else {
        mysqli_rollback($koneksi);
        echo "<script>alert('Gagal menghapus data produksi.'); window.location='index.php?page=produksi';</script>";
    }
}
?>

<div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-industry me-2"></i>Manajemen Produksi & Pasokan Stok</h5>

    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-3">
            <select name="nama_produk" class="form-select" required>
                <option value="">-- Pilih Varian Produk --</option>
                <?php
                $ambil_produk = mysqli_query($koneksi, "SELECT nama_produk FROM stok ORDER BY nama_produk ASC");
                while ($p = mysqli_fetch_assoc($ambil_produk)) {
                    echo "<option value='" . htmlspecialchars($p['nama_produk']) . "'>" . htmlspecialchars($p['nama_produk']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="jumlah_masuk" class="form-control" placeholder="Jumlah Masuk " min="1" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="keterangan" class="form-control" placeholder="Catatan Tambahan (Misal: Shift A)">
        </div>
        <div class="col-md-3">
            <button type="submit" name="simpan" class="btn btn-primary w-100">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan & Update Stok
            </button>
        </div>
    </form>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Tanggal Produksi</th>
                <th>Jumlah Masuk</th>
                <th>Rincian Hasil Produksi</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($koneksi, "SELECT * FROM produksi ORDER BY tgl_produksi DESC");
            if (mysqli_num_rows($query) > 0) {
                while ($d = mysqli_fetch_assoc($query)) {
                    $tgl_tampil = date('d/m/Y', strtotime($d['tgl_produksi']));
            ?>
                    <tr>
                        <td><?= $tgl_tampil; ?></td>
                        <td class="fw-bold text-success">+ <?= $d['jumlah_masuk']; ?> </td>
                        <td><?= htmlspecialchars($d['keterangan']); ?></td>
                        <td class="text-center">
                            <a href="index.php?page=produksi&hapus=true&tgl=<?= $d['tgl_produksi']; ?>&jml=<?= $d['jumlah_masuk']; ?>&ket=<?= urlencode($d['keterangan']); ?>"
                                class="btn btn-sm btn-outline-danger py-1"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus data produksi ini? Stok gudang otomatis dikurangi kembali!')">
                                <i class="fa-solid fa-trash-can"></i> Hapus
                            </a>
                        </td>
                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='4' class='text-center text-muted py-3'>Belum ada riwayat produksi.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>