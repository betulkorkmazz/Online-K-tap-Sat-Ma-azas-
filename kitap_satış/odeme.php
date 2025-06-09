<?php
session_start();
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}
include 'baglan.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Ekranı - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fffbee; font-family: 'Segoe UI', sans-serif; }
        .odeme-container { max-width: 600px; margin: 40px auto; background-color: #fff8d6; padding: 30px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-geri { background-color: #ffd000; color: black; font-weight: 500; }
        .btn-onayla { background-color: #28a745; color: white; font-weight: 500; }
    </style>
</head>
<body>

<div class="container odeme-container">
    <h3 class="text-center mb-4">💳 Ödeme Bilgileri</h3>

    <form action="tesekkur.php" method="post">
        <div class="mb-3">
            <label for="isim" class="form-label">Ad Soyad</label>
            <input type="text" class="form-control" name="isim" required>
        </div>
        <div class="mb-3">
            <label for="adres" class="form-label">Teslimat Adresi</label>
            <textarea class="form-control" name="adres" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label for="kart" class="form-label">Kart Numarası</label>
            <input type="text" class="form-control" name="kart" maxlength="19" placeholder="1234 5678 9012 3456" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Son Kullanma Tarihi</label>
            <input type="month" class="form-control" name="sonkullanma" required>
        </div>
        <div class="mb-3">
            <label class="form-label">CVV</label>
            <input type="text" class="form-control" name="cvv" maxlength="3" required>
        </div>

        <?php
        // Sepet bilgilerini gizli inputlarla gönderiyoruz
        $kullanici_id = $_SESSION['kullanici_id'];
        $toplam_tutar = 0;

        $sorgu = $baglanti->prepare("SELECT kitap_id, adet, k.fiyat FROM sepet s JOIN kitaplar k ON s.kitap_id = k.id WHERE s.kullanici_id = ?");
        $sorgu->bind_param("i", $kullanici_id);
        $sorgu->execute();
        $sonuc = $sorgu->get_result();

        while ($row = $sonuc->fetch_assoc()) {
            $toplam_tutar += $row['fiyat'] * $row['adet'];
            echo "<input type='hidden' name='kitaplar[]' value='{$row['kitap_id']}'>";
            echo "<input type='hidden' name='adetler[]' value='{$row['adet']}'>";
        }
        echo "<input type='hidden' name='toplam_tutar' value='{$toplam_tutar}'>";
        ?>

        <button type="submit" class="btn btn-onayla w-100">🧾 Siparişi Onayla</button>
    </form>

    <a href="sepet.php" class="btn btn-geri mt-3">⬅ Sepete Geri Dön</a>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
