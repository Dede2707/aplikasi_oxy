<?php
// Cek apakah koneksi sudah include di index.php, jika belum uncomment baris di bawah:
// include "koneksi.php";

// ==========================================
// 1. OTOMATIS TAMBAH KOLOM ID_REKENING JIKA BELUM ADA
// ==========================================
// Ini agar database Anda tidak error saat menyimpan data rekening di tabel retur
$cek_kolom_rek = mysqli_query($koneksi, "SHOW COLUMNS FROM retur LIKE 'id_rekening'");
if (mysqli_num_rows($cek_kolom_rek) == 0) {
    mysqli_query($koneksi, "ALTER TABLE retur ADD id_rekening INT NULL AFTER jumlah");
}

// ==========================================
// 2. PROSES SIMPAN DATA RETUR BARU
// ==========================================
if (isset($_POST['btn_simpan_retur'])) {
    $tgl_retur   = mysqli_real_escape_string($koneksi, $_POST['tgl_retur']);
    $jumlah      = (int)$_POST['jumlah'];
    $id_rekening = !empty($_POST['id_rekening']) ? (int)$_POST['id_rekening'] : 'NULL';
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // Deteksi nama kolom tabel retur secara dinamis untuk query insert
    $cek_struktur = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
    $col_tgl = 'tgl_retur';
    $col_jml = 'jumlah';
    $col_ket = 'keterangan';

    while ($c = mysqli_fetch_assoc($cek_struktur)) {
        $f = strtolower($c['Field']);
        if (in_array($f, ['tanggal_retur', 'tanggal', 'tgl'])) $col_tgl = $c['Field'];
        if (in_array($f, ['jumlah_retur', 'qty', 'qty_retur'])) $col_jml = $c['Field'];
        if (in_array($f, ['alasan', 'detail', 'note', 'catatan'])) $col_ket = $c['Field'];
    }

    $query_add = "INSERT INTO retur ($col_tgl, $col_jml, id_rekening, $col_ket) VALUES ('$tgl_retur', $jumlah, $id_rekening, '$keterangan')";

    if (mysqli_query($koneksi, $query_add)) {
        echo "<script>alert('Data retur berhasil ditambahkan!'); window.location.href='index.php?page=retur';</script>";
    } else {
        echo "<script>alert('Gagal menambah data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// ==========================================
// 3. LOGIKA HAPUS DATA RETUR
// ==========================================
if (isset($_GET['hapus'])) {
    $id_retur = (int)$_GET['hapus'];
    $cek_id = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
    $pk_kolom = 'id';
    while ($c = mysqli_fetch_assoc($cek_id)) {
        if ($c['Key'] == 'PRI') {
            $pk_kolom = $c['Field'];
            break;
        }
    }
    mysqli_query($koneksi, "DELETE FROM retur WHERE $pk_kolom = $id_retur");
    echo "<script>alert('Data retur berhasil dihapus'); window.location.href='index.php?page=retur';</script>";
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 text-dark fw-bold"><i class="fa-solid fa-box-open me-2 text-danger"></i>Data Retur Produk</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Retur Produk</li>
        </< /ol>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-dark text-white py-3" style="border-radius: 12px 12px 0 0;">
                        <i class="fas fa-plus-circle me-1"></i> Input Komplain / Retur Baru
                    </div>
                    <div class="card-body bg-white text-dark p-4">
                        <form action="index.php?page=retur" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Retur</label>
                                <input type="date" name="tgl_retur" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Jumlah Barang (Dus)</label>
                                <input type="number" name="jumlah" class="form-control" min="1" placeholder="Contoh: 5" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Rekening Refund / Tujuan</label>
                                <select name="id_rekening" class="form-select" required>
                                    <option value="">-- Pilih Rekening Tujuan --</option>
                                    <?php
                                    // Mengambil data rekening yang diisi admin sebelumnya
                                    $list_rek = mysqli_query($koneksi, "SELECT * FROM rekening ORDER BY nama_bank ASC");
                                    if ($list_rek && mysqli_num_rows($list_rek) > 0) {
                                        while ($rek = mysqli_fetch_assoc($list_rek)) {
                                            echo "<option value='" . $rek['id'] . "'>" . $rek['nama_bank'] . " - " . $rek['no_rekening'] . " (a.n " . $rek['atas_nama'] . ")</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>Belum ada rekening terdaftar! Daftarkan dulu di menu Rekening.</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Rincian / Catatan Kerusakan</label>
                                <textarea name="keterangan" class="form-control" rows="4" placeholder="Contoh: 2 Dus botol bocor saat pengiriman, dana di-refund ke rekening konsumen..." required></textarea>
                            </div>

                            <button type="submit" name="btn_simpan_retur" class="btn btn-danger w-100 fw-semibold">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Laporan Retur
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-danger text-white py-3" style="border-radius: 12px 12px 0 0;">
                        <i class="fas fa-table me-1"></i> Daftar Komplain & Retur Masuk dari Pelanggan
                    </div>
                    <div class="card-body bg-white text-dark p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-3 px-3" style="width: 5%;">No</th>
                                        <th class="py-3" style="width: 15%;">Tanggal</th>
                                        <th class="py-3 text-center" style="width: 12%;">Qty</th>
                                        <th class="py-3" style="width: 53%;">Rincian Informasi & Rekening</th>
                                        <th class="py-3 text-center" style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;

                                    // Jalankan ulang deteksi kolom untuk tabel list
                                    $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
                                    $col_id = 'id';
                                    $col_tgl = 'tanggal';
                                    $col_jml = 'jumlah';
                                    $col_ket = 'keterangan';

                                    while ($c = mysqli_fetch_assoc($cek_kolom)) {
                                        $f = strtolower($c['Field']);
                                        if ($c['Key'] == 'PRI') $col_id = $c['Field'];
                                        if (in_array($f, ['tgl_retur', 'tanggal_retur', 'tanggal', 'tgl'])) $col_tgl = $c['Field'];
                                        if (in_array($f, ['jumlah_retur', 'jumlah', 'qty', 'qty_retur'])) $col_jml = $c['Field'];
                                        if (in_array($f, ['keterangan', 'alasan', 'detail', 'note', 'catatan'])) $col_ket = $c['Field'];
                                    }

                                    // LEFT JOIN ke tabel rekening untuk menampilkan info bank di tabel retur
                                    $query_retur = mysqli_query($koneksi, "
                                    SELECT retur.*, rekening.nama_bank, rekening.no_rekening, rekening.atas_nama 
                                    FROM retur 
                                    LEFT JOIN rekening ON retur.id_rekening = rekening.id 
                                    ORDER BY retur.$col_id DESC
                                ");

                                    if ($query_retur && mysqli_num_rows($query_retur) > 0) {
                                        while ($row = mysqli_fetch_assoc($query_retur)) {
                                    ?>
                                            <tr>
                                                <td class="px-3 fw-bold"><?= $no++; ?></td>
                                                <td>
                                                    <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i></span>
                                                    <?= (!empty($row[$col_tgl])) ? date('d/m/Y', strtotime($row[$col_tgl])) : date('d/m/Y'); ?>
                                                </td>
                                                <td class="text-center fw-bold text-danger">
                                                    <?= isset($row[$col_jml]) ? $row[$col_jml] : '1'; ?> Dus
                                                </td>
                                                <td>
                                                    <div class="p-3 bg-light rounded text-dark mb-1" style="border-left: 4px solid #dc3545; font-size: 14px;">
                                                        <strong>Keterangan:</strong> <?= isset($row[$col_ket]) ? htmlspecialchars($row[$col_ket]) : '-'; ?>
                                                    </div>
                                                    <small class="text-secondary d-block px-1">
                                                        <i class="fa-solid fa-credit-card me-1"></i> Rekening:
                                                        <?php if (!empty($row['nama_bank'])): ?>
                                                            <span class="badge bg-secondary"><?= htmlspecialchars($row['nama_bank']); ?></span>
                                                            <strong><?= htmlspecialchars($row['no_rekening']); ?></strong> (a.n <?= htmlspecialchars($row['atas_nama']); ?>)
                                                        <?php else: ?>
                                                            <span class="text-muted italic">Tidak melalui transfer bank / belum dipilih</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <a href="index.php?page=retur&hapus=<?= $row[$col_id]; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus arsip komplain ini?')">
                                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-4 text-muted'>Belum ada laporan komplain atau retur dari pelanggan.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>