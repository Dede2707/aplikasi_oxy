<?php
// Proteksi: Mencegah file diakses langsung tanpa melalui index.php
if (!isset($level_user) || $level_user !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
    exit();
}

// =========================================================================
// SCRIPT OTOMATIS: MEMBUAT / UPDATE TABEL REKENING (DENGAN KOLOM QRIS)
// =========================================================================
$auto_create_table = "CREATE TABLE IF NOT EXISTS rekening (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_bank VARCHAR(50) NOT NULL,
    no_rekening VARCHAR(50) NOT NULL,
    atas_nama VARCHAR(100) NOT NULL,
    teks_qris TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $auto_create_table);

// Cek apakah kolom teks_qris sudah ada, jika belum tambah otomatis (untuk database lama)
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM rekening LIKE 'teks_qris'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE rekening ADD teks_qris TEXT NULL AFTER atas_nama");
}
// =========================================================================


// ==========================================
// 1. PROSES SIMPAN DATA (JIKA FORM DIKIRIM)
// ==========================================
if (isset($_POST['btn_simpan'])) {
    $nama_bank   = mysqli_real_escape_string($koneksi, $_POST['nama_bank']);
    $no_rekening = mysqli_real_escape_string($koneksi, $_POST['no_rekening']);
    $atas_nama   = mysqli_real_escape_string($koneksi, $_POST['atas_nama']);
    $teks_qris   = mysqli_real_escape_string($koneksi, $_POST['teks_qris']);

    if (!empty($nama_bank) && !empty($atas_nama)) {
        // Jika memilih tipe QRIS, nomor rekening bisa dikosongkan atau diisi '-' otomatis
        if ($nama_bank === 'QRIS' && empty($no_rekening)) {
            $no_rekening = '-';
        }

        $query_insert = "INSERT INTO rekening (nama_bank, no_rekening, atas_nama, teks_qris) 
                         VALUES ('$nama_bank', '$no_rekening', '$atas_nama', '$teks_qris')";

        if (mysqli_query($koneksi, $query_insert)) {
            echo "<script>alert('Data metode pembayaran berhasil ditambahkan!'); window.location.href='index.php?page=rekening';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "');</script>";
        }
    } else {
        echo "<script>alert('Nama Bank/Metode dan Atas Nama wajib diisi!');</script>";
    }
}

// ==========================================
// 2. PROSES HAPUS DATA (JIKA TOMBOL HAPUS DIKLIK)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['id']);

    $query_hapus = "DELETE FROM rekening WHERE id = '$id_hapus'";
    if (mysqli_query($koneksi, $query_hapus)) {
        echo "<script>alert('Data berhasil dihapus!'); window.location.href='index.php?page=rekening';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-credit-card text-primary me-2"></i>Rekening & QRIS Perusahaan
            </h5>
            <p class="text-muted small mb-0">Kelola akun bank resmi dan payload/link QRIS PT. NANOPLEX INDONESIA untuk pembayaran otomatis pelanggan.</p>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-plus text-success me-2"></i>Tambah Metode Pembayaran
                </h6>
                <hr>

                <form action="index.php?page=rekening" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tipe / Nama Bank</label>
                        <select name="nama_bank" id="pilihan_tipe" class="form-select" onchange="sesuaikanForm()" required>
                            <option value="BCA">Bank BCA</option>
                            <option value="Mandiri">Bank Mandiri</option>
                            <option value="BRI">Bank BRI</option>
                            <option value="BNI">Bank BNI</option>
                            <option value="QRIS">QRIS (Gunakan Data Payload/Link)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="box_no_rekening">
                        <label class="form-label small fw-semibold text-secondary">Nomor Rekening</label>
                        <input type="text" name="no_rekening" id="input_no_rek" class="form-control" placeholder="Contoh: 8830123xxx" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Atas Nama (Pemilik)</label>
                        <input type="text" name="atas_nama" class="form-control" placeholder="Contoh: PT Nanoplex Indonesia" required>
                    </div>

                    <div class="mb-3 d-none" id="box_teks_qris">
                        <label class="form-label small fw-semibold text-danger">Data / Payload QRIS (Opsional)</label>
                        <textarea name="teks_qris" class="form-control" rows="3" placeholder="Masukkan teks data QRIS string isi dari QR Code Anda, atau link static URL QRIS perusahaan Anda"></textarea>
                        <div class="form-text text-muted small" style="font-size: 11px;">Jika diisi teks mentah QRIS, sistem invoice pelanggan dapat membuat QR Code otomatis secara dinamis.</div>
                    </div>

                    <button type="submit" name="btn_simpan" class="btn btn-primary w-100 fw-semibold mt-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Metode
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-list text-primary me-2"></i>Daftar Rekening & QRIS Aktif
                </h6>
                <hr>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th width="5%">No</th>
                                <th>Metode/Bank</th>
                                <th>Nomor Rekening / QRIS</th>
                                <th>Atas Nama</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            $no = 1;
                            $sql_rekening = "SELECT * FROM rekening ORDER BY id DESC";
                            $query_rekening = mysqli_query($koneksi, $sql_rekening);

                            if ($query_rekening && mysqli_num_rows($query_rekening) > 0) {
                                while ($row = mysqli_fetch_assoc($query_rekening)) {
                                    $is_qris = (strtoupper($row['nama_bank']) === 'QRIS');
                            ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <span class="badge <?= $is_qris ? 'bg-danger text-white' : 'bg-info text-dark' ?> fw-bold">
                                                <?= htmlspecialchars($row['nama_bank']); ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            <?php if ($is_qris): ?>
                                                <span class="text-muted italic small"><i class="fa-solid fa-qrcode me-1 text-danger"></i> QRIS Aktif</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['no_rekening']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['atas_nama']); ?></td>
                                        <td class="text-center">
                                            <a href="index.php?page=rekening&action=hapus&id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus metode pembayaran ini?')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data rekening bank atau QRIS yang terdaftar.</td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function sesuaikanForm() {
        const pilihan = document.getElementById('pilihan_tipe').value;
        const boxNoRek = document.getElementById('box_no_rekening');
        const inputNoRek = document.getElementById('input_no_rek');
        const boxTeksQris = document.getElementById('box_teks_qris');

        if (pilihan === 'QRIS') {
            boxNoRek.style.display = 'none';
            inputNoRek.removeAttribute('required');
            boxTeksQris.classList.remove('d-none');
        } else {
            boxNoRek.style.display = 'block';
            inputNoRek.setAttribute('required', 'required');
            boxTeksQris.classList.add('d-none');
        }
    }

    // Jalankan fungsi saat halaman pertama kali dimuat untuk memastikan sinkronisasi form awal
    document.addEventListener("DOMContentLoaded", function() {
        sesuaikanForm();
    });
</script>