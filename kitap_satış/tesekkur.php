<?php
// tesekkur.php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

$kullanici_id = $_SESSION['kullanici_id'];

$adres = $_POST['adres'] ?? ''; 
// Kart bilgileri normalde veritabanında saklanmaz, ödeme ağ geçidine gönderilir.
// $kart_numarasi = $_POST['kart'] ?? ''; 
// $sonkullanma = $_POST['sonkullanma'] ?? '';
// $cvv = $_POST['cvv'] ?? '';


$kitaplar_ids = $_POST['kitaplar'] ?? []; 
$adetler = $_POST['adetler'] ?? [];     
$siparis_genel_toplam_tutar_formdan = isset($_POST['toplam_tutar']) ? floatval($_POST['toplam_tutar']) : 0;


if (empty($kitaplar_ids) || empty($adetler) || count($kitaplar_ids) !== count($adetler)) {
    $_SESSION['odeme_hata'] = "Sepet bilgileri eksik veya tutarsız. Sipariş verilemedi.";
    header("Location: sepet.php"); 
    exit();
}

// Adres boşsa dijital ürünler için opsiyonel olabilir, fiziksel için zorunlu olmalı.
// Bu projede adres zorunlu kabul edilmiş, o yüzden kontrol edelim.
if (empty($adres)) { 
    $_SESSION['odeme_hata'] = "Teslimat adresi boş olamaz.";
    header("Location: odeme.php"); // odeme.php'de bu hata mesajı gösterilmeli.
    exit();
}

// Raporunuzdaki `satin_al` saklı yordamı:
// (IN kullanici INT, IN kitap INT, IN adet INT, IN adres TEXT, IN toplam DECIMAL(10,2))
// `toplam` parametresi, o satış satırının (kitap*adet) toplamını mı yoksa siparişin genel toplamını mı ifade ediyor?
// `Satislar` tablosundaki `toplam_tutar` sütunu genellikle o satış kaydının kendi tutarını ifade eder.
// Bu durumda, her kitap için (fiyat * adet) hesaplayıp yordama göndermek daha mantıklı.

$baglanti->begin_transaction(); 
$siparis_basarili = true;
$gerceklesen_siparis_toplami = 0; // Gerçekleşen satışların toplamını burada tutalım

for ($i = 0; $i < count($kitaplar_ids); $i++) {
    $kitap_id = intval($kitaplar_ids[$i]);
    $adet = intval($adetler[$i]);

    if ($adet <= 0) continue; 

    // Kitabın fiyatını ve stoğunu çek
    $kitap_bilgi_sorgu = $baglanti->prepare("SELECT fiyat, stok FROM kitaplar WHERE id = ? FOR UPDATE"); // Satır kilitleme
    if (!$kitap_bilgi_sorgu) {
        $_SESSION['odeme_hata'] = "Sorgu hazırlama hatası (kitap bilgisi): " . $baglanti->error;
        $siparis_basarili = false;
        break;
    }
    $kitap_bilgi_sorgu->bind_param("i", $kitap_id);
    $kitap_bilgi_sorgu->execute();
    $kitap_bilgi_sonuc = $kitap_bilgi_sorgu->get_result();
    
    if ($kitap_bilgi_satir = $kitap_bilgi_sonuc->fetch_assoc()) {
        $kitap_fiyati = $kitap_bilgi_satir['fiyat'];
        $mevcut_stok = $kitap_bilgi_satir['stok'];
        
        if ($mevcut_stok < $adet) {
            $_SESSION['odeme_hata'] = "ID: $kitap_id olan '" . ($kitap_bilgi_satir['baslik'] ?? '') . "' için stok yetersiz (İstenen: $adet, Mevcut: $mevcut_stok).";
            $siparis_basarili = false;
            $kitap_bilgi_sorgu->close();
            break;
        }
        
        $satir_toplam_tutar = $kitap_fiyati * $adet;
        $gerceklesen_siparis_toplami += $satir_toplam_tutar;

        // `satin_al` saklı yordamını çağır
        $stmt = $baglanti->prepare("CALL satin_al(?, ?, ?, ?, ?)");
        if (!$stmt) {
            $_SESSION['odeme_hata'] = "Saklı yordam hazırlama hatası (satin_al): " . $baglanti->error;
            $siparis_basarili = false;
            $kitap_bilgi_sorgu->close();
            break;
        }
        $stmt->bind_param("iiisd", $kullanici_id, $kitap_id, $adet, $adres, $satir_toplam_tutar);
        
        if (!$stmt->execute()) {
            // Saklı yordam içindeki SIGNAL ile hata mesajını yakalamak daha zordur.
            // En iyisi, yordamın bir OUT parametresi ile başarı/hata durumunu bildirmesidir.
            // execute() false dönerse, genellikle yordam içinde bir ROLLBACK veya hata oluşmuştur.
            $_SESSION['odeme_hata'] = "Sipariş oluşturulurken hata (Kitap ID: $kitap_id). Stok yetersiz olabilir veya veritabanı sorunu. Detay: " . $baglanti->error;
            // $stmt->error içinde yordamdan dönen SIGNAL mesajı olabilir, ama bu her zaman garantili değil.
            // Örneğin: if (strpos($stmt->error, 'Stok yetersiz') !== false) { ... }
            $siparis_basarili = false;
            $stmt->close();
            $kitap_bilgi_sorgu->close();
            break; 
        }
        $stmt->close();
    } else {
        $_SESSION['odeme_hata'] = "ID: $kitap_id olan kitap bulunamadı.";
        $siparis_basarili = false;
        $kitap_bilgi_sorgu->close();
        break;
    }
    $kitap_bilgi_sorgu->close();
}


if ($siparis_basarili) {
    $temizle_stmt = $baglanti->prepare("DELETE FROM sepet WHERE kullanici_id = ?");
    if (!$temizle_stmt) {
         $baglanti->rollback();
         $_SESSION['odeme_hata'] = "Sipariş başarılı ancak sepet temizlenirken sorgu hatası: " . $baglanti->error;
         header("Location: sepet.php");
         exit();
    }
    $temizle_stmt->bind_param("i", $kullanici_id);
    if ($temizle_stmt->execute()) {
        $baglanti->commit(); 
        $_SESSION['siparis_basarili_mesaj'] = "Siparişiniz başarıyla alındı!";
        $_SESSION['son_siparis_adres'] = $adres; // Teşekkür sayfasında göstermek için
        $_SESSION['son_siparis_tutar'] = $gerceklesen_siparis_toplami; // Gerçekleşen toplamı göster
    } else {
        $baglanti->rollback(); 
        $_SESSION['odeme_hata'] = "Sipariş sonrası sepet temizlenirken bir hata oluştu: " . $temizle_stmt->error;
        header("Location: sepet.php"); 
        exit();
    }
    $temizle_stmt->close();
} else {
    $baglanti->rollback();
    header("Location: odeme.php"); 
    exit();
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teşekkürler - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fffbee; font-family: 'Segoe UI', sans-serif; }
        .tesekkur-container { max-width: 600px; margin: 50px auto; background-color: #fff8d6; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-anasayfa { background-color: #ffd000; color: black; font-weight: 500; margin-top: 20px; }
        .btn-anasayfa:hover { background-color: #e6b800; }
    </style>
</head>
<body>

<div class="container tesekkur-container">
    <h2>🎉 Siparişiniz Alındı!</h2>
    <p>Teşekkür ederiz! 📦 Siparişiniz en kısa sürede işleme alınacaktır.</p>
    <?php if (isset($_SESSION['siparis_basarili_mesaj'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['siparis_basarili_mesaj']) ?></div>
        <p><strong>Teslimat Adresi:</strong> <?= htmlspecialchars($_SESSION['son_siparis_adres'] ?? 'Belirtilmedi') ?></p>
        <p><strong>Sipariş Toplamı:</strong> <?= number_format($_SESSION['son_siparis_tutar'] ?? 0, 2) ?> ₺</p>
        <?php 
            unset($_SESSION['siparis_basarili_mesaj']); 
            unset($_SESSION['son_siparis_adres']);
            unset($_SESSION['son_siparis_tutar']);
        ?>
    <?php elseif(isset($_SESSION['odeme_hata'])): // Bu normalde buraya düşmemeli, odeme.php'ye yönlendirilmeli ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['odeme_hata']) ?></div>
        <?php unset($_SESSION['odeme_hata']); ?>
    <?php endif; ?>
    <a href="anasayfa.php" class="btn btn-anasayfa">🏠 Ana Sayfaya Dön</a>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
