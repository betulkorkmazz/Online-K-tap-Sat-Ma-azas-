<?php
$host = "localhost";
$veritabani_adi = "kitap_satis";
$kullanici_adi = "root";
$sifre = "root"; // Eğer bir şifre belirlemediyseniz boş bırakın

try {
    $db = new PDO("mysql:host=$host;dbname=$veritabani_adi;charset=utf8", $kullanici_adi, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
}

// Kitabı sepete ekleme fonksiyonu
function sepeteEkle($kullanici_id, $kitap_id, $adet = 1) {
    global $db;
    $stmt = $db->prepare("INSERT INTO Sepet (kullanici_id, kitap_id, adet) VALUES (?, ?, ?)");
    $stmt->execute([$kullanici_id, $kitap_id, $adet]);
    return $stmt->rowCount();
}

// Kullanıcının sepetini getirme fonksiyonu
function sepetiGetir($kullanici_id) {
    global $db;
    $stmt = $db->prepare("SELECT Sepet.adet, Kitaplar.* FROM Sepet INNER JOIN Kitaplar ON Sepet.kitap_id = Kitaplar.id WHERE Sepet.kullanici_id = ?");
    $stmt->execute([$kullanici_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sepetten kitap çıkarma fonksiyonu (gerekirse)
function sepettenCikar($kullanici_id, $kitap_id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM Sepet WHERE kullanici_id = ? AND kitap_id = ?");
    $stmt->execute([$kullanici_id, $kitap_id]);
    return $stmt->rowCount();
}

// Sepetteki kitap adedini güncelleme fonksiyonu (gerekirse)
function sepetAdetGuncelle($kullanici_id, $kitap_id, $adet) {
    global $db;
    $stmt = $db->prepare("UPDATE Sepet SET adet = ? WHERE kullanici_id = ? AND kitap_id = ?");
    $stmt->execute([$adet, $kullanici_id, $kitap_id]);
    return $stmt->rowCount();
}
?>