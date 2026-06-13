if (isset($_POST['konfirmasi_final'])) {
if (!isset($_SESSION['temp_order'])) {
echo "<script>
    alert('Session kedaluwarsa, silakan ulangi.');
    window.location.href = '';
</script>";
exit();
}

$data = $_SESSION['temp_order'];

// --- TAMBAHKAN KODE INI UNTUK DEBUGGING ---
echo "
<pre>";
    print_r($data);
    echo "</pre>";
die(); // Menghentikan program sementara agar kita bisa melihat datanya
// ------------------------------------------

$tgl_order = date('Y-m-d H:i:s');
// ... kode seterusnya ...