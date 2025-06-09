<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    $_SESSION['kullanici_id'] = null; // Giriş yapılmamışsa null olarak tutalım
}

$kullanici_id = $_SESSION['kullanici_id'];

// Kitaplar çekiliyor
// Sayfalama ayarları
$kitapSayisiSayfa = 5; // Her sayfada 5 kitap göster
$sayfa = isset($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;
$baslangic = ($sayfa - 1) * $kitapSayisiSayfa;

// Toplam kitap sayısı
$toplamKitapSorgu = $baglanti->query("SELECT COUNT(*) as toplam FROM kitaplar");
$toplamKitap = $toplamKitapSorgu->fetch_assoc()['toplam'];
$toplamSayfa = ceil($toplamKitap / $kitapSayisiSayfa);

// Kitapları sınırla
$kitaplar = $baglanti->query("
    SELECT kitaplar.id, kitaplar.baslik, kitaplar.resim_url, kitaplar.fiyat, kitaplar.ozet, kategoriler.ad AS kategori_adi 
    FROM kitaplar 
    INNER JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id 
    ORDER BY kitaplar.id DESC 
    LIMIT $baslangic, $kitapSayisiSayfa
");

// Kullanıcının favori kitaplarının id'lerini alıyoruz
$fav_ids = [];
if ($kullanici_id) {
    $fav_sorgu = $baglanti->prepare("SELECT kitap_id FROM favoriler WHERE kullanici_id = ?");
    $fav_sorgu->bind_param("i", $kullanici_id);
    $fav_sorgu->execute();
    $fav_result = $fav_sorgu->get_result();
    while ($row = $fav_result->fetch_assoc()) {
        $fav_ids[] = $row['kitap_id'];
    }
    $fav_sorgu->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>ArıKitap - Anasayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #fff8dc;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        h1 {
            color: #d4af37;
            text-align: center;
            margin-bottom: 40px;
            font-weight: 700;
            text-shadow: 1px 1px 3px #b8860b;
        }
        .card {
            border: 2px solid #d4af37;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(212,175,55,0.4);
            position: relative;
        }
        .card-title {
            color: #b8860b;
            font-weight: 600;
        }
        .btn-bee {
            background-color: #d4af37;
            color: #1a1a1a;
            font-weight: 600;
            border: none;
            transition: background-color 0.3s ease;
        }
        .btn-bee:hover {
            background-color: #b8860b;
            color: #fff;
        }
        .accordion-button {
            background-color: #d4af37;
            color: #1a1a1a;
            font-weight: 600;
        }
        .accordion-button:not(.collapsed) {
            background-color: #b8860b;
            color: white;
            box-shadow: none;
        }
        .accordion-body {
            background-color: #fff8dc;
            color: #333;
        }
        .kitap-resim {
            max-width: 120px;
            max-height: 160px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 0 5px #d4af37;
            margin-bottom: 15px;
        }
        .navbar-nav .nav-link {
            color: #1a1a1a !important;
            font-weight: 600;
        }
        .navbar-nav .nav-link:hover {
            color: #fff !important;
        }
        /* Kalp stili */
        .fav-heart {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #bbb;
            transition: color 0.3s ease;
            user-select: none;
            z-index: 10;
        }
        .fav-heart.favorited {
            color: #e63946;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d4af37;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="anasayfa.php" style="color: #1a1a1a;">🐝 ArıKitap</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" 
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Kategoriler Dropdown -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold" href="#" id="kategoriDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color:#1a1a1a;">
                        Kategoriler
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="kategoriDropdown">
                        <?php
                        $kategoriler = $baglanti->query("SELECT * FROM kategoriler ORDER BY ad ASC");
                        while ($kategori = $kategoriler->fetch_assoc()):
                        ?>
                            <li><a class="dropdown-item" href="kategori.php?id=<?= $kategori['id'] ?>"><?= htmlspecialchars($kategori['ad']) ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
            </ul>

            <!-- Sağdaki linkler -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <?php if ($kullanici_id): ?>
                    <li class="nav-item me-3">
                        <span class="text-dark fw-semibold">Hoşgeldiniz, <?= htmlspecialchars($_SESSION['kullanici_adi'] ?? 'Kullanıcı') ?>!</span>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-semibold" href="favoriler.php">Favoriler</a>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-semibold" href="sepet.php">Sepet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="cikis.php">Çıkış Yap</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-semibold" href="index.php">Giriş Yap</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
<?php if (isset($_SESSION['sepet_mesaj'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['sepet_mesaj']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
    <?php unset($_SESSION['sepet_mesaj']); ?>
<?php endif; ?>


    <h1>🐝 ArıKitap - Kitaplar 🐝</h1>
    

    <!-- Arama Kutusu -->
    <div class="mb-4">
        <input type="text" id="aramaKutusu" class="form-control" placeholder="Kitap adıyla arayın...">
    </div>


    <?php while ($kitap = $kitaplar->fetch_assoc()) : 
        $favorited = in_array($kitap['id'], $fav_ids);
    ?>
        <div class="card mb-4 p-3">
            <div class="row g-3 align-items-center position-relative">
                <!-- Kalp İkonu -->
                <?php if ($kullanici_id): ?>
                    <div class="fav-heart <?= $favorited ? 'favorited' : '' ?>" data-kitap-id="<?= $kitap['id'] ?>" title="Favorilere Ekle/Çıkar">
                        &#10084;
                    </div>
                <?php endif; ?>

                <?php if (!empty($kitap['resim_url'])): ?>
                    <div class="col-md-2 text-center">
                        <img src="<?= htmlspecialchars($kitap['resim_url']) ?>" alt="Kitap Resmi" class="kitap-resim" />
                    </div>
                <?php endif; ?>
                <div class="<?= !empty($kitap['resim_url']) ? 'col-md-10' : 'col-12' ?>">
                    <h5 class="card-title"><?= htmlspecialchars($kitap['baslik']) ?></h5>
                    <p><strong>Kategori:</strong> <?= htmlspecialchars($kitap['kategori_adi']) ?></p>
                    <p><?= htmlspecialchars($kitap['ozet']) ?></p>
                    <p><strong>Fiyat:</strong> <?= htmlspecialchars($kitap['fiyat']) ?> ₺</p>

                    <!-- Sepete Ekle Formu -->
                    <?php if ($kullanici_id) : ?>
                        <form action="sepete_ekle.php" method="POST" class="mt-2">
                            <input type="hidden" name="kitap_id" value="<?= $kitap['id'] ?>">
                            <input type="hidden" name="kitap_adi" value="<?= htmlspecialchars($kitap['baslik']) ?>">
                            <input type="hidden" name="adet" value="1">
                            <button type="submit" class="btn btn-bee">Sepete Ekle</button>
                        </form>
                    <?php else : ?>
                        <p class="mt-2"><a href="giris.php" class="text-decoration-none" style="color:#b8860b; font-weight:600;">Giriş yap</a>arak sepete kitap ekleyebilirsiniz.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accordion Yorumlar -->
            <div class="accordion mt-3" id="yorumAccordion<?= $kitap['id'] ?>">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $kitap['id'] ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $kitap['id'] ?>" aria-expanded="false" aria-controls="collapse<?= $kitap['id'] ?>">
                            🐝 Yorumları Göster/Gizle
                        </button>
                    </h2>
                    <div id="collapse<?= $kitap['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $kitap['id'] ?>" data-bs-parent="#yorumAccordion<?= $kitap['id'] ?>">
                        <div class="accordion-body">
                            <?php
                            $kitap_id = $kitap['id'];
                            $yorumlar_sorgu = $baglanti->prepare("SELECT y.yorum, y.puan, k.ad_soyad FROM yorumlar y JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.kitap_id = ? ORDER BY y.id DESC");
                            $yorumlar_sorgu->bind_param("i", $kitap_id);
                            $yorumlar_sorgu->execute();
                            $yorumlar_result = $yorumlar_sorgu->get_result();

                            if ($yorumlar_result->num_rows > 0) {
                                while ($yorum = $yorumlar_result->fetch_assoc()) {
                                    echo "<p><strong>" . htmlspecialchars($yorum['ad_soyad']) . "</strong> (" . htmlspecialchars($yorum['puan']) . "/5):<br>" . htmlspecialchars($yorum['yorum']) . "</p><hr>";
                                }
                            } else {
                                echo "<p>Henüz yorum yapılmamış.</p>";
                            }
                            $yorumlar_sorgu->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yorum ve Puan Ekleme -->
            <?php if ($kullanici_id) : ?>
                <button class="btn btn-bee mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#yorumForm<?= $kitap['id'] ?>" aria-expanded="false" aria-controls="yorumForm<?= $kitap['id'] ?>">
                    Yorum ve Puan Ekle
                </button>

                <div class="collapse mt-3" id="yorumForm<?= $kitap['id'] ?>">
                    <form action="yorum_ve_puan_ekle.php" method="POST">
                        <input type="hidden" name="kitap_id" value="<?= htmlspecialchars($kitap['id']) ?>">
                        <div class="mb-3">
                            <label for="yorum" class="form-label">Yorum Yap:</label>
                            <textarea name="yorum" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="puan" class="form-label">Puan Ver (1-5):</label>
                            <input type="number" name="puan" class="form-control" min="1" max="5" required>
                        </div>
                        <button type="submit" class="btn btn-warning">Gönder</button>
                    </form>
                </div>
            <?php else : ?>
                <p class="mt-3"><a href="giris.php" class="text-decoration-none" style="color:#b8860b; font-weight:600;">Giriş yap</a>arak yorum yapabilirsiniz.</p>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Favori kalp ikonuna tıklanınca AJAX ile favorilere ekle/sil işlemi yapalım
document.querySelectorAll('.fav-heart').forEach(heart => {
    heart.addEventListener('click', () => {
        const kitapId = heart.getAttribute('data-kitap-id');

        fetch('favorilere_ekle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'kitap_id=' + encodeURIComponent(kitapId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                heart.classList.toggle('favorited', data.favorited);
            } else {
                alert(data.message || 'Bir hata oluştu!');
            }
        })
        .catch(() => alert('İstek gönderilemedi!'));
    });
});



// Canlı kitap başlığı filtreleme
document.getElementById('aramaKutusu').addEventListener('input', function() {
    const arama = this.value.toLowerCase();
    const kitapKartlari = document.querySelectorAll('.card.mb-4');

    kitapKartlari.forEach(kart => {
        const baslik = kart.querySelector('.card-title').textContent.toLowerCase();
        if (baslik.startsWith(arama)) {
            kart.style.display = '';
        } else {
            kart.style.display = 'none';
        }
    });
});


</script>

<!-- Sayfalama -->
<nav aria-label="Kitap Sayfalama" class="mt-4">
  <ul class="pagination justify-content-center">
    <?php if ($sayfa > 1): ?>
      <li class="page-item"><a class="page-link" href="?sayfa=<?= $sayfa - 1 ?>">« Geri</a></li>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $toplamSayfa; $i++): ?>
      <li class="page-item <?= $i == $sayfa ? 'active' : '' ?>"><a class="page-link" href="?sayfa=<?= $i ?>"><?= $i ?></a></li>
    <?php endfor; ?>

    <?php if ($sayfa < $toplamSayfa): ?>
      <li class="page-item"><a class="page-link" href="?sayfa=<?= $sayfa + 1 ?>">İleri »</a></li>
    <?php endif; ?>
  </ul>
</nav>
<?php include 'footer.php'; ?>



</body>
</html>