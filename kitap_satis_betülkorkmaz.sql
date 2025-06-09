-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 05 Haz 2025, 19:39:51
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `kitap_satis`
--

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `kitap_yorumlari` (IN `kitap` INT)   BEGIN
    SELECT kullanici_adi, yorum, puan
    FROM yorumlar
    WHERE kitap_id = kitap;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `kullanici_sepeti` (IN `p_kullanici_id` INT)   BEGIN
    SELECT 
        k.baslik AS kitap_adi,
        s.adet,
        k.fiyat,
        (s.adet * k.fiyat) AS toplam_tutar
    FROM sepet s
    JOIN kitaplar k ON s.kitap_id = k.id
    WHERE s.kullanici_id = p_kullanici_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `satin_al` (IN `kullanici` INT, IN `kitap` INT, IN `adet` INT, IN `adres` TEXT, IN `toplam` DECIMAL(10,2))   BEGIN
    DECLARE mevcut_stok INT;
    START TRANSACTION;
    SELECT stok INTO mevcut_stok FROM Kitaplar WHERE id = kitap;
    IF mevcut_stok >= adet THEN
        INSERT INTO satislar (kullanici_id, kitap_id, adet, adres, toplam_tutar)
        VALUES (kullanici, kitap, adet, adres, toplam);
        UPDATE Kitaplar
        SET stok = stok - adet
        WHERE id = kitap;
        COMMIT;
    ELSE
        ROLLBACK;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `satis_ekle` (IN `kullanici` INT, IN `kitap` INT, IN `adet` INT, IN `adres` TEXT, IN `toplam` DECIMAL(10,2))   BEGIN
    INSERT INTO satislar (kullanici_id, kitap_id, adet, adres, toplam_tutar)
    VALUES (kullanici, kitap, adet, adres, toplam);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `yorum_ekle` (IN `kitap` INT, IN `kullanici` INT, IN `yazi` TEXT, IN `puan` INT, IN `adsoyad` VARCHAR(255))   BEGIN
    INSERT INTO yorumlar (kitap_id, kullanici_id, yorum, puan, kullanici_adi)
    VALUES (kitap, kullanici, yazi, puan, adsoyad);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `yorum_guncelle` (IN `yorum_id` INT, IN `yeni_yorum` TEXT, IN `yeni_puan` INT)   BEGIN
    UPDATE yorumlar
    SET yorum = yeni_yorum,
        puan = yeni_puan
    WHERE id = yorum_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `yorum_sil` (IN `yorum_id` INT)   BEGIN
    DELETE FROM yorumlar WHERE id = yorum_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `favoriler`
--

CREATE TABLE `favoriler` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `kitap_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `favoriler`
--

INSERT INTO `favoriler` (`id`, `kullanici_id`, `kitap_id`) VALUES
(1, 2, 3);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategoriler`
--

CREATE TABLE `kategoriler` (
  `id` int(11) NOT NULL,
  `ad` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kategoriler`
--

INSERT INTO `kategoriler` (`id`, `ad`) VALUES
(1, 'Bilim Kurgu'),
(2, 'Tarih'),
(3, 'Roman'),
(4, 'Polisiye');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kitaplar`
--

CREATE TABLE `kitaplar` (
  `id` int(11) NOT NULL,
  `baslik` varchar(255) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `fiyat` decimal(10,2) DEFAULT NULL,
  `ozet` text DEFAULT NULL,
  `resim_url` varchar(255) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kitaplar`
--

INSERT INTO `kitaplar` (`id`, `baslik`, `kategori_id`, `fiyat`, `ozet`, `resim_url`, `stok`) VALUES
(1, 'Bilim Kurgu Sinemasını Okumak', 1, 137.00, 'Bilim kurgu sinemasının tarihine, gelişimine ve bu türün toplumsal ve kültürel etkilerine dair kapsamlı bir inceleme.', 'resimler/BilimKurguSinemasınıOkumak.png', 15),
(2, 'Bilim Kurgu ve Yapay Sinir Ağları', 1, 140.00, 'Bilim kurgu ile yapay sinir ağlarının kesişim noktalarını keşfeden, teknolojinin geleceğine dair öngörüler sunan bir çalışma.', 'resimler/BilimKurguVeYapaySinirAglari.png', 15),
(3, 'Bir Bedenin Gerçeği', 1, 145.00, 'İnsan bedenine dair hem felsefi hem de bilimsel bir inceleme, bedenin sınırlarını ve potansiyelini derinlemesine araştıran bir eser.', 'resimler/BirBedeninGercegi.png', 15),
(4, 'Birinci Dünya Savaşı Tarihi', 2, 150.00, 'Birinci Dünya Savaşı’nın nedenleri, gelişimi ve sonuçlarını detaylı bir şekilde ele alan, savaşın dünya üzerindeki etkilerini analiz eden bir eser.', 'resimler/BirinciDünyaSavaşıTarihi.png', 15),
(5, 'Cennetin Çeşmeleri', 1, 137.00, 'Bilim kurgu dünyasında cennet tasvirleri, ütopyalar ve distopyalar üzerine derinlemesine bir keşif sunan etkileyici bir roman.', 'resimler/CennetinCesmeleri.png', 15),
(6, 'Cinayet Kokusu', 4, 140.00, 'Sürükleyici bir hikaye ile cinayetlerin gizemini çözmek için okuyucuyu ipuçlarıyla dolu bir serüvene çıkaran, gerilim dolu bir roman.', 'resimler/CinayetKokusu.png', 15),
(7, 'Hayvan Çiftliği', 3, 125.00, 'George Orwell’ın totaliter rejimlerin eleştirisini hayvanlar üzerinden yaptığı, alegorik ve çarpıcı bir başyapıt.', 'resimler/HayvanCiftligi.png', 15),
(8, 'Hayvanların Tarihi', 2, 142.00, 'Hayvanların insanlık tarihindeki rolü, evcilleştirilme süreçleri ve kültürel etkileri üzerine detaylı bir inceleme.', 'resimler/HayvanlarınTarihi.png', 15),
(9, 'Kar ve Kan', 3, 130.00, 'İnsanlık, kader ve ahlaki seçimler üzerine derinlemesine düşünceler sunan etkileyici ve sürükleyici bir roman.', 'resimler/KarveKan.png', 15),
(10, 'Kısa Osmanlı Tarihi', 2, 135.00, 'Osmanlı İmparatorluğu’nun kuruluşundan yıkılışına kadar olan süreci, temel olayları ve karakterleriyle anlatan özlü bir tarih kitabı.', 'resimler/KisaOsmanlıTarihi.png', 15),
(11, 'Kumarbaz', 3, 128.00, 'Dostoyevski’nin insan psikolojisi, bağımlılıklar ve aşk üzerine yazdığı, derinlikli ve etkileyici bir klasik roman.', 'resimler/Kumarbaz.png', 15),
(12, 'Mısır Tarihi', 2, 148.00, 'Antik Mısır’ın görkemli geçmişinden modern zamanlara kadar Mısır tarihini kapsamlı bir şekilde ele alan bir eser.', 'resimler/MisirTarihi.png', 15),
(13, 'Sana Vadettiğim Her Şey', 3, 134.00, 'Hayaller, sözler ve gerçeklik arasındaki karmaşık ilişkileri derinlemesine ele alan duygusal ve etkileyici bir hikaye.', 'resimler/SanaVaddettigimHerSey.png', 15),
(14, 'Sarsıntı', 4, 138.00, 'Gerilim ve gizemin doruklarında geçen, okuyucuyu sürekli şaşırtan ve sürükleyen bir roman.', 'resimler/Sarsinti.png', 15),
(15, 'Şeker Portakalı', 3, 120.00, 'Vazgeçilmez bir çocukluk klasiği olan Şeker Portakalı, masumiyetin ve büyümenin hikayesini anlatan dokunaklı bir eser.', 'resimler/SekerPortakali.png', 15),
(16, 'Şeytan Kapısı', 4, 139.00, 'Gizemli olayların peşinde, karanlık ve tedirgin edici bir atmosferde geçen sürükleyici bir gerilim romanı.', 'resimler/SeytanKapısı.png', 15),
(17, 'Suç ve Ceza', 3, 145.00, 'Dostoyevski’nin adalet, suç ve vicdan temalarını işlediği, dünya edebiyatının en önemli eserlerinden biri.', 'resimler/SucVeCeza.png', 15),
(18, 'Tarihi Hoşça Kal Lokantası', 3, 132.00, 'Bir lokantanın geçmişten günümüze uzanan hikayesini, değişen toplumla birlikte ele alan sıcak ve samimi bir roman.', 'resimler/TarihiHoscaKalLokantasi.png', 15),
(19, 'Tutunamayanlar', 3, 150.00, 'Türk edebiyatının unutulmaz eserlerinden biri olan Tutunamayanlar, bireyin toplumla ilişkisini sorgulayan derin bir roman.', 'resimler/Tutunamayanlar.png', 15),
(20, 'Uçurtma Avcısı', 3, 136.00, 'Bir dostluk ve bağışlama hikayesi olan Uçurtma Avcısı, Afganistan’ın çalkantılı tarihini de arka planında işler.', 'resimler/UcurtmaAvcisi.png', 15),
(21, 'Uzun Veda', 1, 129.00, 'Veda ve ayrılık temalarını duygusal bir şekilde işleyen, unutulmaz bir roman.', 'resimler/UzunVeda.png', 15),
(22, 'Vardiya', 3, 126.00, 'İşçilerin hayatlarına dair çarpıcı bir kesit sunan, işçi sınıfının mücadelesini anlatan bir roman.', 'resimler/Vardiya.png', 15),
(23, 'Zamanın Daha Kısa Tarihi', 1, 142.00, 'Stephen Hawking’in zaman ve evrenin doğası üzerine yaptığı çalışmaların daha anlaşılır bir versiyonunu sunan etkileyici bir eser.', 'resimler/ZamanınDahaKisaTarihi.png', 15),
(24, 'Cumhuriyetin Tarihi Yalanları', 2, 143.00, 'Cumhuriyet tarihindeki bazı tartışmalı olayları ve yanlış bilinenleri ele alarak, tarihsel gerçeklere ışık tutmayı amaçlayan kapsamlı bir eser.', 'resimler/CumhuriyetTarihiYalanları.png', 29);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `eposta` varchar(255) DEFAULT NULL,
  `sifre` varchar(255) DEFAULT NULL,
  `ad_soyad` varchar(255) DEFAULT NULL,
  `yetki` varchar(50) DEFAULT 'uye'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `eposta`, `sifre`, `ad_soyad`, `yetki`) VALUES
(1, 'betul@hotmail.com', '$2y$10$BFuGkZlp13rFw5waLdY9IOXps7/0K96qONxJrSdRBXp2wzk56ZgXm', 'betül korkmaz', 'admin'),
(2, 'rasit@mail.com', '$2y$10$KO0PICa883n9zarKkND52uyVA9lUm4ZS1skd814NsLwRjH1NYpQum', 'rasitcanbulat', 'uye'),
(4, 'ahmet@gmail.com', '$2y$10$test5678901234şifrehash', 'Ahmet Yılmaz', 'uye'),
(10, 'betul@example.com', '$2y$10$denemehashsifre', 'Betül Korkmaz', 'uye');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `satislar`
--

CREATE TABLE `satislar` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `kitap_id` int(11) DEFAULT NULL,
  `adet` int(11) DEFAULT 1,
  `adres` text NOT NULL,
  `toplam_tutar` decimal(10,2) NOT NULL,
  `tarih` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `satislar`
--

INSERT INTO `satislar` (`id`, `kullanici_id`, `kitap_id`, `adet`, `adres`, `toplam_tutar`, `tarih`) VALUES
(1, 2, 24, 1, 'aaa', 143.00, '2025-06-05 20:37:08');

--
-- Tetikleyiciler `satislar`
--
DELIMITER $$
CREATE TRIGGER `stok_azalt` AFTER INSERT ON `satislar` FOR EACH ROW BEGIN
    UPDATE Kitaplar
    SET stok = stok - NEW.adet
    WHERE id = NEW.kitap_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `stok_kontrol` BEFORE INSERT ON `satislar` FOR EACH ROW BEGIN
    DECLARE mevcut_stok INT;
    SELECT stok INTO mevcut_stok FROM Kitaplar WHERE id = NEW.kitap_id;
    IF mevcut_stok < 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Yetersiz stok! Satış yapılamaz.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sepet`
--

CREATE TABLE `sepet` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `kitap_id` int(11) DEFAULT NULL,
  `adet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `sepet`
--

INSERT INTO `sepet` (`id`, `kullanici_id`, `kitap_id`, `adet`) VALUES
(7, 2, 3, 1);

--
-- Tetikleyiciler `sepet`
--
DELIMITER $$
CREATE TRIGGER `sepet_silince_stok_artir` AFTER DELETE ON `sepet` FOR EACH ROW BEGIN
    UPDATE Kitaplar
    SET stok = stok + OLD.adet
    WHERE id = OLD.kitap_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yorumlar`
--

CREATE TABLE `yorumlar` (
  `id` int(11) NOT NULL,
  `kitap_id` int(11) DEFAULT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `yorum` text DEFAULT NULL,
  `puan` int(11) DEFAULT NULL,
  `kullanici_adi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `yorumlar`
--

INSERT INTO `yorumlar` (`id`, `kitap_id`, `kullanici_id`, `yorum`, `puan`, `kullanici_adi`) VALUES
(1, 24, 2, 'çok güzel', 2, 'rasitcanbulat');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `favoriler`
--
ALTER TABLE `favoriler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kullanici_id` (`kullanici_id`,`kitap_id`),
  ADD KEY `kitap_id` (`kitap_id`);

--
-- Tablo için indeksler `kategoriler`
--
ALTER TABLE `kategoriler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `kitaplar`
--
ALTER TABLE `kitaplar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eposta` (`eposta`);

--
-- Tablo için indeksler `satislar`
--
ALTER TABLE `satislar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `kitap_id` (`kitap_id`);

--
-- Tablo için indeksler `sepet`
--
ALTER TABLE `sepet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `kitap_id` (`kitap_id`);

--
-- Tablo için indeksler `yorumlar`
--
ALTER TABLE `yorumlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kitap_id` (`kitap_id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `favoriler`
--
ALTER TABLE `favoriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `kategoriler`
--
ALTER TABLE `kategoriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `kitaplar`
--
ALTER TABLE `kitaplar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `satislar`
--
ALTER TABLE `satislar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `sepet`
--
ALTER TABLE `sepet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `yorumlar`
--
ALTER TABLE `yorumlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `favoriler`
--
ALTER TABLE `favoriler`
  ADD CONSTRAINT `favoriler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`),
  ADD CONSTRAINT `favoriler_ibfk_2` FOREIGN KEY (`kitap_id`) REFERENCES `kitaplar` (`id`);

--
-- Tablo kısıtlamaları `kitaplar`
--
ALTER TABLE `kitaplar`
  ADD CONSTRAINT `kitaplar_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `satislar`
--
ALTER TABLE `satislar`
  ADD CONSTRAINT `satislar_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`),
  ADD CONSTRAINT `satislar_ibfk_2` FOREIGN KEY (`kitap_id`) REFERENCES `kitaplar` (`id`);

--
-- Tablo kısıtlamaları `sepet`
--
ALTER TABLE `sepet`
  ADD CONSTRAINT `sepet_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`),
  ADD CONSTRAINT `sepet_ibfk_2` FOREIGN KEY (`kitap_id`) REFERENCES `kitaplar` (`id`);

--
-- Tablo kısıtlamaları `yorumlar`
--
ALTER TABLE `yorumlar`
  ADD CONSTRAINT `yorumlar_ibfk_1` FOREIGN KEY (`kitap_id`) REFERENCES `kitaplar` (`id`),
  ADD CONSTRAINT `yorumlar_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
