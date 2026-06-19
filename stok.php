<?php
// 1. Pastikan session database tetap berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kunci status sebagai 'admin' khusus di halaman stok ini agar tombol edit/hapus langsung terbuka
$level_user = 'admin';

// ---- LOGIKA 1: PROSES JIKA ADMIN MENAMBAH PRODUK BARU ----
if (isset($_POST['tambah_produk_baru'])) {
    $produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk_baru']);
    $harga  = (int)$_POST['harga_satuan'];

    $cek = mysqli_query($koneksi, "SELECT * FROM stok WHERE nama_produk = '$produk'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<div class='alert alert-warning alert-dismissible fade show m-3' role='alert'>
                <i class='fa-solid fa-triangle-exclamation me-2'></i>Gagal! Produk <b>$produk</b> sudah terdaftar sebelumnya.
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    } else {
        $sql_produk = "INSERT INTO stok (nama_produk, jumlah_stok, harga_per_dus, keterangan, tgl_update) 
                       VALUES ('$produk', 0, '$harga', 'Produk baru didaftarkan', NOW())";

        if (mysqli_query($koneksi, $sql_produk)) {
            echo "<script>window.location.href='index.php?page=stok';</script>";
        } else {
            echo "<script>alert('Gagal mendaftarkan produk: " . mysqli_error($koneksi) . "');</script>";
        }
    }
}

// ---- LOGIKA 2: PROSES UPDATE PASOKAN STOK & HARGA TERBARU ----
if (isset($_POST['tambah_stok'])) {
    $produk     = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $jumlah     = (int)$_POST['jumlah_stok'];
    $harga      = (int)$_POST['harga_per_dus'];
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $sql_aksi = "UPDATE stok SET jumlah_stok = jumlah_stok + $jumlah, harga_per_dus = '$harga', keterangan = '$keterangan', tgl_update = NOW() WHERE nama_produk = '$produk'";

    if (mysqli_query($koneksi, $sql_aksi)) {
        echo "<script>window.location.href='index.php?page=stok';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// ---- LOGIKA 3: PROSES EDIT FULL DATA PRODUK ----
if (isset($_POST['edit_produk'])) {
    $id_stok    = (int)$_POST['id_stok'];
    $produk     = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $harga      = (int)$_POST['harga_per_dus'];
    $stok       = (int)$_POST['jumlah_stok'];
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $sql_edit = "UPDATE stok SET nama_produk = '$produk', harga_per_dus = '$harga', jumlah_stok = '$stok', keterangan = '$keterangan', tgl_update = NOW() WHERE id = '$id_stok'";

    if (mysqli_query($koneksi, $sql_edit)) {
        echo "<script>window.location.href='index.php?page=stok';</script>";
    } else {
        echo "<script>alert('Gagal mengubah data produk: " . mysqli_error($koneksi) . "');</script>";
    }
}

// ---- LOGIKA 4: PROSES HAPUS PRODUK DARI STOK ----
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $sql_delete = "DELETE FROM stok WHERE id = $id_hapus";

    if (mysqli_query($koneksi, $sql_delete)) {
        echo "<script>alert('Varian produk berhasil dihapus dari daftar stok!'); window.location.href='index.php?page=stok';</script>";
    } else {
        echo "<script>alert('Gagal menghapus produk: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>

<div class="row g-4 p-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Master Data & Stok Produk
                    </h5>
                    <p class="text-muted small mb-0">Menu Admin: Daftarkan varian produk baru, sesuaikan harga satuan, dan isi pasokan stok gudang.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary fw-semibold py-2 px-3" data-bs-toggle="modal" data-bs-target="#modalProdukBaru">
                        <i class="fa-solid fa-folder-plus me-2"></i>+ Produk Baru
                    </button>
                    <button class="btn btn-primary fw-semibold py-2 px-3" data-bs-toggle="modal" data-bs-target="#modalStok">
                        <i class="fa-solid fa-plus me-2"></i>Update Pasokan Stok
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0" style="font-size: 14px;">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Varian Produk</th>
                                <th>Harga Jual / Dus</th>
                                <th class="text-center">Sisa Stok (Qty)</th>
                                <th>Status Batas</th>
                                <th>Keterangan / Lokasi Rak</th>
                                <th>Terakhir Diperbarui</th>
                                <th style="width: 160px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_view = "SELECT * FROM stok ORDER BY nama_produk ASC";
                            $query_view = mysqli_query($koneksi, $sql_view);
                            $no = 1;

                            if ($query_view && mysqli_num_rows($query_view) > 0) {
                                while ($row = mysqli_fetch_assoc($query_view)) {
                                    $stok = $row['jumlah_stok'];
                                    $harga_dus = $row['harga_per_dus'] ?? 0;

                                    if ($stok == 0) {
                                        $status = "<span class='badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1'>Stok Kosong</span>";
                                    } else if ($stok <= 10) {
                                        $status = "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1'>Kritis</span>";
                                    } else if ($stok <= 50) {
                                        $status = "<span class='badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1'>Restock</span>";
                                    } else {
                                        $status = "<span class='badge bg-success-subtle text-success border border-success-subtle px-2 py-1'>Aman</span>";
                                    }

                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td class='fw-semibold text-dark'>" . htmlspecialchars($row['nama_produk']) . "</td>";
                                    echo "<td class='fw-bold text-success'>Rp " . number_format($harga_dus, 0, ',', '.') . "</td>";
                                    echo "<td class='text-center fw-bold text-primary'>" . $stok . " Dus</td>";
                                    echo "<td>" . $status . "</td>";
                                    echo "<td class='text-muted'>" . htmlspecialchars($row['keterangan'] ?? '-') . "</td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['tgl_update'])) . "</td>";

                                    // Kolom Aksi Langsung Memunculkan Tombol Edit & Hapus
                                    echo "<td class='text-center'>";
                                    echo "<button class='btn btn-sm btn-warning text-white fw-bold me-1' data-bs-toggle='modal' data-bs-target='#modalEdit" . $row['id'] . "'>
                                            <i class='fa-solid fa-pen-to-square'></i> Edit
                                          </button>";
                                    echo "<a href='index.php?page=stok&hapus=" . $row['id'] . "' class='btn btn-sm btn-danger fw-bold' onclick=\"return confirm('Apakah Anda yakin ingin menghapus varian produk ini dari database stok?');\">
                                            <i class='fa-solid fa-trash-can'></i> Hapus
                                          </a>";
                                    echo "</td>";
                                    echo "</tr>";
                            ?>
                                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Data Master Produk</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="id_stok" value="<?= $row['id'] ?>">
                                                    <div class="modal-body p-4 text-dark text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Nama Varian Produk</label>
                                                            <input type="text" class="form-control" name="nama_produk" value="<?= htmlspecialchars($row['nama_produk']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Harga Satuan Jual (Per Dus)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-light text-muted">Rp</span>
                                                                <input type="number" class="form-control" name="harga_per_dus" value="<?= $row['harga_per_dus'] ?>" min="0" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Sisa Stok di Gudang</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="jumlah_stok" value="<?= $row['jumlah_stok'] ?>" min="0" required>
                                                                <span class="input-group-text bg-light text-muted">Dus</span>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <label class="form-label small fw-semibold">Keterangan / Lokasi Rak</label>
                                                            <textarea class="form-control" name="keterangan" rows="3"><?= htmlspecialchars($row['keterangan'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light border-top-0">
                                                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_produk" class="btn btn-warning btn-sm px-4 fw-semibold">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center text-muted py-4'>Belum ada varian produk yang terdaftar.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProdukBaru" tabindex="-1" aria-labelledby="modalProdukBaruLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold" id="modalProdukBaruLabel"><i class="fa-solid fa-folder-plus me-2 text-primary"></i>Registrasi Varian Produk Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4 text-dark">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Varian Produk Oxywater Baru</label>
                        <input type="text" class="form-control" name="nama_produk_baru" placeholder="Contoh: Oxywater Premium Plus 600ml" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Harga Satuan (Per Dus)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number" class="form-control" name="harga_satuan" min="0" placeholder="Contoh: 150000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_produk_baru" class="btn btn-primary btn-sm px-4 fw-semibold">Daftarkan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalStok" tabindex="-1" aria-labelledby="modalStokLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold" id="modalStokLabel"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Form Pasokan Stok Masuk</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4 text-dark">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Produk Terdaftar</label>
                        <select class="form-select" name="nama_produk" required>
                            <option value="" disabled selected>-- Pilih Produk yang Masuk --</option>
                            <?php
                            $get_list = mysqli_query($koneksi, "SELECT nama_produk, harga_per_dus FROM stok ORDER BY nama_produk ASC");
                            while ($p = mysqli_fetch_assoc($get_list)) {
                                echo "<option value='" . htmlspecialchars($p['nama_produk']) . "'>" . htmlspecialchars($p['nama_produk']) . " (Rp " . number_format($p['harga_per_dus'], 0, ',', '.') . ")</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jumlah Dus yang Masuk Gudang</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="jumlah_stok" min="1" placeholder="Contoh: 100" required>
                            <span class="input-group-text bg-light text-muted">Dus</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Konfirmasi / Sesuaikan Harga Jual Terbaru (Per Dus)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number" class="form-control" name="harga_per_dus" min="0" placeholder="Biarkan sesuai harga lama atau input harga baru" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Keterangan Tambahan / Lokasi Rak Gudang</label>
                        <textarea class="form-control" name="keterangan" rows="3" placeholder="Contoh: Masuk Gudang Utama Blok C"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_stok" class="btn btn-primary btn-sm px-4 fw-semibold">Simpan Pasokan</button>
                </div>
            </form>
        </div>
    </div>
</div>