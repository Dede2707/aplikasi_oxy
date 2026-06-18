<?php
// Pastikan timezone sudah diset ke WIB
date_default_timezone_set('Asia/Jakarta');

// ---- LOGIKA 1: PROSES SIMPAN DATA PENJUALAN + POTONG STOK ----
if (isset($_POST['simpan_penjualan'])) {
    $tgl_penjualan  = $_POST['tgl_penjualan'] . ' ' . date('H:i:s');
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $telepon        = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    $alamat_kirim   = mysqli_real_escape_string($koneksi, $_POST['alamat_kirim']);
    $nama_produk    = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $jumlah_produk  = (int)$_POST['jumlah_produk'];
    $total_harga    = (int)$_POST['total_harga'];

    // Menyisipkan Nomor Telepon ke dalam kolom alamat_kirim agar masuk database tunggal
    $format_alamat_kirim = "Telp: " . $telepon . " | Produk: " . $nama_produk . " | Alamat: " . $alamat_kirim . " | [STATUS: Lunas]";

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
        $sql_insert = "INSERT INTO penjualan (tgl_penjualan, nama_pelanggan, alamat_kirim, jumlah_produk, total_harga) 
                       VALUES ('$tgl_penjualan', '$nama_pelanggan', '$format_alamat_kirim', '$jumlah_produk', '$total_harga')";

        if (mysqli_query($koneksi, $sql_insert)) {
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

// ---- LOGIKA 2: PROSES UPDATE/EDIT DATA PENJUALAN ----
if (isset($_POST['update_penjualan'])) {
    $id_penjualan   = (int)$_POST['id_penjualan'];
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $alamat_kirim   = mysqli_real_escape_string($koneksi, $_POST['alamat_kirim']);
    $jumlah_produk  = (int)$_POST['jumlah_produk'];
    $total_harga    = (int)$_POST['total_harga'];

    $sql_update = "UPDATE penjualan SET 
                    nama_pelanggan = '$nama_pelanggan', 
                    alamat_kirim = '$alamat_kirim', 
                    jumlah_produk = '$jumlah_produk', 
                    total_harga = '$total_harga' 
                   WHERE id_penjualan = $id_penjualan";

    if (mysqli_query($koneksi, $sql_update)) {
        echo "<script>alert('Data penjualan berhasil diperbarui!'); window.location.href='index.php?page=penjualan';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// ---- LOGIKA 3: PROSES HAPUS DATA PENJUALAN ----
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $sql_delete = "DELETE FROM penjualan WHERE id_penjualan = $id_hapus";
    if (mysqli_query($koneksi, $sql_delete)) {
        echo "<script>window.location.href='index.php?page=penjualan';</script>";
    }
}
?>

<style>
    /* CSS Khusus Cetak Dokumen */
    @media print {
        body * {
            visibility: hidden;
        }

        .print-faktur-active #areaCetakFaktur,
        .print-faktur-active #areaCetakFaktur * {
            visibility: visible;
        }

        .print-faktur-active #areaCetakFaktur {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            color: #000 !important;
            background: #fff !important;
        }

        .print-struk-active #areaCetakStruk,
        .print-struk-active #areaCetakStruk * {
            visibility: visible;
        }

        .print-struk-active #areaCetakStruk {
            position: absolute;
            left: 0;
            top: 0;
            width: 58mm;
            font-family: 'Courier New', Courier, monospace !important;
            color: #000 !important;
            background: #fff !important;
            padding: 5px;
            font-size: 11px !important;
        }

        .no-print {
            display: none !important;
        }
    }

    .struk-kasir {
        font-family: 'Courier New', Courier, monospace;
        background: #fdfdfd;
        border: 1px solid #ddd;
        padding: 15px;
        color: #000;
        max-width: 320px;
        margin: 0 auto;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="row g-4 p-3">
    <div class="col-12 col-xl-4 no-print">
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
                        <label>Nama Pelanggan / Pembeli</label>
                        <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control" autocomplete="off" placeholder="Ketik nama pelanggan..." required>
                        <div id="info_diskon_loyal" class="mt-1 small"></div>
                    </div>
                    <div class="mb-3">
                        <label>Nomor Telepon</label>
                        <input type="tel" name="telepon" class="form-control" placeholder="Contoh: 08123456xxx" required>
                    </div>
                    <div class="mb-3">
                        <label>Alamat Tujuan Pelanggan</label>
                        <textarea name="alamat_kirim" id="alamat_kirim" class="form-control" rows="2" placeholder="Ketik alamat pengiriman lengkap..." required></textarea>
                    </div>
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
                    <div class="mb-3">
                        <label>Total Yang Harus Dibayar</label>
                        <input type="number" name="total_harga" id="total_harga_akhir" class="form-control fw-bold text-success" readonly>
                    </div>
                    <button type="submit" name="simpan_penjualan" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Penjualan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8 no-print">
        <div class="card border-0 shadow-sm mb-4 border-start border-danger border-3">
            <div class="card-body p-4">
                <h6 class="fw-bold text-danger mb-3">
                    <i class="fa-solid fa-bullhorn me-2"></i>Laporan Keluhan & Masalah Pelanggan
                </h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-danger text-dark small text-uppercase" style="font-size: 11px;">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Tanggal Laporan</th>
                                <th>Nama Pelapor</th>
                                <th>Isi Pengaduan / Kontak</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <?php
                            $q_komplain = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE jumlah_produk = 0 AND total_harga = 0 ORDER BY tgl_penjualan DESC");
                            $no_k = 1;
                            if (mysqli_num_rows($q_komplain) > 0) {
                                while ($rk = mysqli_fetch_assoc($q_komplain)) {
                            ?>
                                    <tr>
                                        <td><?= $no_k++ ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($rk['tgl_penjualan'])) ?> WIB</td>
                                        <td class="fw-bold text-danger"><?= htmlspecialchars($rk['nama_pelanggan']) ?></td>
                                        <td class="text-wrap text-break"><span class="badge bg-warning text-dark mb-1">Aduan</span><br><?= htmlspecialchars($rk['alamat_kirim']) ?></td>
                                        <td class="text-center">
                                            <a href="index.php?page=penjualan&hapus=<?= $rk['id_penjualan'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Tandai laporan keluhan ini sudah selesai ditangani?')">
                                                <i class="fa-solid fa-check"></i> Selesai
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center text-muted py-3'>Tidak ada keluhan aktif dari pelanggan.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                                <th>No. Nota</th>
                                <th>Tanggal & Jam</th>
                                <th>Pelanggan</th>
                                <th>Rincian Order & Alamat</th>
                                <th class="text-center">Qty</th>
                                <th>Total Harga</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 14px;">
                            <?php
                            $sql_select = "SELECT * FROM penjualan WHERE jumlah_produk > 0 ORDER BY tgl_penjualan DESC";
                            $query_select = mysqli_query($koneksi, $sql_select);
                            $no = 1;

                            if (mysqli_num_rows($query_select) > 0) {
                                while ($row = mysqli_fetch_assoc($query_select)) {
                            ?>
                                    <tr>
                                        <td class="text-muted"><?= $no++ ?></td>
                                        <td>
                                            <span class="badge bg-primary px-2 py-1fw-bold" style="font-size: 12px;">
                                                #<?= $row['id_penjualan'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['tgl_penjualan'])) ?> WIB</td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                        <td class="text-wrap" style="max-width: 250px;"><small class="text-muted"><?= htmlspecialchars($row['alamat_kirim']) ?></small></td>
                                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= $row['jumlah_produk'] ?> Dus</span></td>
                                        <td class="fw-bold text-success">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?page=penjualan&faktur=<?= $row['id_penjualan'] ?>" class="btn btn-outline-info" title="Faktur / Cetak Struk">
                                                    <i class="fa-solid fa-receipt"></i> Struk
                                                </a>
                                                <a href="index.php?page=penjualan&edit=<?= $row['id_penjualan'] ?>" class="btn btn-outline-warning" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="index.php?page=penjualan&hapus=<?= $row['id_penjualan'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus data penjualan ini?')" title="Hapus">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center text-muted py-4'>Belum ada transaksi penjualan yang dicatat.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['edit'])):
    $id_edit = (int)$_GET['edit'];
    $q_edit = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE id_penjualan = $id_edit");
    $d_edit = mysqli_fetch_assoc($q_edit);
    if ($d_edit):
?>
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Data Penjualan</h5>
                        <a href="index.php?page=penjualan" class="btn-close btn-close-white"></a>
                    </div>
                    <form action="index.php?page=penjualan" method="POST">
                        <div class="modal-body text-dark">
                            <input type="hidden" name="id_penjualan" value="<?= $d_edit['id_penjualan']; ?>">
                            <div class="mb-3">
                                <label class="small fw-bold">Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" class="form-control" value="<?= htmlspecialchars($d_edit['nama_pelanggan']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Rincian Kontak & Alamat Tujuan Kirim</label>
                                <textarea name="alamat_kirim" class="form-control" rows="3" required><?= htmlspecialchars($d_edit['alamat_kirim']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small fw-bold">Kuantitas (Qty)</label>
                                        <input type="number" name="jumlah_produk" class="form-control" value="<?= $d_edit['jumlah_produk']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="small fw-bold">Total Harga (Rp)</label>
                                        <input type="number" name="total_harga" class="form-control" value="<?= $d_edit['total_harga']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <a href="index.php?page=penjualan" class="btn btn-secondary btn-sm">Batal</a>
                            <button type="submit" name="update_penjualan" class="btn btn-warning btn-sm text-white fw-bold">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
<?php endif;
endif; ?>

<?php
if (isset($_GET['faktur'])):
    $id_faktur = (int)$_GET['faktur'];
    $q_faktur = mysqli_query($koneksi, "SELECT * FROM penjualan WHERE id_penjualan = $id_faktur");
    $d_faktur = mysqli_fetch_assoc($q_faktur);
    if ($d_faktur):
?>
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6);" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white no-print">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-print me-2"></i>Pilih Format Cetak Nota</h5>
                        <a href="index.php?page=penjualan" class="btn-close btn-close-white"></a>
                    </div>

                    <div class="modal-body bg-light text-dark row g-3">
                        <div class="col-12 col-md-7 no-print">
                            <div class="p-3 border rounded bg-white shadow-sm" id="areaCetakFaktur">
                                <div class="text-center mb-3">
                                    <h5 class="fw-bold text-primary mb-0">OXYWATER INDONESIA</h5>
                                    <small style="font-size:11px;" class="text-muted">Faktur Penjualan Resmi #OXY-<?= $d_faktur['id_penjualan'] ?></small>
                                    <hr class="my-1">
                                </div>
                                <table class="w-100 mb-2" style="font-size:12px;">
                                    <tr>
                                        <td width="30%">Pelanggan</td>
                                        <td>: <?= htmlspecialchars($d_faktur['nama_pelanggan']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal</td>
                                        <td>: <?= date('d/m/Y H:i', strtotime($d_faktur['tgl_penjualan'])) ?> WIB</td>
                                    </tr>
                                    <tr>
                                        <td>Detail Tujuan</td>
                                        <td>: <?= htmlspecialchars($d_faktur['alamat_kirim']) ?></td>
                                    </tr>
                                </table>
                                <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Deskripsi</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Produk Air Mineral Oxywater</td>
                                            <td class="text-center"><?= $d_faktur['jumlah_produk'] ?> Dus</td>
                                            <td class="text-end">Rp <?= number_format($d_faktur['total_harga'], 0, ',', '.') ?></td>
                                        </tr>
                                        <tr class="fw-bold">
                                            <td colspan="2" class="text-end">TOTAL LUNAS:</td>
                                            <td class="text-end text-success">Rp <?= number_format($d_faktur['total_harga'], 0, ',', '.') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-12 col-md-5">
                            <div class="struk-kasir" id="areaCetakStruk">
                                <div class="text-center">
                                    <b style="font-size:14px;">OXYWATER KASIR</b><br>
                                    <small>PT. OXYWATER INDONESIA</small><br>
                                    <small>JL. UTAMA NO. 88 JAKARTA</small><br>
                                    <span>--------------------------------</span>
                                </div>
                                <div style="font-size:11px;">
                                    <span>No : OXY-<?= $d_faktur['id_penjualan'] ?></span><br>
                                    <span>Tgl : <?= date('d/m/Y H:i', strtotime($d_faktur['tgl_penjualan'])) ?></span><br>
                                    <span>Kasir: Admin System</span><br>
                                    <span>Plg : <?= htmlspecialchars(substr($d_faktur['nama_pelanggan'], 0, 15)) ?></span><br>
                                    <span>Info : <?= htmlspecialchars(substr($d_faktur['alamat_kirim'], 0, 30)) ?>...</span><br>
                                    <span>--------------------------------</span>
                                </div>
                                <div style="font-size:11px;">
                                    <span>Oxywater Air Mineral Premium</span><br>
                                    <span> <?= $d_faktur['jumlah_produk'] ?> DUS x Rp <?= number_format(($d_faktur['total_harga'] / max(1, $d_faktur['jumlah_produk'])), 0, ',', '.') ?></span>
                                    <span style="float:right;">Rp <?= number_format($d_faktur['total_harga'], 0, ',', '.') ?></span><br>
                                    <span>--------------------------------</span>
                                </div>
                                <div style="font-size:11px;">
                                    <b>TOTAL <span style="float:right;">Rp <?= number_format($d_faktur['total_harga'], 0, ',', '.') ?></span></b><br>
                                    <span>BAYAR (CASH) <span style="float:right;">Rp <?= number_format($d_faktur['total_harga'], 0, ',', '.') ?></span></span><br>
                                    <span>KEMBALI <span style="float:right;">Rp 0</span></span><br>
                                    <span>--------------------------------</span>
                                </div>
                                <div class="text-center" style="font-size:10px; margin-top:5px;">
                                    <span>* TERCATAT LUNAS *</span><br>
                                    <span>TERIMA KASIH SEHAT SELALU</span><br>
                                    <span>LAYANAN KONSUMEN: 0812345678</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light no-print">
                        <a href="index.php?page=penjualan" class="btn btn-secondary btn-sm">Batal</a>
                        <button onclick="document.body.classList.add('print-faktur-active'); document.body.classList.remove('print-struk-active'); window.print();" class="btn btn-info btn-sm fw-bold">
                            <i class="fa-solid fa-file-invoice me-1"></i> Cetak Faktur (A4)
                        </button>
                        <button onclick="document.body.classList.add('print-struk-active'); document.body.classList.remove('print-faktur-active'); window.print();" class="btn btn-success btn-sm fw-bold">
                            <i class="fa-solid fa-print me-1"></i> Cetak Struk Kasir (Mini)
                        </button>
                    </div>
                </div>
            </div>
        </div>
<?php endif;
endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const pilihProduk = document.getElementById('pilih_produk');
        const jumlahProduk = document.getElementById('jumlah_produk');
        const inputHargaAkhir = document.getElementById("total_harga_akhir");
        const inputNama = document.getElementById("nama_pelanggan");
        const infoDiskon = document.getElementById("info_diskon_loyal");

        let persenDiskonAktif = 0;

        function hitungTotal() {
            const pilihanAktif = pilihProduk.options[pilihProduk.selectedIndex];
            const hargaPerDus = pilihanAktif && pilihanAktif.getAttribute('data-harga') ? parseInt(pilihanAktif.getAttribute('data-harga')) : 0;
            const qty = jumlahProduk.value ? parseInt(jumlahProduk.value) : 0;

            let totalAsli = hargaPerDus * qty;

            if (persenDiskonAktif > 0) {
                let nominalPotongan = (totalAsli * persenDiskonAktif) / 100;
                if (inputHargaAkhir) inputHargaAkhir.value = Math.round(totalAsli - nominalPotongan);
            } else {
                if (inputHargaAkhir) inputHargaAkhir.value = totalAsli;
            }
        }

        if (pilihProduk) pilihProduk.addEventListener('change', hitungTotal);
        if (jumlahProduk) jumlahProduk.addEventListener('input', hitungTotal);

        if (inputNama) {
            inputNama.addEventListener("change", function() {
                let namaInput = this.value;

                if (namaInput.trim() === "") {
                    persenDiskonAktif = 0;
                    if (infoDiskon) infoDiskon.innerHTML = "";
                    hitungTotal();
                    return;
                }

                fetch('cek_loyalitas.php?nama=' + encodeURIComponent(namaInput))
                    .then(response => response.json())
                    .then(data => {
                        if (data.status_loyal === true) {
                            persenDiskonAktif = data.potongan_persen;
                            if (infoDiskon) {
                                infoDiskon.innerHTML = `<div class="alert alert-warning py-1 px-2 mt-2 mb-0 d-inline-block rounded small fw-bold">
                                <i class="fa-solid fa-crown text-danger me-1"></i> Pelanggan Loyal Terdeteksi! Dapat Diskon ${data.potongan_persen}% (Total Order Ke-${data.total_transaksi + 1})
                            </div>`;
                            }
                        } else {
                            persenDiskonAktif = 0;
                            if (infoDiskon) {
                                infoDiskon.innerHTML = `<small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info me-1"></i> Pelanggan Umum (Baru order ${data.total_transaksi} kali)</small>`;
                            }
                        }
                        hitungTotal();
                    })
                    .catch(err => {
                        console.error("Gagal memuat sistem cek_loyalitas.php.", err);
                    });
            });
        }
    });
</script>