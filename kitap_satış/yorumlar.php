<?php
session_start();
include("baglan.php");

$kitap_id = isset($_GET['kitap_id']) ? intval($_GET['kitap_id']) : 0;
$kullanici_id = $_SESSION['kullanici_id'] ?? null;

if (!$kullanici_id) {
    header("Location: giris.php");
    exit();
}

// Yorumları prosedürle çek
$stmt = $baglanti->prepare("CALL kitap_yorumlari(?)");
$stmt->bind_param("i", $kitap_id);
$stmt->execute();
$result = $stmt->get_result();

// Kitap bilgisi
$kitap_stmt = $baglanti->prepare("SELECT * FROM kitaplar WHERE id = ?");
$kitap_stmt->bind_param("i", $kitap_id);
$kitap_stmt->execute();
$kitap_result = $kitap_stmt->get_result();
$kitap = $kitap_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($kitap['baslik']); ?> - Yorumlar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2><?php echo htmlspecialchars($kitap['baslik']); ?> - Yorumlar</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($yorum = $result->fetch_assoc()): ?>
            <div class="yorum">
                <strong><?php echo htmlspecialchars($yorum['kullanici_adi']); ?>:</strong>
                <p><?php echo htmlspecialchars($yorum['yorum']); ?></p>
                <p>Puan: <?php echo intval($yorum['puan']); ?></p>

                <!-- Eğer bu yorumu giriş yapan kişi yazmışsa düzenle/sil -->
                <?php if ($yorum['kullanici_adi'] == $_SESSION['kullanici_adi']): ?>
                    <form method="POST" action="yorum_guncelle.php" style="display:inline;">
                        <input type="hidden" name="kitap_id" value="<?php echo $kitap_id; ?>">
                        <input type="hidden" name="yorum_id" value="<?php echo $yorum['id']; ?>">
                        <input type="submit" value="📝 Düzenle">
                    </form>
                    <form method="POST" action="yorum_sil.php" style="display:inline;" onsubmit="return confirm('Yorumu silmek istediğinize emin misiniz?');">
                        <input type="hidden" name="yorum_id" value="<?php echo $yorum['id']; ?>">
                        <input type="submit" value="🗑️ Sil">
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Henüz yorum yapılmamış.</p>
    <?php endif; ?>

    <h3>💬 Yorum Yap</h3>
    <form method="POST" action="yorum_ve_puan_ekle.php">
        <textarea name="yorum" placeholder="Yorumunuzu yazın..." required></textarea><br><br>
        <input type="hidden" name="kitap_id" value="<?php echo $kitap_id; ?>">
        <label for="puan">Puan:</label>
        <select name="puan" required>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select><br><br>
        <button type="submit">Yorumu Gönder</button>
    </form>
</div>
</body>
</html>
