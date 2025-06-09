<?php
session_start();
include("baglan.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $yorum_id = intval($_POST['yorum_id']);
    $kitap_id = intval($_POST['kitap_id']);
    $yorum = trim($_POST['yorum']);
    $puan = intval($_POST['puan']);

    $stmt = $baglanti->prepare("CALL yorum_guncelle(?, ?, ?, ?)");
    $stmt->bind_param("iisi", $yorum_id, $_SESSION['kullanici_id'], $yorum, $puan);

    if ($stmt->execute()) {
        header("Location: yorumlar.php?kitap_id=$kitap_id");
        exit();
    } else {
        echo "Yorum güncellenirken hata oluştu!";
    }
}
?>
