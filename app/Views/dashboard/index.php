<?php
/**
 * Data dashboard diberikan oleh Dashboard::index().
 * Default di bawah hanya mencegah notice IDE/runtime bila view dibuka tanpa data;
 * ini bukan data contoh atau data transaksi.
 */
$userName = isset($userName) && is_string($userName) ? $userName : '';
$cartCount = isset($cartCount) ? max(0, (int) $cartCount) : 0;
$packageCount = isset($packageCount) && is_numeric($packageCount) ? (int) $packageCount : null;
$extraCount = isset($extraCount) && is_numeric($extraCount) ? (int) $extraCount : null;
$featuredPackages = isset($featuredPackages) && is_array($featuredPackages) ? $featuredPackages : [];
$dashboardDataAvailable = isset($dashboardDataAvailable) ? (bool) $dashboardDataAvailable : false;
$displayName = $userName !== '' ? explode(' ', trim($userName))[0] : null;
$cardClasses = ['wedding', 'church', 'mourning', 'cross', 'bouquet'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beranda | Nauli Dekor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
</head>
<body>
    <?= view('partials/navbar', ['activeNav' => 'home']) ?>

    <main>
        <section class="hero">
            <div class="hero-inner">
                <p class="section-label light">NAULI DEKOR</p>
                <h1>Menghadirkan keindahan<br>di setiap momen.</h1>
                <p>Kami merangkai dekorasi yang personal dan berkesan untuk momen paling istimewa Anda.</p>
                <a class="cta" href="<?= base_url('paket-dekorasi') ?>">Pesan Sekarang <span>→</span></a>
            </div>
            <span class="hero-number">
                <?php if ($dashboardDataAvailable && $packageCount !== null && $extraCount !== null): ?>
                    <?= esc((string) $packageCount) ?> Paket · <?= esc((string) $extraCount) ?> Tambahan
                <?php else: ?>
                    Data katalog belum tersedia
                <?php endif; ?>
            </span>
        </section>

        <section class="highlights" aria-label="Keunggulan layanan">
            <div><span>✦</span><p><b>Desain Personal</b><br>Dibuat sesuai cerita Anda</p></div>
            <div><span>✦</span><p><b>Tim Berpengalaman</b><br>Setiap detail terjaga</p></div>
            <div><span>✦</span><p><b>Kualitas Terbaik</b><br>Material pilihan</p></div>
            <div><span>✦</span><p><b>Pelayanan Hangat</b><br>Dari awal hingga selesai</p></div>
        </section>

        <section class="collection" id="koleksi">
            <p class="section-label">KOLEKSI KAMI</p>
            <div class="collection-title"><h2>Dekorasi untuk setiap cerita</h2><a href="<?= base_url('paket-dekorasi') ?>">Lihat semua koleksi <span>→</span></a></div>
            <p class="collection-intro">Temukan inspirasi dekorasi yang dirancang dengan penuh cinta, untuk merayakan momen penting dalam hidup Anda.</p>
            <?php if ($featuredPackages !== []): ?>
                <div class="gallery">
                    <?php foreach ($featuredPackages as $index => $package): ?>
                        <?php
                        $packageId = (int) ($package['id'] ?? 0);
                        $packageName = trim((string) ($package['nama_paket'] ?? ''));
                        $category = trim((string) ($package['kategori'] ?? ''));
                        $packageType = trim((string) ($package['jenis_paket'] ?? ''));
                        $imageName = basename((string) ($package['gambar'] ?? ''));
                        $imagePath = FCPATH . 'assets/images/' . $imageName;
                        $hasImage = $imageName !== '' && is_file($imagePath);
                        $cardClass = $cardClasses[$index % count($cardClasses)];
                        $packageMeta = trim(ucfirst($category) . ($packageType !== '' ? ' · ' . ucfirst($packageType) : ''));
                        ?>
                        <?php if ($packageId > 0 && $packageName !== ''): ?>
                            <a
                                class="card <?= esc($cardClass) ?>"
                                href="<?= base_url('paket/' . $packageId) ?>"
                                <?php if ($hasImage): ?>style="background-image:linear-gradient(0deg,rgba(9,8,8,.7),rgba(0,0,0,.02)),url('<?= esc(base_url('assets/images/' . rawurlencode($imageName)), 'attr') ?>')"<?php endif; ?>
                            >
                                <div>
                                    <span><?= esc(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                    <h3><?= esc($packageName) ?></h3>
                                    <p><?= esc($packageMeta) ?></p>
                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="collection-intro">
                    <?= $dashboardDataAvailable ? 'Belum ada paket dekorasi aktif untuk ditampilkan.' : 'Data koleksi belum dapat dimuat. Silakan coba kembali.' ?>
                </p>
            <?php endif; ?>
        </section>

        <section class="story" id="tentang">
            <div class="story-image"></div>
            <div class="story-copy">
                <p class="section-label">TENTANG NAULI</p>
                <h2>Bukan sekadar dekorasi, sebuah pengalaman.</h2>
                <p>Nauli Dekor percaya bahwa setiap perayaan memiliki cerita. Dengan sentuhan detail dan rangkaian bunga pilihan, kami mengubah ruang menjadi kenangan yang akan selalu dikenang.</p>
                <a class="dark-link" href="<?= base_url('paket-dekorasi') ?>">Jelajahi koleksi <span>→</span></a>
            </div>
        </section>
    </main>

    <footer id="kontak"><a class="logo" href="<?= base_url('dashboard') ?>"><span>✦</span> NAULI <small>DEKOR</small></a><p>Merangkai momen istimewa Anda.</p><p>© <?= date('Y') ?> Nauli Dekor</p></footer>
    <script>
        const menuToggle = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', () => mainNav.classList.toggle('open'));
        }
    </script>
</body>
</html>
