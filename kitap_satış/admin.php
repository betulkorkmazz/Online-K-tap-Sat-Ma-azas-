<?php
// admin.php
session_start();
include 'baglan.php'; // Veritabanı bağlantısı

// Admin kontrolü - yetkiyi string 'admin' olarak kontrol et
if (!isset($_SESSION['kullanici_id']) || $_SESSION['yetki'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

// Silme işlemleri
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];

    if ($action === 'delete_kitap') {
        // Kitap silinmeden önce ilişkili kayıtlar (satislar, sepet, favoriler, yorumlar) silinmeli veya kitap_id null yapılmalı
        // Bu örnekte basitlik adına doğrudan silme yapılıyor, ancak CASCADE kuralları tanımlanmadıysa hata verebilir.
        // Önce yorumları sil
        $stmt_yorum = $baglanti->prepare("DELETE FROM yorumlar WHERE kitap_id = ?");
        $stmt_yorum->bind_param("i", $id);
        $stmt_yorum->execute();
        $stmt_yorum->close();

        // Sonra favorileri sil
        $stmt_fav = $baglanti->prepare("DELETE FROM favoriler WHERE kitap_id = ?");
        $stmt_fav->bind_param("i", $id);
        $stmt_fav->execute();
        $stmt_fav->close();
        
        // Sonra sepeti sil
        $stmt_sepet = $baglanti->prepare("DELETE FROM sepet WHERE kitap_id = ?");
        $stmt_sepet->bind_param("i", $id);
        $stmt_sepet->execute();
        $stmt_sepet->close();

        // Sonra satışları sil
        $stmt_satis = $baglanti->prepare("DELETE FROM satislar WHERE kitap_id = ?");
        $stmt_satis->bind_param("i", $id);
        $stmt_satis->execute();
        $stmt_satis->close();

        // En son kitabı sil
        $stmt = $baglanti->prepare("DELETE FROM kitaplar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Kitap ve ilişkili tüm kayıtlar başarıyla silindi.";
        } else {
            $error = "Kitap silme işlemi başarısız: " . $baglanti->error;
        }
        $stmt->close();

    } elseif ($action === 'delete_yorum') {
        $stmt = $baglanti->prepare("DELETE FROM yorumlar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Yorum başarıyla silindi.";
        } else {
            $error = "Yorum silme işlemi başarısız: " . $baglanti->error;
        }
        $stmt->close();
    } elseif ($action === 'delete_kullanici') {
        // Kullanıcı silinmeden önce ilişkili kayıtlar (satislar, sepet, favoriler, yorumlar) silinmeli veya kullanici_id null yapılmalı
        // Ya da ON DELETE CASCADE / SET NULL kuralları veritabanında tanımlanmalı
        // Örnek: Yorumları, favorileri, sepeti, satışları bu kullanıcıya ait olanları sil
        $stmt_yorum = $baglanti->prepare("DELETE FROM yorumlar WHERE kullanici_id = ?");
        $stmt_yorum->bind_param("i", $id);
        $stmt_yorum->execute();
        $stmt_yorum->close();

        $stmt_fav = $baglanti->prepare("DELETE FROM favoriler WHERE kullanici_id = ?");
        $stmt_fav->bind_param("i", $id);
        $stmt_fav->execute();
        $stmt_fav->close();

        $stmt_sepet = $baglanti->prepare("DELETE FROM sepet WHERE kullanici_id = ?");
        $stmt_sepet->bind_param("i", $id);
        $stmt_sepet->execute();
        $stmt_sepet->close();
        
        $stmt_satis = $baglanti->prepare("DELETE FROM satislar WHERE kullanici_id = ?");
        $stmt_satis->bind_param("i", $id);
        $stmt_satis->execute();
        $stmt_satis->close();

        // Sonra kullanıcıyı sil
        $stmt = $baglanti->prepare("DELETE FROM kullanicilar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Kullanıcı ve ilişkili tüm kayıtları başarıyla silindi.";
        } else {
            $error = "Kullanıcı silme işlemi başarısız: " . $baglanti->error;
        }
        $stmt->close();
    }
     // Sayfayı yeniden yönlendirerek GET parametrelerinin tekrar işlenmesini engelle
    header("Location: admin.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit;
}

// Yönlendirmeden sonra mesajları al
if (isset($_GET['success'])) $success = urldecode($_GET['success']); // urldecode ile al
if (isset($_GET['error'])) $error = urldecode($_GET['error']); // urldecode ile al


// Kitap ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kitap_ekle'])) {
    $baslik = $_POST['baslik'] ?? '';
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $fiyat = (float)($_POST['fiyat'] ?? 0);
    $ozet = $_POST['ozet'] ?? '';
    $resim_url = $_POST['resim_url'] ?? '';
    $stok = (int)($_POST['stok'] ?? 0); // Stok bilgisi eklendi

    if ($baslik && $kategori_id > 0 && $fiyat >= 0 && $ozet && $stok >= 0) {
        // Kitaplar tablosuna stok sütunu eklenmiş olmalı
        $stmt = $baglanti->prepare("INSERT INTO kitaplar (baslik, kategori_id, fiyat, ozet, resim_url, stok) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sidssi", $baslik, $kategori_id, $fiyat, $ozet, $resim_url, $stok);
        if ($stmt->execute()) {
            $success = "Kitap başarıyla eklendi.";
        } else {
            $error = "Kitap ekleme işlemi başarısız: " . $baglanti->error;
        }
        $stmt->close();
    } else {
        $error = "Lütfen tüm kitap bilgilerini (başlık, kategori, fiyat, özet, stok) doğru şekilde doldurun.";
    }
     // Sayfayı yeniden yönlendirerek POST verilerinin tekrar işlenmesini engelle
    header("Location: admin.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit;
}


// Verileri çek
// Stok bilgisi de çekiliyor
$kitaplar_result = $baglanti->query("SELECT kitaplar.*, kategoriler.ad AS kategori_adi FROM kitaplar LEFT JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id ORDER BY kitaplar.id DESC");

$yorumlar_result = $baglanti->query("SELECT yorumlar.*, kitaplar.baslik AS kitap_baslik, kullanicilar.ad_soyad AS kullanici_adi 
                             FROM yorumlar 
                             LEFT JOIN kitaplar ON yorumlar.kitap_id = kitaplar.id 
                             LEFT JOIN kullanicilar ON yorumlar.kullanici_id = kullanicilar.id ORDER BY yorumlar.id DESC");

$kullanicilar_result = $baglanti->query("SELECT * FROM kullanicilar ORDER BY id DESC");

$satislar_result = $baglanti->query("SELECT satislar.*, kullanicilar.ad_soyad AS kullanici_adi, kitaplar.baslik AS kitap_adi 
                              FROM satislar 
                              LEFT JOIN kullanicilar ON satislar.kullanici_id = kullanicilar.id 
                              LEFT JOIN kitaplar ON satislar.kitap_id = kitaplar.id ORDER BY satislar.id DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Admin Paneli - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #FFD700; /* Sarı arka plan */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #3b2f0b; /* Koyu kahverengi arı teması */
            margin: 0;
            padding: 0;
        }

        .admin-box {
            max-width: 1100px;
            margin: 40px auto;
            background: #fffbee;
            border-radius: 20px;
            box-shadow: 0 0 20px 5px rgba(60, 45, 0, 0.2);
            padding: 30px 40px;
            border: 4px solid #f7c948; /* açık altın sarısı sınır */
        }

        h2, h3 {
            color: #6b4c00; /* koyu altın */
            text-shadow: 1px 1px 0 #f7c948;
            margin-bottom: 25px;
        }

        a.btn-exit {
            background-color: #d95c00;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-bottom: 35px;
        }

        a.btn-exit:hover {
            background-color: #b34700;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-bottom: 50px;
        }

        thead tr {
            background-color: #f7c948;
            color: #3b2f0b;
            font-weight: 700;
            /* border-radius: 15px; */ /* Genelde başlıklara border-radius verilmez, hücrelere verilir */
        }
        
        thead tr th:first-child {
            border-top-left-radius: 15px;
            border-bottom-left-radius: 15px;
        }

        thead tr th:last-child {
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
        }


        thead tr th {
            padding: 12px 18px;
            text-align: left;
        }

        tbody tr {
            background-color: #fffbee;
            border: 1px solid #f7c948;
            border-radius: 15px; /* Satır bazında border radius */
        }
        
        tbody tr td {
            padding: 12px 18px;
            vertical-align: middle;
        }


        tbody tr:hover {
            background-color: #fff3b0;
            /* cursor: pointer; */ 
        }

        .btn-danger {
            background-color: #d95c00;
            border: none;
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #b34700;
            color: #fff;
        }

        .btn-success {
            background-color: #6b4c00;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 700;
            transition: background-color 0.3s ease;
        }

        .btn-success:hover {
            background-color: #8a6700;
            color: #fff;
        }

        input, textarea, select { 
            border-radius: 15px;
            border: 2px solid #f7c948;
            padding: 10px 15px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            width: 100%;
            box-sizing: border-box;
            background-color: #fffbee;
            color: #3b2f0b;
        }

        input:focus, textarea:focus, select:focus { 
            border-color: #d95c00;
            outline: none;
            background-color: #fff8c4;
        }

        label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
            color: #6b4c00;
        }

        .alert { /* Genel alert stili */
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-weight: 700;
            border: 1px solid transparent;
        }
        .alert-danger {
            background-color: #d95c0059; /* Arkaplan rengi daha açık */
            border-color: #d95c00; /* Kenarlık rengi */
            color: #6b4c00; /* Metin rengi */
        }

        .alert-success {
            background-color: #c8e6c9a1; /* Daha yumuşak bir yeşil tonu */
            border-color: #5cb85ca1;   /* Kenarlık rengi */
            color: #2b572e;     /* Metin rengi */
        }
    </style>
</head>
<body>
    <div class="admin-box">
        <h2>🐝 ArıKitap Admin Paneli</h2>
        <p>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['kullanici_adi']) ?></strong>!</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
             <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>


        <a href="cikis.php" class="btn-exit">Çıkış Yap</a>

        <!-- Kitap Ekleme Formu -->
        <h3>Yeni Kitap Ekle</h3>
        <form method="POST" action="admin.php" class="mb-5">
            <input type="hidden" name="kitap_ekle" value="1" />
            <div class="mb-3">
                <label for="baslik">Başlık:</label>
                <input type="text" id="baslik" name="baslik" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="kategori_id">Kategori:</label>
                 <select id="kategori_id" name="kategori_id" class="form-select" required>
                    <option value="">Kategori Seçin...</option>
                    <?php
                    $kategoriler_list = $baglanti->query("SELECT id, ad FROM kategoriler ORDER BY ad ASC");
                    if ($kategoriler_list->num_rows > 0) {
                        while ($kategori_item = $kategoriler_list->fetch_assoc()) {
                            echo "<option value=\"{$kategori_item['id']}\">" . htmlspecialchars($kategori_item['ad']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="fiyat">Fiyat (₺):</label>
                <input type="number" step="0.01" id="fiyat" name="fiyat" class="form-control" required />
            </div>
             <div class="mb-3">
                <label for="stok">Stok Adedi:</label> <!-- Stok alanı eklendi -->
                <input type="number" id="stok" name="stok" class="form-control" required min="0" />
            </div>
            <div class="mb-3">
                <label for="ozet">Özet:</label>
                <textarea id="ozet" name="ozet" rows="3" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="resim_url">Resim URL:</label>
                <input type="text" id="resim_url" name="resim_url" class="form-control" />
            </div>
            <button type="submit" class="btn btn-success">Kitap Ekle</button>
        </form>

        <!-- Kitap Listesi -->
        <h3>Kitaplar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Fiyat (₺)</th>
                    <th>Stok</th> <!-- Stok başlığı eklendi -->
                    <th>Özet</th>
                    <th>Resim URL</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($kitaplar_result && $kitaplar_result->num_rows > 0): ?>
                    <?php while($kitap = $kitaplar_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $kitap['id'] ?></td>
                            <td><?= htmlspecialchars($kitap['baslik']) ?></td>
                            <td><?= htmlspecialchars($kitap['kategori_adi']) ?></td>
                            <td><?= number_format($kitap['fiyat'], 2) ?></td>
                            <td><?= $kitap['stok'] ?? '0' ?></td>
                            <td><?= htmlspecialchars(substr($kitap['ozet'], 0, 100)) . (strlen($kitap['ozet']) > 100 ? '...' : '') ?></td>
                            <td><img src="<?= htmlspecialchars($kitap['resim_url']) ?>" alt="<?= htmlspecialchars($kitap['baslik']) ?>" style="width: 50px; height: auto; border-radius: 5px;" onerror="this.style.display='none'"></td>
                            <td>
                                <a href="admin.php?action=delete_kitap&id=<?= $kitap['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu kitabı ve ilişkili tüm kayıtlarını (yorumlar, favoriler, sepetler, satışlar) silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')">Sil</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">Gösterilecek kitap bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Yorumlar -->
        <h3>Yorumlar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kitap</th>
                    <th>Kullanıcı</th>
                    <th>Yorum</th>
                    <th>Puan</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($yorumlar_result && $yorumlar_result->num_rows > 0): ?>
                    <?php while($yorum = $yorumlar_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $yorum['id'] ?></td>
                            <td><?= htmlspecialchars($yorum['kitap_baslik']) ?></td>
                            <td><?= htmlspecialchars($yorum['kullanici_adi'] ? $yorum['kullanici_adi'] : 'Bilinmeyen Kullanıcı') ?></td>
                            <td><?= htmlspecialchars(substr($yorum['yorum'], 0, 100)) . (strlen($yorum['yorum']) > 100 ? '...' : '') ?></td>
                            <td><?= $yorum['puan'] ?></td>
                            <td>
                                <a href="admin.php?action=delete_yorum&id=<?= $yorum['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">Gösterilecek yorum bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Kullanıcılar -->
        <h3>Kullanıcılar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Yetki</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                 <?php if ($kullanicilar_result && $kullanicilar_result->num_rows > 0): ?>
                    <?php while($kullanici = $kullanicilar_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $kullanici['id'] ?></td>
                            <td><?= htmlspecialchars($kullanici['ad_soyad']) ?></td>
                            <td><?= htmlspecialchars($kullanici['eposta']) ?></td>
                            <td><?= htmlspecialchars($kullanici['yetki']) ?></td> 
                            <td>
                                <?php if ($kullanici['id'] != $_SESSION['kullanici_id']): ?>
                                <a href="admin.php?action=delete_kullanici&id=<?= $kullanici['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu kullanıcıyı ve ilişkili tüm kayıtlarını (yorumlar, favoriler, sepetler, satışlar) silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')">Sil</a>
                                <?php else: ?>
                                    Kendini Silemezsin
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Gösterilecek kullanıcı bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Satışlar -->
        <h3>Satışlar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı</th>
                    <th>Kitap</th>
                    <th>Adet</th>
                    <th>Toplam Tutar (₺)</th>
                    <th>Adres</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                 <?php if ($satislar_result && $satislar_result->num_rows > 0): ?>
                    <?php while ($satis = $satislar_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $satis['id'] ?></td>
                            <td><?= htmlspecialchars($satis['kullanici_adi'] ?? 'Bilinmeyen Kullanıcı') ?></td>
                            <td><?= htmlspecialchars($satis['kitap_adi'] ?? 'Bilinmeyen Kitap') ?></td>
                            <td><?= $satis['adet'] ?></td>
                            <td><?= number_format($satis['toplam_tutar'], 2) ?></td>
                            <td><?= htmlspecialchars($satis['adres']) ?></td>
                            <td><?= date("d.m.Y H:i:s", strtotime($satis['tarih'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Gösterilecek satış bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
