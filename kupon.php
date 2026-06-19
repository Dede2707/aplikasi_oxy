<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// ---- LOGIKA 1: PROSES SIMPAN KUPON BARU ----
if (isset($_POST['simpan_kupon'])) {
    $kode_kupon    = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['kode_kupon'])));
    $persen_diskon = (int)$_POST['persen_diskon'];

    // Cek apakah kode kupon sudah pernah terdaftar
    $cek_kupon = mysqli_query($koneksi, "SELECT * FROM kupon_diskon WHERE kode_kupon = '$kode_kupon'");
    if (mysqli_num_rows($cek_kupon) > 0) {
        echo "<script>alert('Gagal! Kode kupon sudah digunakan, buat kode lain.'); window.location.href='index.php?page=kupon';</script>";
    } else {
        $sql_insert = "INSERT INTO kupon_diskon (kode_kupon, persen_diskon, status_aktif) VALUES ('$kode_kupon', $persen_diskon, 1)";
        if (mysqli_query($koneksi, $sql_insert)) {
            echo "<script>alert('Sukses! Kode kupon baru berhasil ditambahkan.'); window.location.href='index.php?page=kupon';</script>";
        }
    }
}

// ---- LOGIKA 2: PROSES UBAH STATUS AKTIF (TOGGLE) ----
if (isset($_GET['toggle_status'])) {
    $id_kupon = (int)$_GET['toggle_status'];
    $status_sekarang = (int)$_GET['current'];
    $status_baru = ($status_sekarang == 1) ? 0 : 1;

    $sql_toggle = "UPDATE kupon_diskon SET status_aktif = $status_baru WHERE id = $id_kupon";
    if (mysqli_query($koneksi, $sql_toggle)) {
        header("Location: index.php?page=kupon");
        exit();
    }
}

// ---- LOGIKA 3: PROSES HAPUS KUPON ----
if (isset($_GET['hapus_kupon'])) {
    $id_hapus = (int)$_GET['hapus_kupon'];
    $sql_delete = "DELETE FROM kupon_diskon WHERE id = $id_hapus";
    if (mysqli_query($koneksi, $sql_delete)) {
        echo "<script>window.location.href='index.php?page=kupon';</script>";
    }
}
?>

<div class="row g-4 p-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-ticket text-primary me-2"></i>Buat Kupon Promo Baru
                </h6>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Kode Kupon / Promo</label>
                        <input type="text" name="kode_kupon" class="form-control text-uppercase" placeholder="Contoh: PROMOOXY" autocomplete="off" required>
                        <small class="text-muted">Gunakan huruf kapital tanpa spasi.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Besar Potongan Diskon (%)</label>
                        <div class="input-group">
                            <input type="number" name="persen_diskon" class="form-control" min="1" max="100" placeholder="0" required>
                            <span class="input-group-text bg-primary text-white fw-bold">%</span>
                        </div>
                    </div>
                    <button type="submit" name="simpan_kupon" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Kupon
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-list-check text-success me-2"></i>Daftar Manajemen Kupon Toko
                </h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Kode Kupon</th>
                                <th class="text-center">Potongan Harga</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 14px;">
                            <?php
                            $get_data = mysqli_query($koneksi, "SELECT * FROM kupon_diskon ORDER BY id DESC");
                            $no = 1;
                            if (mysqli_num_rows($get_data) == 0) {
                                echo "<tr><td colspan='5' class='text-center text-muted py-3'>Belum ada kupon promo yang dibuat.</td></tr>";
                            } else {
                                while ($row = mysqli_fetch_assoc($get_data)) {
                                    $status_badge = ($row['status_aktif'] == 1)
                                        ? "<span class='badge bg-success-subtle text-success border border-success'>Aktif</span>"
                                        : "<span class='badge bg-danger-subtle text-danger border border-danger'>Non-Aktif</span>";

                                    $btn_status_text = ($row['status_aktif'] == 1) ? "Nonaktifkan" : "Aktifkan";
                                    $btn_status_class = ($row['status_aktif'] == 1) ? "btn-outline-warning" : "btn-outline-success";
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><strong class="text-primary text-uppercase"><?= htmlspecialchars($row['kode_kupon']) ?></strong></td>
                                        <td class="text-center fw-bold text-success"><?= $row['persen_diskon'] ?>%</td>
                                        <td class="text-center"><?= $status_badge ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?page=kupon&toggle_status=<?= $row['id'] ?>&current=<?= $row['status_aktif'] ?>" class="btn <?= $btn_status_class ?> btn-sm fw-semibold">
                                                    <?= $btn_status_text ?>
                                                </a>
                                                <a href="index.php?page=kupon&hapus_kupon=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus kupon ini?')" class="btn btn-outline-danger btn-sm">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>