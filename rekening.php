<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 1. PROSES UPDATE PERSENTASE DISKON LOYALITAS
// ==========================================
if (isset($_POST['update_diskon'])) {
    $diskon_baru = (int)$_POST['persen_diskon'];

    if ($diskon_baru >= 1 && $diskon_baru <= 10) {
        $update = mysqli_query($koneksi, "UPDATE setting_diskon SET persen_diskon = $diskon_baru WHERE id = 1");
        if ($update) {
            echo "<script>alert('Persentase diskon loyalitas berhasil diubah menjadi $diskon_baru%!'); window.location.href='index.php?page=rekening';</script>";
        }
    } else {
        echo "<script>alert('Gagal! Diskon harus bernilai antara 1% sampai 10%.');</script>";
    }
}

// ==========================================
// 2. PROSES UPDATE/SIMPAN REKENING PERUSAHAAN
// ==========================================
if (isset($_POST['update_rekening'])) {
    $nama_bank = mysqli_real_escape_string($koneksi, $_POST['nama_bank']);
    $no_rekening = mysqli_real_escape_string($koneksi, $_POST['no_rekening']);
    $atas_nama = mysqli_real_escape_string($koneksi, $_POST['atas_nama']);

    // Cek apakah data rekening sudah ada atau belum
    $check_rek = mysqli_query($koneksi, "SELECT * FROM rekening LIMIT 1");
    if (mysqli_num_rows($check_rek) > 0) {
        // Jika sudah ada, lakukan UPDATE
        $save_rek = mysqli_query($koneksi, "UPDATE rekening SET nama_bank='$nama_bank', no_rekening='$no_rekening', atas_nama='$atas_nama'");
    } else {
        // Jika masih kosong, lakukan INSERT
        $save_rek = mysqli_query($koneksi, "INSERT INTO rekening (nama_bank, no_rekening, atas_nama) VALUES ('$nama_bank', '$no_rekening', '$atas_nama')");
    }

    if ($save_rek) {
        echo "<script>alert('Data Rekening Perusahaan berhasil diperbarui!'); window.location.href='index.php?page=rekening';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data rekening.');</script>";
    }
}

// ==========================================
// 3. AMBIL DATA TERBARU DARI DATABASE
// ==========================================
// Ambil data diskon
$get_diskon = mysqli_query($koneksi, "SELECT persen_diskon FROM setting_diskon WHERE id = 1");
$data_diskon = mysqli_fetch_assoc($get_diskon);
$diskon_aktif = $data_diskon['persen_diskon'] ?? 5;

// Ambil data rekening
$get_rekening = mysqli_query($koneksi, "SELECT * FROM rekening LIMIT 1");
$data_rek = mysqli_fetch_assoc($get_rekening);
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-gear text-primary me-2"></i>Pengaturan Khusus Administrator</h5>
            <p class="text-muted small mb-0">Kelola informasi rekening resmi perusahaan dan konfigurasi diskon otomatis aplikasi di sini.</p>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h6 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-credit-card text-success me-2"></i>Rekening Resmi Perusahaan
            </h6>
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nama Bank / Dompet Digital</label>
                    <input type="text" name="nama_bank" class="form-control" placeholder="Contoh: BCA, Mandiri, BRI, OVO" value="<?= htmlspecialchars($data_rek['nama_bank'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nomor Rekening / No. HP</label>
                    <input type="text" name="no_rekening" class="form-control" placeholder="Masukkan nomor rekening..." value="<?= htmlspecialchars($data_rek['no_rekening'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Atas Nama (Pemilik)</label>
                    <input type="text" name="atas_nama" class="form-control" placeholder="Contoh: PT. Nanoplex Indonesia" value="<?= htmlspecialchars($data_rek['atas_nama'] ?? '') ?>" required>
                </div>
                <button type="submit" name="update_rekening" class="btn btn-success fw-semibold w-100 py-2">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Rekening
                </button>
            </form>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-4 h-100 border-start border-primary border-4">
            <h6 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-percent text-primary me-2"></i>Diskon Pelanggan Loyal (> 3x Belanja)
            </h6>
            <p class="text-muted small">Tentukan berapa persen potongan harga otomatis yang akan diterima oleh pelanggan jika mereka terdeteksi sudah berbelanja lebih dari 3 kali.</p>

            <form action="" method="POST" class="mt-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Besar Potongan Diskon :</label>
                    <div class="input-group style-input-group">
                        <input type="number" name="persen_diskon" class="form-control form-control-lg fw-bold text-center text-primary" min="1" max="10" value="<?= $diskon_aktif ?>" required>
                        <span class="input-group-text bg-primary text-white fw-bold">%</span>
                    </div>
                    <small class="text-muted d-block mt-2">* Batas inputan diskon valid yang diizinkan sistem: **1% sampai 10%**.</small>
                </div>
                <button type="submit" name="update_diskon" class="btn btn-primary fw-semibold w-100 py-2 mt-2">
                    <i class="fa-solid fa-check-double me-1"></i> Terapkan Aturan Diskon
                </button>
            </form>
        </div>
    </div>
</div>