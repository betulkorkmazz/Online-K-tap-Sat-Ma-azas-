<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

$kullanici_id = isset($_SESSION['kullanici_id']) ? $_SESSION['kullanici_id'] : null;
?>

<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d4af37;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="anasayfa.php" style="color: #1a1a1a;">🐝 ArıKitap</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" 
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold" href="#" id="kategoriDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color:#1a1a1a;">
                        Kategoriler
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="kategoriDropdown">
                        <?php
                        $izin_verilen_idler_str = implode(',', [1, 2, 3, 4]);
                        $kategoriler = $baglanti->query("SELECT * FROM kategoriler WHERE id IN ($izin_verilen_idler_str) ORDER BY ad ASC");
                        while ($kategori = $kategoriler->fetch_assoc()):
                        ?>
                            <li><a class="dropdown-item" href="kategori.php?id=<?= $kategori['id'] ?>"><?= htmlspecialchars($kategori['ad']) ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
            </ul>

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
                        <a class="nav-link fw-semibold" href="giris.php">Giriş Yap</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
