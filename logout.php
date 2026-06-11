<?php
session_start();
// Hancurkan semua session login
session_destroy();
echo "<script>alert('Anda telah berhasil keluar dari sistem.'); window.location.href='login.php';</script>";
exit();
