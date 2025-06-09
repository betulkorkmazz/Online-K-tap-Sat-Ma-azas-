<?php
session_start();
include 'baglan.php';

header('Content-Type: application/json');

if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$kitap_id = $_POST['kitap_id'] ?? null;

if (!$kitap_id || !is_numeric($kitap_id)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kitap ID.']);
    exit;
}

// Önce favoride var mı kontrol et
$sorgu = $baglanti->prepare("SELECT id FROM favoriler WHERE kullanici_id = ? AND kitap_id = ?");
$sorgu->bind_param("ii", $kullanici_id, $kitap_id);
$sorgu->execute();
$sorgu->store_result();

if ($sorgu->num_rows > 0) {
    // Var ise favoriden çıkaralım
    $sorgu->close();
    $sil = $baglanti->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND kitap_id = ?");
    $sil->bind_param("ii", $kullanici_id, $kitap_id);
    $sil->execute();
    $sil->close();
    echo json_encode(['success' => true, 'favorited' => false]);
} else {
// Yoksa favorilere ekleyelim
$sorgu->close();
$ekle = $baglanti->prepare("INSERT INTO favoriler (kullanici_id, kitap_id) VALUES (?, ?)");
$ekle->bind_param("ii", $kullanici_id, $kitap_id);
if ($ekle->execute()) {
echo json_encode(['success' => true, 'favorited' => true]);
} else {
echo json_encode(['success' => false, 'message' => 'Favorilere eklenemedi!']);
}
$ekle->close();
}
?>
