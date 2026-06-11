<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_oxy";

$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek jika koneksi gagal
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
