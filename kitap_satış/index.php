<?php
// index.php
session_start();
include 'baglan.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'];

    if ($form_type === 'login') {
        $eposta = $_POST['eposta'] ?? '';
        $sifre = $_POST['sifre'] ?? '';
        $giris_tipi = $_POST['giris_tipi'] ?? 'kullanici';

        if ($eposta && $sifre) {
            $stmt = $baglanti->prepare("SELECT id, ad_soyad, sifre, yetki FROM kullanicilar WHERE eposta = ?");
            $stmt->bind_param("s", $eposta);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $kullanici = $result->fetch_assoc();
                if (password_verify($sifre, $kullanici['sifre'])) {
                    // Yetkiyi string olarak kontrol et
                    if ($giris_tipi === 'admin') {
                        if ($kullanici['yetki'] === 'admin') { // Admin için 'admin' string'ini kontrol et
                            $_SESSION['kullanici_id'] = $kullanici['id'];
                            $_SESSION['kullanici_adi'] = $kullanici['ad_soyad'];
                            $_SESSION['yetki'] = $kullanici['yetki'];
                            header("Location: admin.php");
                            exit;
                        } else {
                            $error = "Admin yetkiniz yok.";
                        }
                    } else { // Normal kullanıcı girişi (yetki 'uye' olabilir)
                        $_SESSION['kullanici_id'] = $kullanici['id'];
                        $_SESSION['kullanici_adi'] = $kullanici['ad_soyad'];
                        $_SESSION['yetki'] = $kullanici['yetki'];
                        header("Location: anasayfa.php");
                        exit;
                    }
                } else {
                    $error = "Şifre yanlış.";
                }
            } else {
                $error = "Kullanıcı bulunamadı.";
            }
            $stmt->close();
        } else {
            $error = "Lütfen tüm alanları doldurun.";
        }
    } elseif ($form_type === 'register') {
        $ad_soyad = $_POST['ad_soyad'] ?? '';
        $eposta = $_POST['eposta'] ?? '';
        $sifre_plain = $_POST['sifre'] ?? '';
        $yetki_varsayilan = 'uye'; // Yeni kullanıcılar için varsayılan yetki (string)

        if ($ad_soyad && $eposta && $sifre_plain) {
            // E-posta adresinin daha önce kayıtlı olup olmadığını kontrol et
            $check_email_stmt = $baglanti->prepare("SELECT id FROM kullanicilar WHERE eposta = ?");
            $check_email_stmt->bind_param("s", $eposta);
            $check_email_stmt->execute();
            $check_email_result = $check_email_stmt->get_result();

            if ($check_email_result->num_rows > 0) {
                $error = "Bu e-posta adresi zaten kayıtlı.";
            } else {
                $sifre_hashed = password_hash($sifre_plain, PASSWORD_DEFAULT);
                // Yetkiyi string olarak ekle
                $stmt = $baglanti->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, sifre, yetki) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $ad_soyad, $eposta, $sifre_hashed, $yetki_varsayilan);

                if ($stmt->execute()) {
                    $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
                } else {
                    $error = "Kayıt başarısız: " . $baglanti->error; // Hata detayını göster
                }
                $stmt->close();
            }
            $check_email_stmt->close();
        } else {
            $error = "Tüm alanları doldurmanız gerekiyor.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş & Kayıt - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff8dc; font-family: 'Segoe UI', sans-serif; }
        .bee-box {
            max-width: 450px; margin: 60px auto; padding: 30px;
            border: 2px solid #d4af37; border-radius: 20px;
            background-color: #fff; box-shadow: 0 4px 12px rgba(212,175,55,0.4);
        }
        .btn-bee {
            background-color: #d4af37; color: #1a1a1a; font-weight: 600;
        }
        .btn-bee:hover {
            background-color: #b8860b; color: white;
        }
        h2 { color: #d4af37; text-align: center; font-weight: 700; }
        .form-label { font-weight: 600; color: #333; }
        .nav-tabs .nav-link.active { background-color: #d4af37; color: #fff; }
        .nav-tabs .nav-link { font-weight: 600; color: #333; }
    </style>
</head>
<body>
<div class="bee-box">
    <h2>🐝 ArıKitap</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" id="formTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Giriş Yap</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Kayıt Ol</button>
        </li>
    </ul>

    <div class="tab-content" id="formTabContent">
        <!-- Giriş -->
        <div class="tab-pane fade show active" id="login" role="tabpanel">
            <form method="POST" action="index.php">
                <input type="hidden" name="form_type" value="login">

                <div class="mb-3">
                    <label class="form-label">E-posta:</label>
                    <input type="email" name="eposta" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Şifre:</label>
                    <input type="password" name="sifre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Giriş Tipi:</label>
                    <select name="giris_tipi" class="form-select" required>
                        <option value="kullanici" selected>Kullanıcı</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-bee w-100">Giriş Yap</button>
            </form>
        </div>

        <!-- Kayıt -->
        <div class="tab-pane fade" id="register" role="tabpanel">
            <form method="POST" action="index.php">
                <input type="hidden" name="form_type" value="register">

                <div class="mb-3">
                    <label class="form-label">Ad Soyad:</label>
                    <input type="text" name="ad_soyad" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-posta:</label>
                    <input type="email" name="eposta" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Şifre:</label>
                    <input type="password" name="sifre" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-bee w-100">Kayıt Ol</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
