<?php
// yorum_guncelle_islem.php
session_start();
include("baglan.php");

if (!isset($_SESSION['kullanici_id'])) {
    // Kullanıcı giriş yapmamışsa yönlendir
    echo "<script>alert('Bu işlemi yapmak için giriş yapmalısınız.'); window.location.href='giris.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $yorum_id = intval($_POST['yorum_id']);
    $kitap_id = intval($_POST['kitap_id']); // Yönlendirme için
    $yeni_yorum_text = trim($_POST['yorum']);
    $yeni_puan = intval($_POST['puan']);
    $kullanici_id_session = $_SESSION['kullanici_id'];

    if (empty($yeni_yorum_text) || $yeni_puan < 1 || $yeni_puan > 5) {
        echo "<script>alert('Lütfen geçerli bir yorum ve puan giriniz.'); window.history.back();</script>";
        exit;
    }
    
    // Yorumun gerçekten bu kullanıcıya ait olup olmadığını PHP tarafında kontrol edelim.
    $check_stmt = $baglanti->prepare("SELECT kullanici_id FROM yorumlar WHERE id = ?");
    if (!$check_stmt) {
        echo "<script>alert('Sorgu hazırlama hatası: " . $baglanti->error . "'); window.history.back();</script>";
        exit();
    }
    $check_stmt->bind_param("i", $yorum_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo "<script>alert('Yorum bulunamadı.'); window.location.href='anasayfa.php';</script>";
        $check_stmt->close();
        exit();
    }
    
    $check_row = $check_result->fetch_assoc();
    if ($check_row['kullanici_id'] != $kullanici_id_session) {
        echo "<script>alert('Bu yorumu güncelleme yetkiniz yok.'); window.location.href='anasayfa.php';</script>";
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // Raporunuzdaki 3 parametreli yordam tanımına göre çağrı yapılıyor:
    // CALL yorum_guncelle(yorum_id, yeni_yorum, yeni_puan)
    // GÜVENLİK NOTU: İdealde saklı yordam, kullanıcı ID'sini de alıp yetki kontrolünü DB tarafında yapmalıdır.
    // CREATE PROCEDURE yorum_guncelle (IN p_yorum_id INT, IN p_kullanici_id INT, IN p_yeni_yorum TEXT, IN p_yeni_puan INT) ...
    // Şimdilik PHP'de kontrol edildiği için 3 parametreli yordamı çağırıyoruz.
    $stmt = $baglanti->prepare("CALL yorum_guncelle(?, ?, ?)");
    if (!$stmt) {
         echo "<script>alert('Saklı yordam hazırlama hatası: " . $baglanti->error . "'); window.history.back();</script>";
        exit();
    }
    // Parametreler: yorum_id, yeni_yorum, yeni_puan
    $stmt->bind_param("isi", $yorum_id, $yeni_yorum_text, $yeni_puan);


    if ($stmt->execute()) {
        // Başarılı güncelleme sonrası kitabın olduğu sayfadaki yorum bölümüne yönlendir.
        $redirect_url = 'anasayfa.php'; // Varsayılan
        if ($kitap_id > 0) {
             $referer = $_SESSION['yorum_guncelle_referer'] ?? 'anasayfa.php?kitap_id=' . $kitap_id; // Geldiği sayfayı al
             if (strpos($referer, 'kategori.php') !== false) {
                 $redirect_url = $referer . '#yorumAccordion' . $kitap_id;
             } else {
                 $redirect_url = 'anasayfa.php?sayfa=' . ($_GET['sayfa'] ?? 1) . '#yorumAccordion' . $kitap_id;
             }
        }
        unset($_SESSION['yorum_guncelle_referer']);
        echo "<script>alert('Yorum başarıyla güncellendi.'); window.location.href='" . $redirect_url . "';</script>";
        exit();
    } else {
        // Saklı yordam hatası (örn: yordam bulunamadı, parametreler yanlış)
        echo "<script>alert('Yorum güncellenirken bir veritabanı hatası oluştu: " . $baglanti->error . "'); window.history.back();</script>";
    }
    $stmt->close();
} else {
    // POST değilse anasayfaya veya geldiği yere
     $kitap_id_get = isset($_GET['kitap_id']) ? intval($_GET['kitap_id']) : 0;
     if ($kitap_id_get > 0) {
        header("Location: anasayfa.php?kitap_id=$kitap_id_get" . '#yorumAccordion'. $kitap_id_get);
     } else {
        header("Location: anasayfa.php");
     }
    exit();
}
?>
