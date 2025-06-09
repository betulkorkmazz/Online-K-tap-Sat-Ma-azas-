<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

$kullanici_id = $_SESSION['kullanici_id'];

// Sepetten silme işlemi:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_sepet_id'])) {
    $sil_sepet_id = intval($_POST['sil_sepet_id']);

    // Kullanıcının sepetine ait bu id olduğundan emin ol
    $kontrol = $baglanti->prepare("SELECT * FROM sepet WHERE id = ? AND kullanici_id = ?");
    $kontrol->bind_param("ii", $sil_sepet_id, $kullanici_id);
    $kontrol->execute();
    $sonuc_kontrol = $kontrol->get_result();

    if ($sonuc_kontrol->num_rows > 0) {
        $sil = $baglanti->prepare("DELETE FROM sepet WHERE id = ?");
        $sil->bind_param("i", $sil_sepet_id);
        $sil->execute();
    }
    header("Location: sepet.php"); // Sayfayı yenile
    exit();
}

// Sepeti çek:
$sepet = $baglanti->prepare("SELECT s.*, k.baslik, k.resim_url, k.fiyat FROM sepet s JOIN kitaplar k ON s.kitap_id = k.id WHERE s.kullanici_id = ?");
$sepet->bind_param("i", $kullanici_id);
$sepet->execute();
$sonuc = $sepet->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Sepetim - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #fff8dc 25%, #f5deb3 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #3b2f0b;
            min-height: 100vh;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="60" height="52" viewBox="0 0 60 52"><path fill="%23d4af37" fill-opacity="0.1" d="M30 0l15 26-15 26-15-26z"/></svg>');
            background-repeat: repeat;
            opacity: 0.15;
            z-index: -1;
        }
        h1 {
            color: #d4af37;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            text-shadow: 1px 1px 2px #b8860b;
        }
        .btn-warning {
            background-color: #d4af37;
            border: none;
            font-weight: 600;
            color: #1a1a1a;
            box-shadow: 0 2px 5px rgba(212,175,55,0.5);
            transition: background-color 0.3s ease;
        }
        .btn-warning:hover {
            background-color: #b8860b;
            color: #fff;
        }
        .btn-danger {
            background-color: #d9534f;
            border: none;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 2px 5px rgba(217,83,79,0.6);
            transition: background-color 0.3s ease;
            width: 100%;
        }
        .btn-danger:hover {
            background-color: #c9302c;
        }
        .card {
            border: 2px solid #d4af37;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(212,175,55,0.4);
            background-color: #fffbea;
            padding: 10px;
            height: 470px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(212,175,55,0.6);
        }
        .card img {
            border-radius: 10px;
            box-shadow: 0 0 10px #d4af37;
            max-height: 240px;
            width: 100%;
            object-fit: contain;
            margin-bottom: 15px;
            background-color: #fff;
        }
        .kitap-baslik {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #3b2f0b;
            flex-grow: 1;
        }
        .kitap-adet, .kitap-fiyat {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #6b4f01;
        }
        .empty-alert {
            background-color: #fff3cd;
            color: #856404;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 8px rgba(212,175,55,0.3);
            font-size: 1.2rem;
            margin-top: 40px;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>🐝 Sepetiniz</h1>
    <?php if ($sonuc->num_rows > 0): ?>
        <div class="row">
            <?php while ($satir = $sonuc->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <?php if ($satir['resim_url']): ?>
                            <img src="<?= htmlspecialchars($satir['resim_url']) ?>" alt="Kitap Resmi" />
                        <?php else: ?>
                            <img src="default-book.png" alt="Kitap Resmi" />
                        <?php endif; ?>
                        <div class="kitap-baslik"><?= htmlspecialchars($satir['baslik']) ?></div>
                        <div class="kitap-adet">Adet: <?= intval($satir['adet']) ?></div>
                        <div class="kitap-fiyat">Fiyat: <?= number_format($satir['fiyat'], 2, ',', '.') ?> ₺</div>
                        <form method="POST" onsubmit="return confirm('Bu kitabı sepetten silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="sil_sepet_id" value="<?= $satir['id'] ?>" />
                            <button type="submit" class="btn btn-danger mt-3">Sepetten Sil</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <form action="odeme.php" method="post" class="mt-3">
                <button type="submit" class="btn btn-satin-al btn btn-success">💳 Siparişi Tamamla</button>
            </form>
    <?php else: ?>
        <div class="empty-alert">
            Sepetiniz şu an boş. 🐝 Hadi birkaç kitap ekleyelim!
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="anasayfa.php" class="btn btn-warning">⬅ Ana Sayfaya Dön</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
