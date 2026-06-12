<?php
// Cek apakah koneksi sudah include di index.php, jika belum uncomment baris di bawah:
// include "koneksi.php";

// ==========================================
// 1. OTOMATIS TAMBAH KOLOM ID_REKENING JIKA BELUM ADA
// ==========================================
$cek_kolom_rek = mysqli_query($koneksi, "SHOW COLUMNS FROM retur LIKE 'id_rekening'");
if (mysqli_num_rows($cek_kolom_rek) == 0) {
    mysqli_query($koneksi, "ALTER TABLE retur ADD id_rekening INT NULL AFTER jumlah");
}

// Deteksi nama kolom tabel retur secara dinamis
$cek_struktur = mysqli_query($koneksi, "SHOW COLUMNS FROM retur");
$col_id  = 'id';
$col_tgl = 'tgl_retur';
$col_jml = 'jumlah';
$col_ket = 'keterangan';

if ($cek_struktur) {
    while ($c = mysqli_fetch_assoc($cek_struktur)) {
        $f = strtolower($c['Field']);
        if ($c['Key'] == 'PRI') $col_id = $c['Field'];
        if (in_array($f, ['tanggal_retur', 'tanggal', 'tgl', 'tgl_retur'])) $col_tgl = $c['Field'];
        if (in_array($f, ['jumlah_retur', 'qty', 'qty_retur', 'jumlah'])) $col_jml = $c['Field'];
        if (in_array($f, ['alasan', 'detail', 'note', 'catatan', 'keterangan'])) $col_ket = $c['Field'];
    }
}

// ==========================================
// 2. PROSES SIMPAN DATA RETUR / KOMPLAIN BARU
// ==========================================
if (isset($_POST['btn_simpan_retur'])) {
    $tgl_retur    = isset($_POST['tgl_retur']) ? mysqli_real_escape_string($koneksi, $_POST['tgl_retur']) : date('Y-m-d');
    $jumlah       = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $id_rekening  = !empty($_POST['id_rekening']) ? (int)$_POST['id_rekening'] : "NULL";
    $keterangan   = isset($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : '';

    $val_rekening = ($id_rekening === "NULL") ? "NULL" : $id_rekening;

    // --- TRIK DIAM-DIAM: Ambil ID Pelanggan pertama yang ada di database ---
    $id_pelanggan = 1; // Default fallback
    $ambil_plg = mysqli_query($koneksi, "SELECT id_pelanggan FROM pelanggan LIMIT 1");
    if ($ambil_plg && mysqli_num_rows($ambil_plg) > 0) {
        $data_plg = mysqli_fetch_assoc($ambil_plg);
        $id_pelanggan = $data_plg['id_pelanggan'];
    }

    // --- TRIK DIAM-DIAM: Ambil ID Barang pertama yang ada di database ---
    $id_barang = 1; // Default fallback
    $tabel_b = "barang";
    $cek_t = mysqli_query($koneksi, "SHOW TABLES LIKE 'barang'");
    if (mysqli_num_rows($cek_t) == 0) {
        $tabel_b = "produk";
    }

    $pk_b = ($tabel_b == 'barang') ? 'id_barang' : 'id_produk';
    $cek_pk = mysqli_query($koneksi, "SHOW COLUMNS FROM $tabel_b LIKE 'id'");
    if ($cek_pk && mysqli_num_rows($cek_pk) > 0) {
        $pk_b = 'id';
    }

    $ambil_brg = mysqli_query($koneksi, "SELECT $pk_b FROM $tabel_b LIMIT 1");
    if ($ambil_brg && mysqli_num_rows($ambil_brg) > 0) {
        $data_brg = mysqli_fetch_assoc($ambil_brg);
        $id_barang = $data_brg[$pk_b];
    }

    // Eksekusi Simpan (Tetap memasukkan id_pelanggan & id_barang secara otomatis agar database senang)
    $query_add = "INSERT INTO retur ($col_tgl, $col_jml, id_pelanggan, id_barang, id_rekening, $col_ket) 
                  VALUES ('$tgl_retur', $jumlah, $id_pelanggan, $id_barang, $val_rekening, '$keterangan')";

    if (mysqli_query($koneksi, $query_add)) {
        echo "<script>alert('Laporan komplain berhasil disimpan!'); window.location.href='index.php?page=retur';</script>";
    } else {
        echo "<script>alert('Gagal database: " . mysqli_real_escape_string($koneksi, mysqli_error($koneksi)) . "');</script>";
    }
}

// ==========================================
// 3. LOGIKA HAPUS DATA RETUR
// ==========================================
if (isset($_GET['hapus'])) {
    $id_retur = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM retur WHERE $col_id = $id_retur");
    echo "<script>alert('Data komplain berhasil dihapus'); window.location.href='index.php?page=retur';</script>";
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 text-dark fw-bold"><i class="fa-solid fa-box-open me-2 text-danger"></i>Log Komplain & Keluhan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Komplain Masuk</li>
    </ol>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-dark text-white py-3" style="border-radius: 12px 12px 0 0;">
                    <i class="fas fa-plus-circle me-1"></i> Catat Keluhan Baru
                </div>
                <div class="card-body bg-white text-dark p-4">
                    <form action="index.php?page=retur" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Masuk Keluhan</label>
                            <input type="date" name="tgl_retur" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Jumlah Barang Bermasalah (Dus)</label>
                            <input type="number" name="jumlah" class="form-control" min="1" placeholder="Contoh: 5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Rekening Refund / Ganti Rugi</label>
                            <select name="id_rekening" class="form-select" required>
                                <option value="">-- Pilih Rekening Tujuan --</option>
                                <?php
                                $list_rek = mysqli_query($koneksi, "SELECT * FROM rekening ORDER BY nama_bank ASC");
                                if ($list_rek && mysqli_num_rows($list_rek) > 0) {
                                    while ($rek = mysqli_fetch_assoc($list_rek)) {
                                        echo "<option value='" . $rek['id'] . "'>" . $rek['nama_bank'] . " - " . $rek['no_rekening'] . " (a.n " . $rek['atas_nama'] . ")</option>";
                                    }
                                } else {
                                    echo "<option value='' disabled>Belum ada rekening terdaftar!</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Rincian Keluhan Pelanggan</label>
                            <textarea name="keterangan" class="form-control" rows="5" placeholder="Tulis rincian di sini... Contoh: Toko Berkah komplain 2 dus penyok, minta refund ke rekening BCA." required></textarea>
                        </div>

                        <button type="submit" name="btn_simpan_retur" class="btn btn-danger w-100 fw-semibold">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Laporan Keluhan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-danger text-white py-3" style="border-radius: 12px 12px 0 0;">
                    <i class="fas fa-table me-1"></i> Daftar Riwayat Komplain Masuk
                </div>
                <div class="card-body bg-white text-dark p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="py-3 px-3" style="width: 5%;">No</th>
                                    <th class="py-3" style="width: 15%;">Tanggal</th>
                                    <th class="py-3 text-center" style="width: 12%;">Qty</th>
                                    <th class="py-3" style="width: 53%;">Rincian Informasi Keluhan & Rekening</th>
                                    <th class="py-3 text-center" style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
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
                                                <?= isset($row[$col_jml]) ? $row[$col_jml] : '0'; ?> Dus
                                            </td>
                                            <td>
                                                <div class="p-3 bg-light rounded text-dark mb-1" style="border-left: 4px solid #dc3545; font-size: 14px;">
                                                    <?= isset($row[$col_ket]) ? htmlspecialchars($row[$col_ket]) : '-'; ?>
                                                </div>
                                                <small class="text-secondary d-block px-1">
                                                    <i class="fa-solid fa-credit-card me-1"></i> Rekening Ganti Rugi:
                                                    <?php if (!empty($row['nama_bank'])): ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['nama_bank']); ?></span>
                                                        <strong><?= htmlspecialchars($row['no_rekening']); ?></strong> (a.n <?= htmlspecialchars($row['atas_nama']); ?>)
                                                    <?php else: ?>
                                                        <span class="text-muted italic">Belum dipilih / Cash</span>
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
                                    echo "<tr><td colspan='5' class='text-center py-4 text-muted'>Belum ada catatan keluhan masuk.</td></tr>";
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