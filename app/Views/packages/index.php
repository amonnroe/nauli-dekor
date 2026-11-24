<?php
/**
 * Data katalog dikirim dari Packages::index().
 *
 * @var string $pageTitle
 * @var string $packageDescription
 * @var array<int, array<string, mixed>> $packages
 * @var array<int, array<string, mixed>> $extras
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?> | Nauli Dekor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/packages.css') ?>">
</head>
<body>
    <?= view('partials/navbar', ['activeNav' => 'packages']) ?>

    <main>
        <section class="page-heading">
            <div>
                <p class="eyebrow">KOLEKSI NAULI DEKOR</p>
                <h1>Pilih paket dekorasi Anda</h1>
                <p>Rangkaian dekorasi yang dirancang untuk menyempurnakan setiap momen berharga.</p>
            </div>
            <nav class="breadcrumbs">
                <a href="<?= base_url('dashboard') ?>">Beranda</a><span>/</span><b>Paket Dekorasi</b>
            </nav>
        </section>

        <section class="package-layout">
            <div class="packages-content">
                <div class="content-top">
                    <div>
                        <p class="eyebrow" id="type-label">PAKET DEKORASI</p>
                        <h2 id="packages-title"><?= esc($pageTitle) ?></h2>
                    </div>
                    <p class="result-count"><b id="result-number"><?= count($packages) ?></b> pilihan tersedia</p>
                </div>
                <p class="package-description" id="package-description"><?= esc($packageDescription) ?></p>

                <div class="cards" id="package-cards">
                    <?php if ($packages === []): ?>
                        <article class="package-card">
                            <div class="card-content">
                                <p class="card-type">INFORMASI PAKET</p>
                                <h3>Belum ada paket tersedia</h3>
                                <p class="card-subtitle">Belum ada data paket aktif saat ini.</p>
                            </div>
                        </article>
                    <?php else: ?>
                        <?php $cardClasses = ['church', 'grand', 'hall', 'stage', 'chapel']; ?>
                        <?php foreach ($packages as $index => $package): ?>
                            <?php
                                $imageName = ! empty($package['gambar']) ? basename((string) $package['gambar']) : '';
                                $imageUrl = $imageName !== '' ? base_url('assets/images/' . rawurlencode($imageName)) : '';
                                $cardClass = $cardClasses[$index % count($cardClasses)];
                            ?>
                            <article class="package-card <?= esc($cardClass) ?>">
                                <div
                                    class="card-image"
                                    <?php if ($imageUrl !== ''): ?>
                                        style="background-image: linear-gradient(0deg, rgba(12, 9, 8, .32), transparent), url('<?= esc($imageUrl) ?>');"
                                    <?php else: ?>
                                        style="background-image: linear-gradient(135deg, #eee5d8, #c9b99e);"
                                    <?php endif; ?>
                                >
                                    <span class="card-number"><?= esc(sprintf('%02d', $index + 1)) ?></span>
                                    <span class="tag">Paket Dekorasi</span>
                                </div>
                                <div class="card-content">
                                    <p class="card-type">PAKET DEKORASI</p>
                                    <h3><?= esc($package['nama_paket']) ?></h3>
                                    <p class="card-subtitle"><?= esc($package['deskripsi'] ?: $package['lokasi']) ?></p>
                                    <div class="card-bottom">
                                        <div>
                                            <small>Harga paket</small>
                                            <b class="price">Rp <?= number_format((float) $package['harga'], 0, ',', '.') ?></b>
                                        </div>
                                        <a class="select-button" href="<?= base_url('paket/' . (int) $package['id']) ?>">Detail paket <span>→</span></a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="additional" id="tambahan">
            <div>
                <p class="eyebrow">DEKORASI TAMBAHAN</p>
                <h2>Perintilan Tambahan</h2>
                <p>Tambahkan sentuhan yang membuat dekorasi Anda semakin personal dan berkesan.</p>
            </div>
            <div class="addon-list">
                <?php if ($extras === []): ?>
                    <span>Belum ada perlengkapan tersedia.</span>
                <?php else: ?>
                    <?php foreach ($extras as $extra): ?>
                        <span><?= esc($extra['nama']) ?> · Rp <?= number_format((float) $extra['harga'], 0, ',', '.') ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer id="kontak">
        <a class="logo" href="<?= base_url('dashboard') ?>"><span>✦</span> NAULI <small>DEKOR</small></a>
        <p>Merangkai momen istimewa Anda.</p>
        <p>© <?= date('Y') ?> Nauli Dekor</p>
    </footer>

    <script>
        document.querySelector('.menu-toggle')?.addEventListener('click', () => {
            document.querySelector('.main-nav')?.classList.toggle('open');
        });
    </script>
</body>
</html>
