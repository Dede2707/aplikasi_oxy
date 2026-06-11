<?php
include "koneksi.php";

// Proses Update
if (isset($_POST['update_pengaturan'])) {
    $bank = mysqli_real_escape_string($koneksi, $_POST['bank']);
    $rekening = mysqli_real_escape_string($koneksi, $_POST['no_rekening']);
    $pemilik = mysqli_real_escape_string($koneksi, $_POST['nama_pemilik']);

    // Logika Upload QR (Opsional)
    if (!empty($_FILES['qr_file']['name'])) {
        $nama_file = time() . '_' . $_FILES['qr_file']['name'];
        move_uploaded_file($_FILES['qr_file']['tmp_name'], 'uploads/' . $nama_file);
        mysqli_query($koneksi, "UPDATE pengaturan_perusahaan SET qr_file='$nama_file' WHERE id=1");
    }

    mysqli_query($koneksi, "UPDATE pengaturan_perusahaan SET bank_nama='$bank', no_rekening='$rekening', nama_pemilik='$pemilik' WHERE id=1");
    echo "<script>alert('Pengaturan berhasil diperbarui!'); window.location='pengaturan.php';</script>";
}

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan_perusahaan WHERE id=1"));
?>
<!-- Buat Form Sederhana di sini -->
<form method="POST" enctype="multipart/form-data">
    <label>Nama Bank</label>
    <input type="text" name="bank" value="<?= $data['bank_nama'] ?>" class="form-control">
    <label>No Rekening</label>
    <input type="text" name="no_rekening" value="<?= $data['no_rekening'] ?>" class="form-control">
    <label>Nama Pemilik</label>
    <input type="text" name="nama_pemilik" value="<?= $data['nama_pemilik'] ?>" class="form-control">
    <label>Ganti QR Code</label>
    <input type="file" name="qr_file" class="form-control">
    <button type="submit" name="update_pengaturan" class="btn btn-primary mt-3">Simpan Perubahan</button>
</form>