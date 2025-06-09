<?php
$host = "localhost";
$kullanici = "root";
$sifre = "";
$db_adi = "kitap_satis";

$baglanti = new mysqli($host, $kullanici, $sifre, $db_adi);

if ($baglanti->connect_error) {
    die("Bağlantı hatası: " . $baglanti->connect_error);
}

$baglanti->set_charset("utf8");
?>
