<?php
// sepete_ekle.php
session_start();
include 'baglan.php';

if (isset($_POST['kitap_id'], $_POST['adet']) && isset($_SESSION['kullanici_id'])) {
    $kitap_id = intval($_POST['kitap_id']);
    $adet = intval($_POST['adet']);
    $kullanici_id = $_SESSION['kullanici_id'];

    if ($adet <= 0) {
        $_SESSION['sepet_mesaj'] = "Adet geçerli bir sayı olmalıdır.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'anasayfa.php')); // Kullanıcıyı geldiği sayfaya yönlendir
        exit;
    }

    // Kitap stok kontrolü (Eğer Kitaplar tablosunda stok sütunu varsa)
    // Bu kontrol için Kitaplar tablosunda 'stok' adında bir sütun olmalıdır.
    $stok_stmt = $baglanti->prepare("SELECT stok FROM kitaplar WHERE id = ?");
    $stok_mevcut = PHP_INT_MAX; // Stok kontrolü yapılamazsa veya sütun yoksa diye varsayılan

    if ($stok_stmt) {
        $stok_stmt->bind_param("i", $kitap_id);
        $stok_stmt->execute();
        $stok_result = $stok_stmt->get_result();
        if ($stok_row = $stok_result->fetch_assoc()) {
            $stok_mevcut = (int)$stok_row['stok'];
            if ($stok_mevcut < $adet) {
                $_SESSION['sepet_mesaj'] = "Yetersiz stok! En fazla " . $stok_mevcut . " adet ekleyebilirsiniz.";
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'anasayfa.php'));
                exit;
            }
        } else {
            $_SESSION['sepet_mesaj'] = "Kitap bulunamadı.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'anasayfa.php'));
            exit;
        }
        $stok_stmt->close();
    } else {
         // Stok sütunu yoksa veya sorgu başarısızsa, bu durumu loglayabilirsiniz.
         // Şimdilik kritik bir hata olarak kabul etmiyoruz, ancak idealde bu durum handle edilmeli.
         error_log("Stok sorgusu hazırlanamadı: " . $baglanti->error);
    }


    // Sepette bu kitap zaten var mı kontrol et
    $stmt_check = $baglanti->prepare("SELECT adet FROM sepet WHERE kullanici_id = ? AND kitap_id = ?");
    $stmt_check->bind_param("ii", $kullanici_id, $kitap_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Kitap sepette var, adedini güncelle
        $current_item = $result_check->fetch_assoc();
        $yeni_adet_sepette = $current_item['adet'] + $adet;

        // Yeni adet stoku aşıyor mu kontrolü (tekrar)
        if ($stok_mevcut < $yeni_adet_sepette) { // $stok_mevcut daha önce sorgulandı
             $_SESSION['sepet_mesaj'] = "Yetersiz stok! Sepetinizdeki mevcut ürünle birlikte en fazla " . $stok_mevcut . " adet olabilir.";
             header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'anasayfa.php'));
             exit;
        }

        $stmt_update = $baglanti->prepare("UPDATE sepet SET adet = ? WHERE kullanici_id = ? AND kitap_id = ?");
        $stmt_update->bind_param("iii", $yeni_adet_sepette, $kullanici_id, $kitap_id);
        if ($stmt_update->execute()) {
            $_SESSION['sepet_mesaj'] = "Kitap adedi güncellendi.";
        } else {
            $_SESSION['sepet_mesaj'] = "Sepet güncellenirken bir hata oluştu: " . $baglanti->error;
        }
        $stmt_update->close();
    } else {
        // Kitap sepette yok, yeni kayıt ekle (stok kontrolü zaten yukarıda yapıldı)
        $stmt_insert = $baglanti->prepare("INSERT INTO sepet (kullanici_id, kitap_id, adet) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iii", $kullanici_id, $kitap_id, $adet);
        if ($stmt_insert->execute()) {
            $_SESSION['sepet_mesaj'] = "Kitap sepete eklendi.";
        } else {
            $_SESSION['sepet_mesaj'] = "Sepete eklenirken bir hata oluştu: " . $baglanti->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();

} else {
    $_SESSION['sepet_mesaj'] = "Eksik bilgi gönderildi veya giriş yapılmamış!";
}

// Kullanıcıyı geldiği sayfaya veya anasayfaya yönlendir
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'anasayfa.php'));
exit;
?>
