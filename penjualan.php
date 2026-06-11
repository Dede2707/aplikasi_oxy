<?php
// ---- LOGIKA 1: PROSES SIMPAN DATA PENJUALAN + POTONG STOK ----
if (isset($_POST['simpan_penjualan'])) {
    $tgl_penjualan  = $_POST['tgl_penjualan'];
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $nama_produk    = mysqli_real_escape_string($koneksi, $_POST['nama_produk']); // Tetap diambil untuk cek & potong stok
    $jumlah_produk  = (int)$_POST['jumlah_produk'];
    $total_harga    = (int)$_POST['total_harga'];

    // 1. CEK SISA STOK DI GUDANG TERLEBIH DAHULU
    $cek_stok = mysqli_query($koneksi, "SELECT jumlah_stok FROM stok WHERE nama_produk = '$nama_produk'");
    $data_stok = mysqli_fetch_assoc($cek_stok);

    if (!$data_stok) {
        echo "<div class='alert alert-danger alert-dismissible fade show m-3' role='alert'>
                <i class='fa-solid fa-circle-xmark me-2'></i>Gagal! Produk belum terdaftar di modul master Stok.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    } elseif ($data_stok['jumlah_stok'] < $jumlah_produk) {
        echo "<div class='alert alert-warning alert-dismissible fade show m-3' role='alert'>
                <i class='fa-solid fa-triangle-exclamation me-2'></i>Gagal Transaksi! Stok tidak mencukupi. Sisa stok saat ini: <b>" . $data_stok['jumlah_stok'] . " Dus</b>.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    } else {
        // 2. JIKA STOK CUKUP, SIMPAN DATA (Hanya memasukkan kolom yang ada di database kamu)
        $sql_insert = "INSERT INTO penjualan (tgl_penjualan, nama_pelanggan, jumlah_produk, total_harga) 
                       VALUES ('$tgl_penjualan', '$nama_pelanggan', '$jumlah_produk', '$total_harga')";

        if (mysqli_query($koneksi, $sql_insert)) {
            // 3. POTONG STOK SECARA OTOMATIS DI TABEL STOK
            $sql_update_stok = "UPDATE stok SET jumlah_stok = jumlah_stok - $jumlah_produk, tgl_update = NOW() WHERE nama_produk = '$nama_produk'";
            mysqli_query($koneksi, $sql_update_stok);

            echo "<div class='alert alert-success alert-dismissible fade show m-3' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i>Sukses! Data penjualan disimpan & stok otomatis terpotong.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
        } else {
            echo "<div class='alert alert-danger m-3' role='alert'><i class='fa-solid fa-bug me-2'></i>Gagal menyimpan data: " . mysqli_error($koneksi) . "</div>";
        }
    }
}

// ---- LOGIKA 2: PROSES HAPUS DATA PENJUALAN ----
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $sql_delete = "DELETE FROM penjualan WHERE id_penjualan = $id_hapus";
    if (mysqli_query($koneksi, $sql_delete)) {
        echo "<script>window.location.href='index.php?page=penjualan';</script>";
    }
}
?>

<!-- TAMPILAN HALAMAN DATA PENJUALAN -->
<div class="row g-4 p-3">
    <!-- 1. FORM INPUT PENJUALAN (KIRI) -->
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-cart-plus text-primary me-2"></i>Input Penjualan Baru
                </h6>
                <form action="index.php?page=penjualan" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Tanggal Transaksi</label>
                        <input type="date" name="tgl_penjualan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Pelanggan</label>
                        <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Toko Berkah" required>
                    </div>

                    <!-- PILIHAN VARIAN PRODUK -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Pilih Varian Produk Oxywater</label>
                        <select name="nama_produk" id="pilih_produk" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Produk --</option>
                            <?php
                            $get_produk = mysqli_query($koneksi, "SELECT nama_produk, harga_per_dus, jumlah_stok FROM stok WHERE jumlah_stok > 0");
                            while ($p = mysqli_fetch_assoc($get_produk)) {
                                echo "<option value='" . htmlspecialchars($p['nama_produk']) . "' data-harga='" . $p['harga_per_dus'] . "'>" . htmlspecialchars($p['nama_produk']) . " (Rp " . number_format($p['harga_per_dus'], 0, ',', '.') . " - Sisa: " . $p['jumlah_stok'] . ")</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Jumlah (Qty)</label>
                        <input type="number" name="jumlah_produk" id="jumlah_produk" class="form-control" placeholder="0" min="1" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Total Pembayaran (Rp) - *Otomatis</label>
                        <input type="number" name="total_harga" id="total_harga" class="form-control bg-light" readonly placeholder="0" min="0" required>
                    </div>
                    <button type="submit" name="simpan_penjualan" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Penjualan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. TABEL RIWAYAT PENJUALAN (KANAN) -->
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Riwayat Penjualan Produk
                </h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                <th style="width: 50px;">No</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th class="text-center">Qty</th>
                                <th>Total Harga</th>
                                <th style="width: 80px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 14px;">
                            <?php
                            $sql_select = "SELECT * FROM penjualan ORDER BY tgl_penjualan DESC";
                            $query_select = mysqli_query($koneksi, $sql_select);
                            $no = 1;

                            if (mysqli_num_rows($query_select) > 0) {
                                while ($row = mysqli_fetch_assoc($query_select)) {
                            ?>
                                    <tr>
                                        <td class="text-muted"><?= $no++ ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tgl_penjualan'])) ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= $row['jumlah_produk'] ?> Dus</span></td>
                                        <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <!-- TOMBOL PRINT NOTA BESAR (FAKTUR) -->
                                                <a href="print_nota.php?id=<?= $row['id_penjualan'] ?>&mode=faktur" target="_blank" class="btn btn-sm btn-outline-primary border-0 p-1 px-2" title="Cetak Faktur A4">
                                                    <i class="fa-solid fa-print"></i> Faktur
                                                </a>
                                                <!-- TOMBOL PRINT STRUK KECIL (THERMAL) -->
                                                <a href="print_nota.php?id=<?= $row['id_penjualan'] ?>&mode=struk" target="_blank" class="btn btn-sm btn-outline-success border-0 p-1 px-2" title="Cetak Struk Kasir">
                                                    <i class="fa-solid fa-receipt"></i> Struk
                                                </a>
                                                <!-- TOMBOL HAPUS -->
                                                <a href="index.php?page=penjualan&hapus=<?= $row['id_penjualan'] ?>" class="btn btn-sm btn-outline-danger border-0 p-1 px-2" onclick="return confirm('Hapus data penjualan ini?')" title="Hapus">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-4'>Belum ada transaksi penjualan yang dicatat.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT HITUNG OTOMATIS KASIR -->
<script>
    const pilihProduk = document.getElementById('pilih_produk');
    const jumlahProduk = document.getElementById('jumlah_produk');
    const totalHarga = document.getElementById('total_harga');

    function hitungTotal() {
        const pilihanAktif = pilihProduk.options[pilihProduk.selectedIndex];
        const hargaPerDus = pilihanAktif.getAttribute('data-harga') ? parseInt(pilihanAktif.getAttribute('data-harga')) : 0;
        const qty = jumlahProduk.value ? parseInt(jumlahProduk.value) : 0;

        totalHarga.value = hargaPerDus * qty;
    }

    pilihProduk.addEventListener('change', hitungTotal);
    jumlahProduk.addEventListener('input', hitungTotal);
</script>