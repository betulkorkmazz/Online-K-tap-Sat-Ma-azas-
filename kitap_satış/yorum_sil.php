<?php
// yorum_sil.php
session_start();
include("baglan.php");

if (!isset($_SESSION['kullanici_id'])) {
    echo "<script>alert('Bu işlemi yapmak için giriş yapmalısınız.'); window.location.href='giris.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['yorum_id'])) {
    $yorum_id = intval($_POST['yorum_id']);
    $kullanici_id_session = $_SESSION['kullanici_id'];
    
    // Yönlendirme için kitap ID'sini formdan veya yorumun kendisinden al
    $kitap_id_yonlendirme = isset($_POST['kitap_id_yonlendirme']) ? intval($_POST['kitap_id_yonlendirme']) : null;

    // Yorumun gerçekten bu kullanıcıya ait olup olmadığını ve kitap_id'sini PHP tarafında kontrol edelim.
    $check_stmt = $baglanti->prepare("SELECT kullanici_id, kitap_id FROM yorumlar WHERE id = ?");
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
        echo "<script>alert('Bu yorumu silme yetkiniz yok.'); window.location.href='anasayfa.php';</script>";
        $check_stmt->close();
        exit();
    }
    
    if ($kitap_id_yonlendirme === null) { // Eğer formdan gelmediyse, DB'den aldığımızı kullan
        $kitap_id_yonlendirme = $check_row['kitap_id'];
    }
    $check_stmt->close();

    parametreli yordamı çağırıyoruz.
    $stmt = $baglanti->prepare("CALL yorum_sil(?)");
    if (!$stmt) {
        echo "<script>alert('Saklı yordam hazırlama hatası: " . $baglanti->error . "'); window.history.back();</script>";
        exit();
    }
    $stmt->bind_param("i", $yorum_id);

    if ($stmt->execute()) {
        $redirect_url = 'anasayfa.php'; 
        if ($kitap_id_yonlendirme !== null && $kitap_id_yonlendirme > 0) {
           
            $referer = $_SESSION['yorum_sil_referer'] ?? 'anasayfa.php?kitap_id=' . $kitap_id_yonlendirme;
            if (strpos($referer, 'kategori.php') !== false) {
                 $redirect_url = $referer . '#yorumAccordion' . $kitap_id_yonlendirme;
             } else if (strpos($referer, 'anasayfa.php') !== false) {
                 $query_params = [];
                 if (isset($_SESSION['current_page_anasayfa'])) { // anasayfa.php'den sayfa bilgisi session'a atılabilir.
                     $query_params['sayfa'] = $_SESSION['current_page_anasayfa'];
                 }
                 $redirect_url = 'anasayfa.php?' . http_build_query($query_params) . '#yorumAccordion' . $kitap_id_yonlendirme;
             } else { 
                $redirect_url = 'anasayfa.php#yorumAccordion' . $kitap_id_yonlendirme;
             }
        }
        unset($_SESSION['yorum_sil_referer']);
        echo "<script>alert('Yorum başarıyla silindi.'); window.location.href='" . $redirect_url . "';</script>";
        exit();
    } else {
        echo "<script>alert('Yorum silinirken bir veritabanı hatası oluştu: " . $baglanti->error . "'); window.history.back();</script>";
    }
    $stmt->close();
} else {
 
    header("Location: anasayfa.php");
    exit();
}
?>
