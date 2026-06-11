<?php
// Pastikan session dimulai di paling atas file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "koneksi.php";

$pesan_sukses = "";
$pesan_gagal = "";
$tampilan = "form_input"; // Default tampilan awal
$order_review = null;
$order_final = null;

// =========================================================================
// SAKLAR 1: TAMPILKAN PREVIEW & INSTRUKSI BAYAR (BELUM MASUK DATABASE)
// =========================================================================
if (isset($_POST['proses_pembayaran'])) {
    $items_json = isset($_POST['items_keranjang']) ? $_POST['items_keranjang'] : '[]';
    $list_items = json_decode($items_json, true);

    // Proteksi jika keranjang kosong
    if (empty($list_items)) {
        echo "<script>alert('⚠️ Keranjang belanja Anda masih kosong.'); window.location.href='';</script>";
        exit();
    }

    // Hitung total
    $total_harga = 0;
    $array_nama_produk = [];
    $total_qty = 0;
    foreach ($list_items as $item) {
        $total_harga += (int)$item['subtotal'];
        $array_nama_produk[] = $item['produk'] . " (" . $item['qty'] . " Dus)";
        $total_qty += (int)$item['qty'];
    }

    // Simpan data sementara ke Session agar tidak hilang saat disubmit ulang nanti
    $_SESSION['temp_order'] = [
        'nama_pelanggan' => mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']),
        'no_telp'        => mysqli_real_escape_string($koneksi, $_POST['no_telp']),
        'alamat'         => mysqli_real_escape_string($koneksi, $_POST['alamat']),
        'metode_bayar'   => mysqli_real_escape_string($koneksi, $_POST['metode_pembayaran']),
        'string_produk'  => implode(", ", $array_nama_produk),
        'total_qty'      => $total_qty,
        'total_harga'    => $total_harga,
        'list_items'     => $list_items // disimpan untuk potong stok nanti
    ];

    $order_review = $_SESSION['temp_order'];
    $tampilan = "instruksi_bayar"; // Alihkan tampilan ke halaman bayar dulu
}

// =========================================================================
// SAKLAR 2: KONFIRMASI FINAL (BARU MASUK DATABASE & POTONG STOK)
// =========================================================================
if (isset($_POST['konfirmasi_final'])) {
    if (!isset($_SESSION['temp_order'])) {
        echo "<script>alert('Session kedaluwarsa, silakan ulangi.'); window.location.href='';</script>";
        exit();
    }

    $data = $_SESSION['temp_order'];
    $tgl_order = date('Y-m-d H:i:s');

    // 1. Eksekusi Potong Stok Produk di Gudang
    foreach ($data['list_items'] as $item) {
        $prod_name = mysqli_real_escape_string($koneksi, $item['produk']);
        $prod_qty  = (int)$item['qty'];
        mysqli_query($koneksi, "UPDATE stok SET jumlah_stok = jumlah_stok - $prod_qty, tgl_update = NOW() WHERE nama_produk = '$prod_name'");
    }

    // 2. Insert ke Tabel Penjualan
    $nama_p  = $data['nama_pelanggan'];
    $qty_p   = $data['total_qty'];
    $total_p = $data['total_harga'];

    $sql_insert = "INSERT INTO penjualan (tgl_penjualan, nama_pelanggan, jumlah_produk, total_harga) 
                   VALUES ('$tgl_order', '$nama_p', '$qty_p', '$total_p')";

    if (mysqli_query($koneksi, $sql_insert)) {
        $id_baru = mysqli_insert_id($koneksi);

        $order_final = [
            'id' => $id_baru,
            'nama' => $nama_p,
            'produk' => $data['string_produk'],
            'metode' => $data['metode_bayar'],
            'total' => $total_p,
            'tanggal' => $tgl_order
        ];

        // Bersihkan session data sementara
        unset($_SESSION['temp_order']);
        $tampilan = "nota_sukses"; // Alihkan ke tampilan sukses
    } else {
        $pesan_gagal = "Gagal memproses pesanan: " . mysqli_error($koneksi);
        $tampilan = "form_input";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxywater - Sistem Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', sans-serif;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .brand-header {
            background: linear-gradient(135deg, #0d6efd, #00d2ff);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 25px;
        }

        .struk-box {
            font-family: 'Courier New', Courier, monospace;
            background: #fffdf6;
            border: 1px dashed #ddd;
            border-radius: 8px;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row justify-content-center g-4">

            <?php if ($tampilan == "form_input"): ?>
                <div class="col-12 col-lg-7">
                    <?php if (!empty($pesan_gagal)): ?>
                        <div class="alert alert-danger mb-4"><?= $pesan_gagal ?></div>
                    <?php endif; ?>

                    <div class="card card-custom mb-4 bg-white">
                        <div class="brand-header text-center">
                            <h3 class="fw-bold mb-1"><i class="fa-solid fa-droplet me-2"></i>Oxywater Multi-Order</h3>
                            <p class="mb-0 small opacity-75">Silakan pilih beberapa varian sekaligus ke dalam daftar</p>
                        </div>
                        <div class="card-body p-4 text-dark">
                            <form action="" method="POST" id="formOrderCustomer">

                                <h5 class="fw-bold mb-3 text-primary" style="font-size: 15px;"><i class="fa-solid fa-user me-2"></i>Informasi Pelanggan</h5>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Nama Lengkap / Nama Toko</label>
                                    <input type="text" name="nama_pelanggan" class="form-control" placeholder="Masukkan nama toko/pemesan" required>
                                </div>
                                <div class="row g-2 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Nomor WhatsApp</label>
                                        <input type="tel" name="no_telp" class="form-control" placeholder="08123xxx" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Alamat Pengiriman</label>
                                        <input type="text" name="alamat" class="form-control" placeholder="Alamat lengkap tujuan" required>
                                    </div>
                                </div>

                                <hr class="opacity-25 my-4">

                                <h5 class="fw-bold mb-3 text-primary" style="font-size: 15px;"><i class="fa-solid fa-cart-plus me-2"></i>Pilih Produk & Tambah</h5>
                                <div class="row g-2 align-items-end mb-3">
                                    <div class="col-md-6">
                                        <select id="pilih_produk" class="form-select">
                                            <option value="">-- Pilih Varian --</option>
                                            <?php
                                            $get_produk = mysqli_query($koneksi, "SELECT nama_produk, harga_per_dus, jumlah_stok FROM stok WHERE jumlah_stok > 0");
                                            while ($p = mysqli_fetch_assoc($get_produk)) {
                                                echo "<option value='" . htmlspecialchars($p['nama_produk']) . "' data-harga='" . $p['harga_per_dus'] . "'>" . htmlspecialchars($p['nama_produk']) . " (Rp " . number_format($p['harga_per_dus'], 0, ',', '.') . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="number" id="qty_produk" class="form-control" value="1" min="1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <button type="button" id="btn_tambah_keranjang" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-plus me-1"></i> Tambah</button>
                                    </div>
                                </div>

                                <div class="table-responsive bg-light p-2 rounded border mb-4">
                                    <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 14px;">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Varian Produk</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="isi_keranjang">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Belum ada item di daftar.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <input type="hidden" name="items_keranjang" id="items_keranjang" value="[]">

                                <div class="d-flex justify-content-between align-items-center bg-primary bg-opacity-10 p-3 rounded mb-4">
                                    <span class="fw-bold text-dark">TOTAL ESTIMASI PESANAN:</span>
                                    <h4 class="fw-bold text-primary mb-0" id="text_total_akhir">Rp 0</h4>
                                </div>

                                <h5 class="fw-bold mb-3 text-primary" style="font-size: 15px;"><i class="fa-solid fa-credit-card me-2"></i>Metode Pembayaran</h5>
                                <div class="mb-4 text-center">
                                    <div class="form-check form-check-inline me-4">
                                        <input class="form-check-input" type="radio" name="metode_pembayaran" id="bayar_transfer" value="transfer" checked>
                                        <label class="form-check-label fw-bold text-dark" for="bayar_transfer"><i class="fa-solid fa-building-columns text-muted me-1"></i> Transfer Bank</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="metode_pembayaran" id="bayar_qris" value="qris">
                                        <label class="form-check-label fw-bold text-dark" for="bayar_qris"><i class="fa-solid fa-qrcode text-muted me-1"></i> QRIS</label>
                                    </div>
                                </div>

                                <button type="submit" name="proses_pembayaran" id="tombolKirimOrder" class="btn btn-secondary w-100 fw-bold py-3 shadow-sm" disabled>
                                    <i class="fa-solid fa-arrow-right me-2"></i>Lanjut ke Pembayaran
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($tampilan == "instruksi_bayar" && $order_review): ?>
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card card-custom bg-white shadow-lg">
                        <div class="card-header bg-warning text-dark text-center py-3 fw-bold fs-5" style="border-radius: 15px 15px 0 0;">
                            <i class="fa-solid fa-hourglass-half me-2 animate-spin"></i>Menunggu Pembayaran Anda
                        </div>
                        <div class="card-body p-4 text-dark text-center">
                            <p class="text-muted small mb-1">Silakan transfer nominal berikut terlebih dahulu:</p>
                            <h2 class="fw-extrabold text-danger mb-4">Rp <?= number_format($order_review['total_harga'], 0, ',', '.') ?></h2>

                            <div class="alert alert-light text-start p-3 border mb-4" style="background: #fafafa;">
                                <?php if ($order_review['metode_bayar'] == 'qris'): ?>
                                    <h6 class="fw-bold text-center mb-2"><i class="fa-solid fa-qrcode me-2"></i>SCAN QRIS RESMI</h6>
                                    <div class="text-center">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=OxywaterCompanyQRIS" alt="QRIS" class="img-thumbnail" style="width:160px;">
                                    </div>
                                <?php else: ?>
                                    <h6 class="fw-bold mb-3 text-center border-bottom pb-2 text-primary"><i class="fa-solid fa-building-columns me-2"></i>REKENING TRANSFER TUJUAN</h6>
                                    <?php
                                    $query_bank = mysqli_query($koneksi, "SELECT * FROM rekening ORDER BY id ASC");
                                    if (mysqli_num_rows($query_bank) > 0) {
                                        while ($rek = mysqli_fetch_assoc($query_bank)) {
                                            echo "<div class='mb-2 border-bottom pb-2'>";
                                            echo "<small class='text-muted'>Bank " . htmlspecialchars($rek['nama_bank']) . "</small><br>";
                                            echo "<b class='fs-5 text-dark font-monospace'>" . htmlspecialchars($rek['no_rekening']) . "</b><br>";
                                            echo "<small>A/N: " . htmlspecialchars($rek['atas_nama']) . "</small>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<div class='text-center font-monospace'>Bank BCA : 123-4567-890<br>A/N PT. OXYWATER INDONESIA MAJU</div>";
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>

                            <form action="" method="POST">
                                <button type="submit" name="konfirmasi_final" class="btn btn-success w-100 fw-bold py-3 mb-2 shadow">
                                    <i class="fa-solid fa-circle-check me-2"></i>Saya Sudah Bayar / Transfer
                                </button>
                                <a href="" class="btn btn-link text-muted btn-sm">Batal & Ubah Pesanan</a>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($tampilan == "nota_sukses" && $order_final): ?>
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card card-custom bg-white">
                        <div class="card-body p-4 text-center text-dark">
                            <div class="text-success display-4 mb-2"><i class="fa-solid fa-circle-check"></i></div>
                            <h4 class="fw-bold text-success mb-1">Pembayaran Diterima!</h4>
                            <p class="text-muted small">ID Transaksi Berhasil: <b>#<?= $order_final['id'] ?></b></p>

                            <div class="struk-box text-start p-3 my-3 text-dark">
                                <div class="text-center fw-bold">=== OXYWATER RECEIPT ===</div>
                                <div class="small text-muted text-center"><?= date('d/m/Y H:i', strtotime($order_final['tanggal'])) ?></div>
                                <hr style="border-top: 1px dashed #000;">
                                <div class="small"><b>Pelanggan:</b> <?= htmlspecialchars($order_final['nama']) ?></div>
                                <div class="small"><b>Item:</b> <?= htmlspecialchars($order_final['produk']) ?></div>
                                <div class="small"><b>Metode:</b> <?= strtoupper($order_final['metode']) ?></div>
                                <hr style="border-top: 1px dashed #000;">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>TOTAL LUNAS:</span>
                                    <span>Rp <?= number_format($order_final['total'], 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button onclick="window.print()" class="btn btn-dark fw-semibold"><i class="fa-solid fa-print me-2"></i>Cetak Struk Transaksi</button>
                                <a href="" class="btn btn-primary">Kembali Ke Menu Utama</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        let keranjang = [];
        const selectProduk = document.getElementById('pilih_produk');
        const inputQty = document.getElementById('qty_produk');
        const btnTambah = document.getElementById('btn_tambah_keranjang');
        const isiKeranjang = document.getElementById('isi_keranjang');
        const textTotalAkhir = document.getElementById('text_total_akhir');
        const inputHiddenKeranjang = document.getElementById('items_keranjang');
        const btnKirimOrder = document.getElementById('tombolKirimOrder');

        if (btnTambah) {
            btnTambah.addEventListener('click', function() {
                if (selectProduk.value === "") {
                    alert("Silakan pilih varian produk!");
                    return;
                }
                const namaProduk = selectProduk.value;
                const pilihanOption = selectProduk.options[selectProduk.selectedIndex];
                const harga = parseInt(pilihanOption.getAttribute('data-harga'));
                const qty = parseInt(inputQty.value);

                if (qty < 1 || isNaN(qty)) {
                    alert("Minimal 1 Dus.");
                    return;
                }

                const indexExist = keranjang.findIndex(item => item.produk === namaProduk);
                if (indexExist > -1) {
                    keranjang[indexExist].qty += qty;
                    keranjang[indexExist].subtotal = keranjang[indexExist].qty * keranjang[indexExist].harga;
                } else {
                    keranjang.push({
                        produk: namaProduk,
                        harga: harga,
                        qty: qty,
                        subtotal: harga * qty
                    });
                }
                selectProduk.value = "";
                inputQty.value = 1;
                perbaruiTampilanKeranjang();
            });
        }

        function hapusItem(index) {
            keranjang.splice(index, 1);
            perbaruiTampilanKeranjang();
        }

        function perbaruiTampilanKeranjang() {
            if (!isiKeranjang) return;
            if (keranjang.length === 0) {
                isiKeranjang.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Belum ada item di daftar.</td></tr>`;
                textTotalAkhir.innerText = "Rp 0";
                inputHiddenKeranjang.value = "[]";
                btnKirimOrder.setAttribute('disabled', 'disabled');
                btnKirimOrder.className = "btn btn-secondary w-100 fw-bold py-3 shadow-sm";
                return;
            }

            let html = "";
            let totalBelanja = 0;
            keranjang.forEach((item, index) => {
                totalBelanja += item.subtotal;
                html += `<tr>
                <td class="fw-bold text-dark">${item.produk}</td>
                <td class="text-center">${item.qty} Dus</td>
                <td class="text-end fw-semibold">Rp ${item.subtotal.toLocaleString('id-ID')}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="hapusItem(${index})"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>`;
            });

            isiKeranjang.innerHTML = html;
            textTotalAkhir.innerText = "Rp " + totalBelanja.toLocaleString('id-ID');
            inputHiddenKeranjang.value = JSON.stringify(keranjang);
            btnKirimOrder.removeAttribute('disabled');
            btnKirimOrder.className = "btn btn-primary w-100 fw-bold py-3 shadow-sm";
        }
    </script>
</body>

</html>