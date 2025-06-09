<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    echo "<script>
        alert('Yorum ve puan eklemek için giriş yapmalısınız!');
        window.location.href = 'giris.php';
    </script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kitap_id = intval($_POST['kitap_id']);
    $yorum = trim($_POST['yorum']);
    $puan = intval($_POST['puan']);
    $kullanici_id = $_SESSION['kullanici_id'];
    $kullanici_adi = $_SESSION['kullanici_adi'];

    if (empty($yorum) || $puan < 1 || $puan > 5) {
        echo "<script>
            alert('Lütfen geçerli bir yorum ve puan giriniz.');
            window.history.back();
        </script>";
        exit;
    }

    $stmt = $baglanti->prepare("CALL yorum_ekle(?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $kitap_id, $kullanici_id, $yorum, $puan, $kullanici_adi);

    if ($stmt->execute()) {
        echo "<script>
            alert('Yorumunuz ve puanınız başarıyla eklendi.');
            window.location.href = 'anasayfa.php';
        </script>";
    } else {
        echo "<script>
            alert('Bir hata oluştu, lütfen tekrar deneyiniz.');
            window.history.back();
        </script>";
    }

    $stmt->close();
} else {
    header("Location: anasayfa.php");
}
?>
