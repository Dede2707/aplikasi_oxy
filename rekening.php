<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 1. PROSES UPDATE/SIMPAN REKENING PERUSAHAAN (SUPPORT BANK & QR)
// ==========================================
if (isset($_POST['update_rekening'])) {
    $tipe_rekening = mysqli_real_escape_string($koneksi, $_POST['tipe_rekening']);
    $nama_bank     = mysqli_real_escape_string($koneksi, trim($_POST['nama_bank']));
    $no_rekening   = mysqli_real_escape_string($koneksi, trim($_POST['no_rekening']));
    $qr_text       = mysqli_real_escape_string($koneksi, trim($_POST['qr_text']));
    $atas_nama     = mysqli_real_escape_string($koneksi, trim($_POST['atas_nama']));

    // Cek apakah sudah ada data rekening di tabel
    $check_rek = mysqli_query($koneksi, "SELECT * FROM rekening LIMIT 1");

    if (mysqli_num_rows($check_rek) > 0) {
        // Jika sudah ada, lakukan UPDATE semua kolom
        $save_rek = mysqli_query($koneksi, "UPDATE rekening SET 
            tipe_rekening = '$tipe_rekening',
            nama_bank = '$nama_bank', 
            no_rekening = '$no_rekening', 
            qr_text = '$qr_text', 
            atas_nama = '$atas_nama'");
    } else {
        // Jika masih kosong, lakukan INSERT baru
        $save_rek = mysqli_query($koneksi, "INSERT INTO rekening (tipe_rekening, nama_bank, no_rekening, qr_text, atas_nama) 
            VALUES ('$tipe_rekening', '$nama_bank', '$no_rekening', '$qr_text', '$atas_nama')");
    }

    if ($save_rek) {
        echo "<script>alert('Berhasil! Data Rekening & QR Perusahaan telah diperbarui.'); window.location.href='index.php?page=rekening';</script>";
    } else {
        $error_msg = mysqli_error($koneksi);
        echo "<script>alert('Gagal menyimpan! Error: " . addslashes($error_msg) . "');</script>";
    }
}

// ==========================================
// 2. PROSES UPDATE PERSENTASE DISKON LOYALITAS
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
// 3. AMBIL DATA TERBARU DARI DATABASE
// ==========================================
$get_rekening = mysqli_query($koneksi, "SELECT * FROM rekening LIMIT 1");
$data_rek = mysqli_fetch_assoc($get_rekening);

// Set default value jika data di database masih kosong
$tipe_aktif  = $data_rek['tipe_rekening'] ?? 'Bank';
$bank_aktif  = $data_rek['nama_bank'] ?? '';
$no_rek_aktif = $data_rek['no_rekening'] ?? '';
$qr_aktif    = $data_rek['qr_text'] ?? '';
$an_aktif    = $data_rek['atas_nama'] ?? '';

$get_diskon = mysqli_query($koneksi, "SELECT persen_diskon FROM setting_diskon WHERE id = 1");
$data_diskon = mysqli_fetch_assoc($get_diskon);
$diskon_aktif = $data_diskon['persen_diskon'] ?? 5;
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-gear text-primary me-2"></i>Pengaturan Utama Aplikasi</h5>
            <p class="text-muted small mb-0">Kelola informasi metode pembayaran konfirmasi (Bank / QR CODE) dan besaran diskon otomatis di sini.</p>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h6 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-credit-card text-success me-2"></i>Metode Pembayaran Resmi
            </h6>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Jenis Metode Pembayaran</label>
                    <select name="tipe_rekening" id="tipe_rekening" class="form-select fw-bold text-dark" required>
                        <option value="Bank" <?= $tipe_aktif == 'Bank' ? 'selected' : '' ?>>Transfer Bank (BCA, Mandiri, dll)</option>
                        <option value="QR" <?= $tipe_aktif == 'QR' ? 'selected' : '' ?>>QR Code / E-Wallet (QRIS, OVO, Dana, Gopay)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nama Bank / Nama E-Wallet</label>
                    <input type="text" name="nama_bank" class="form-control" placeholder="Contoh: BCA / Mandiri / QRIS / OVO" value="<?= htmlspecialchars($bank_aktif) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nomor Rekening / No. Handphone</label>
                    <input type="text" name="no_rekening" class="form-control" placeholder="Masukkan nomor rekening atau nomor HP..." value="<?= htmlspecialchars($no_rek_aktif) ?>" required>
                </div>

                <div class="mb-3" id="input_qr_group">
                    <label class="form-label text-muted small fw-bold">Link QRIS / Kode QR (Optional)</label>
                    <input type="text" name="qr_text" class="form-control" placeholder="Masukkan link gambar QRIS atau kode QR..." value="<?= htmlspecialchars($qr_aktif) ?>">
                    <small class="text-muted d-block mt-1">Bisa diisi link gambar QRIS internal milik perusahaan.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Atas Nama Pemilik (A/N)</label>
                    <input type="text" name="atas_nama" class="form-control" placeholder="Contoh: PT. Oxywater Sukses Mandiri" value="<?= htmlspecialchars($an_aktif) ?>" required>
                </div>

                <button type="submit" name="update_rekening" class="btn btn-success fw-semibold w-100 py-2 mt-3">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Metode Pembayaran
                </button>
            </form>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-4 h-100 border-start border-primary border-4">
            <h6 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-percent text-primary me-2"></i>Diskon Pelanggan Loyal
            </h6>
            <p class="text-muted small">Tentukan berapa persen potongan harga otomatis yang akan diterima oleh pelanggan jika mereka terdeteksi sudah berbelanja lebih dari 3 kali.</p>

            <form action="" method="POST" class="mt-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Besar Potongan Diskon :</label>
                    <div class="input-group">
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tipeSelect = document.getElementById("tipe_rekening");
        const qrGroup = document.getElementById("input_qr_group");

        function toggleQRInput() {
            if (tipeSelect.value === "QR") {
                qrGroup.style.display = "block";
            } else {
                qrGroup.style.display = "none";
            }
        }

        toggleQRInput();
        tipeSelect.addEventListener("change", toggleQRInput);
    });
</script>