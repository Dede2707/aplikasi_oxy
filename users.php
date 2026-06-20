<?php
// --- PROSES SIMPAN / TAMBAH USER BARU ---
if (isset($_POST['tambah_user'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = md5($_POST['password']);
    $level        = mysqli_real_escape_string($koneksi, $_POST['level']);
    $no_rekening  = mysqli_real_escape_string($koneksi, $_POST['no_rekening']);

    // Logika Upload Gambar QR Code
    $qr_name = NULL;
    if (isset($_FILES['qr_pembayaran']) && $_FILES['qr_pembayaran']['error'] === 0) {
        $nama_file = $_FILES['qr_pembayaran']['name'];
        $tmp_file  = $_FILES['qr_pembayaran']['tmp_name'];
        $ekstensi  = pathinfo($nama_file, PATHINFO_EXTENSION);

        // Beri nama unik agar tidak bentrok (contoh: qr_17182910.png)
        $qr_name   = "qr_" . time() . "." . $ekstensi;
        $tujuan    = "assets/img/" . $qr_name; // Pastikan folder assets/img/ sudah ada

        // Buat folder jika belum ada
        if (!is_dir('assets/img')) {
            mkdir('assets/img', 0777, true);
        }

        move_uploaded_file($tmp_file, $tujuan);
    }

    $sql_insert = "INSERT INTO users (nama_lengkap, username, password, level, no_rekening, qr_pembayaran) 
                   VALUES ('$nama_lengkap', '$username', '$password', '$level', '$no_rekening', '$qr_name')";

    if (mysqli_query($koneksi, $sql_insert)) {
        echo "<script>alert('User dan Metode Pembayaran berhasil ditambahkan!'); window.location.href='index.php?page=users';</script>";
    } else {
        echo "<script>alert('Gagal menambah user: " . mysqli_error($koneksi) . "');</script>";
    }
}

// --- PROSES HAPUS USER ---
if (isset($_GET['hapus'])) {
    $id_user = (int)$_GET['hapus'];

    // Hapus juga file gambar QR dari folder sebelum data di database dihapus
    $cek_gambar = mysqli_query($koneksi, "SELECT qr_pembayaran FROM users WHERE id_user = $id_user");
    $data_gbr = mysqli_fetch_assoc($cek_gambar);
    if (!empty($data_gbr['qr_pembayaran']) && file_exists("assets/img/" . $data_gbr['qr_pembayaran'])) {
        unlink("assets/img/" . $data_gbr['qr_pembayaran']);
    }

    mysqli_query($koneksi, "DELETE FROM users WHERE id_user = $id_user");
    echo "<script>alert('User berhasil dihapus!'); window.location.href='index.php?page=users';</script>";
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 text-dark fw-bold"><i class="fa-solid fa-users-gear me-2 text-primary"></i>Manajemen User & Pembayaran</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manajemen User</li>
    </ol>

    <div class="row g-4">
        <!-- KOLOM KIRI: TABEL DAFTAR USER & PEMBAYARAN -->
        <div class="col-12 col-lg-8">
            <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-dark text-white py-3" style="border-radius: 12px 12px 0 0;">
                    <i class="fas fa-users me-1"></i> Daftar Hak Akses & Info Pembayaran
                </div>
                <div class="card-body bg-white text-dark p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="py-3 px-3" style="width: 5%;">No</th>
                                    <th class="py-3">Nama Lengkap / Level</th>
                                    <th class="py-3">Username</th>
                                    <th class="py-3">No. Rekening</th>
                                    <th class="py-3 text-center">QR Code</th>
                                    <th class="py-3 text-center" style="width: 12%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $query_user = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id_user DESC");
                                if ($query_user && mysqli_num_rows($query_user) > 0) {
                                    while ($row = mysqli_fetch_assoc($query_user)) {
                                ?>
                                        <tr>
                                            <td class="px-3 fw-bold"><?= $no++; ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($row['nama_lengkap']); ?></div>
                                                <span class="badge bg-primary-subtle text-primary" style="font-size: 11px;"><?= $row['level']; ?></span>
                                            </td>
                                            <td class="text-muted"><?= htmlspecialchars($row['username']); ?></td>
                                            <td class="font-monospace fw-bold text-success">
                                                <?= !empty($row['no_rekening']) ? htmlspecialchars($row['no_rekening']) : '<span class="text-muted fw-normal opacity-50">-</span>'; ?>
                                            </td>
                                            <!-- Kolom QR Code Gambar -->
                                            <td class="text-center">
                                                <?php if (!empty($row['qr_pembayaran']) && file_exists("assets/img/" . $row['qr_pembayaran'])): ?>
                                                    <a href="assets/img/<?= $row['qr_pembayaran']; ?>" target="_blank">
                                                        <img src="assets/img/<?= $row['qr_pembayaran']; ?>" alt="QR" class="img-thumbnail" style="max-height: 50px; cursor: pointer;">
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small opacity-50">Tidak ada QR</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="index.php?page=users&hapus=<?= $row['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Belum ada data user.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: FORM TAMBAH USER BARU (MENDUKUNG UPLOAD FILE) -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-primary text-white py-3" style="border-radius: 12px 12px 0 0;">
                    <i class="fa-solid fa-user-plus me-1"></i> Tambah Pengguna Baru
                </div>
                <!-- PENTING: Menggunakan enctype="multipart/form-data" agar bisa mengupload gambar QR -->
                <div class="card-body bg-white text-dark p-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Nama Lengkap Pengguna</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama asli petugas" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Username Login</label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: admin_gudang" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password sandi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Level Hak Akses</label>
                            <select name="level" class="form-select" required>
                                <option value="Admin">Manajer</option>
                                <option value="Staff Kasir">Staff Kasir</option>
                                <option value="Gudang">Gudang</option>
                            </select>
                        </div>





                        <button type="submit" name="tambah_user" class="btn btn-primary w-100 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Anggota Baru
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>