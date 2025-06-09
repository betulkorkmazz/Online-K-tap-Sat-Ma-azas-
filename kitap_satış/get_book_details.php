<?php
session_start();
include 'baglan.php';

if (isset($_GET['id'])) {
    $kitap_id = intval($_GET['id']);

    $sql = "SELECT kitaplar.id, kitaplar.baslik, kitaplar.resim_url, kitaplar.fiyat, kitaplar.ozet, kategoriler.ad AS kategori_adi
            FROM kitaplar
            INNER JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id
            WHERE kitaplar.id = ?";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("i", $kitap_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $response = $book;

        if (isset($_SESSION['kullanici_id'])) {
            $yorum_sql = "SELECT kullanicilar.ad_soyad, yorumlar.yorum AS yorum_metni, yorumlar.puan
                          FROM yorumlar
                          INNER JOIN kullanicilar ON yorumlar.kullanici_id = kullanicilar.id
                          WHERE yorumlar.kitap_id = ?";
            $yorum_stmt = $baglanti->prepare($yorum_sql);
            $yorum_stmt->bind_param("i", $kitap_id);
            $yorum_stmt->execute();
            $yorum_result = $yorum_stmt->get_result();

            $yorumlar = [];
            while ($yorum = $yorum_result->fetch_assoc()) {
                $yorumlar[] = $yorum;
            }
            $response['yorumlar'] = $yorumlar;
        } else {
            $response['yorumlar'] = [];
        }

        echo json_encode($response);
    } else {
        echo json_encode(["error" => "Kitap bulunamadı."]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Geçersiz istek."]);
}
?>