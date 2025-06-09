<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];

// Sepete ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sepete_ekle_kitap_id'])) {
    $sepete_ekle_kitap_id = intval($_POST['sepete_ekle_kitap_id']);
    $adet = 1; // varsayılan adet

    // Sepette var mı kontrol
    $kontrolSepet = $baglanti->prepare("SELECT * FROM sepet WHERE kullanici_id = ? AND kitap_id = ?");
    $kontrolSepet->bind_param("ii", $kullanici_id, $sepete_ekle_kitap_id);
    $kontrolSepet->execute();
    $sonucSepet = $kontrolSepet->get_result();

    if ($sonucSepet->num_rows > 0) {
        // Varsa adet artır
        $guncelleSepet = $baglanti->prepare("UPDATE sepet SET adet = adet + ? WHERE kullanici_id = ? AND kitap_id = ?");
        $guncelleSepet->bind_param("iii", $adet, $kullanici_id, $sepete_ekle_kitap_id);
        $guncelleSepet->execute();
        $guncelleSepet->close();
    } else {
        // Yoksa yeni kayıt ekle
        $ekleSepet = $baglanti->prepare("INSERT INTO sepet (kullanici_id, kitap_id, adet) VALUES (?, ?, ?)");
        $ekleSepet->bind_param("iii", $kullanici_id, $sepete_ekle_kitap_id, $adet);
        $ekleSepet->execute();
        $ekleSepet->close();
    }
    $kontrolSepet->close();

    header("Location: favoriler.php?sepete_eklendi=1");
    exit();
}

// Favorilerden kaldırma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_favori_id'])) {
    $sil_favori_id = intval($_POST['sil_favori_id']);

    $kontrol = $baglanti->prepare("SELECT * FROM favoriler WHERE kullanici_id = ? AND kitap_id = ?");
    $kontrol->bind_param("ii", $kullanici_id, $sil_favori_id);
    $kontrol->execute();
    $sonuc_kontrol = $kontrol->get_result();

    if ($sonuc_kontrol->num_rows > 0) {
        $sil = $baglanti->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND kitap_id = ?");
        $sil->bind_param("ii", $kullanici_id, $sil_favori_id);
        $sil->execute();
    }
    header("Location: favoriler.php");
    exit();
}

// Favorideki kitaplar sorgusu
$favoriler = $baglanti->prepare("SELECT kitaplar.id, kitaplar.baslik, kitaplar.resim_url, kitaplar.fiyat, kitaplar.ozet, kategoriler.ad AS kategori_adi
                                 FROM favoriler 
                                 INNER JOIN kitaplar ON favoriler.kitap_id = kitaplar.id
                                 INNER JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id
                                 WHERE favoriler.kullanici_id = ?
                                 ORDER BY kitaplar.id DESC");
$favoriler->bind_param("i", $kullanici_id);
$favoriler->execute();
$result = $favoriler->get_result();

$sepete_eklendi_mesaji = isset($_GET['sepete_eklendi']) && $_GET['sepete_eklendi'] == 1;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Favori Kitaplar - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Arı teması renkleri */
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

        .kategori-badge {
            background-color: #b8860b;
            color: #fff;
            font-weight: 600;
            border-radius: 12px;
            padding: 4px 10px;
            font-size: 0.85rem;
            user-select: none;
            display: inline-block;
            margin-bottom: 8px;
        }

        p {
            font-size: 1rem;
            line-height: 1.4;
        }

        .alert-success {
            max-width: 600px;
            margin: 10px auto 25px auto;
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(212,175,55,0.4);
            font-weight: 600;
        }

        /* Responsive grid spacing */
        @media (max-width: 576px) {
            .card {
                margin-bottom: 25px;
            }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h1>🐝 Favori Kitaplarınız</h1>

    <?php if ($sepete_eklendi_mesaji): ?>
        <div class="alert alert-success text-center" role="alert">
            🛒 Kitap sepete eklendi!
        </div>
    <?php endif; ?>

    <div class="text-center mb-4">
        <a href="anasayfa.php" class="btn btn-warning">Anasayfaya Dön</a>
    </div>

    <?php if ($result->num_rows == 0): ?>
        <p class="text-center fs-5">Henüz favori kitabınız yok.</p>
    <?php else: ?>
        <div class="row">
            <?php while ($kitap = $result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card p-3">
                        <?php if ($kitap['resim_url']): ?>
                            <img src="<?= htmlspecialchars($kitap['resim_url']) ?>" alt="Kitap Resmi" class="img-fluid mb-3">
                        <?php endif; ?>
                        <h5><?= htmlspecialchars($kitap['baslik']) ?></h5>
                        <div class="kategori-badge"><?= htmlspecialchars($kitap['kategori_adi']) ?></div>
                        <p><strong>Fiyat:</strong> <?= htmlspecialchars($kitap['fiyat']) ?> ₺</p>

                        <form method="POST" class="mb-2">
                            <input type="hidden" name="sepete_ekle_kitap_id" value="<?= $kitap['id'] ?>" />
                            <button type="submit" class="btn btn-warning w-100">Sepete Ekle</button>
                        </form>

                        <form method="POST" onsubmit="return confirm('Bu kitabı favorilerden kaldırmak istediğinize emin misiniz?');">
                            <input type="hidden" name="sil_favori_id" value="<?= $kitap['id'] ?>" />
                            <button type="submit" class="btn btn-danger w-100">Favorilerden Kaldır</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
    <?php include 'footer.php'; ?>

</div>
