<?php
helper('form');
/**
 * Variabel view dipasok oleh Customer controller melalui view('customer/flow', $data).
 * Nilai default hanya mencegah diagnostic undefined-variable dan menjaga tampilan
 * tetap aman ketika halaman dibuka tanpa data; tidak membuat record/data dummy.
 *
 * @var string|null $page
 * @var array|null $cart
 * @var array|null $extras
 * @var array|null $user
 * @var array|null $orders
 * @var array|null $order
 * @var array|null $orderItems
 * @var string|null $checkoutToken
 * @var string|null $paymentScheme
 * @var string|null $paymentChannel
 * @var array|null $bookingDraft
 * @var array|null $kecamatanList
 */
$page = isset($page) && is_string($page) ? $page : 'cart';
$cart = isset($cart) && is_array($cart) ? $cart : [];
$cart = array_replace(['items' => [], 'count' => 0, 'total' => 0], $cart);
$cart['items'] = is_array($cart['items']) ? $cart['items'] : [];
$extras = isset($extras) && is_array($extras) ? $extras : [];
$user = isset($user) && is_array($user) ? $user : [];
$orders = isset($orders) && is_array($orders) ? $orders : [];
$kecamatanList = isset($kecamatanList) && is_array($kecamatanList) ? $kecamatanList : [];
$settlementPayments = isset($settlementPayments) && is_array($settlementPayments) ? $settlementPayments : [];
$latestSettlement = isset($latestSettlement) && is_array($latestSettlement) ? $latestSettlement : null;
$remainingAmount = isset($remainingAmount) ? (float) $remainingAmount : 0.0;
$order = isset($order) && is_array($order) ? $order : [];
$orderItems = isset($orderItems) && is_array($orderItems) ? $orderItems : [];
$checkoutToken = isset($checkoutToken) && is_string($checkoutToken) ? $checkoutToken : '';
$paymentScheme = isset($paymentScheme) && is_string($paymentScheme) ? $paymentScheme : (string) session()->get('payment_scheme');
$paymentChannel = isset($paymentChannel) && is_string($paymentChannel) ? $paymentChannel : (string) session()->get('payment_channel');
$bookingDraft = isset($bookingDraft) && is_array($bookingDraft) ? $bookingDraft : (is_array(session()->get('booking_draft')) ? session()->get('booking_draft') : []);
$invoiceSuccessMessage = $page === 'invoice' ? (string) session()->getFlashdata('order_created') : '';
$invoiceNoticeMessage = '';
$invoiceNoticeType = 'is-review';
if ($page === 'invoice') {
    foreach (['error' => 'is-rejected', 'info' => 'is-review', 'success' => 'is-verified'] as $flashKey => $flashClass) {
        $flashValue = (string) session()->getFlashdata($flashKey);
        if ($flashValue !== '') { $invoiceNoticeMessage = $flashValue; $invoiceNoticeType = $flashClass; break; }
    }
}
$latestSettlement = isset($latestSettlement) && is_array($latestSettlement) ? $latestSettlement : ($settlementPayments[0] ?? null);

$pageTitles = [
    'extras'  => 'Tambahan Dekorasi',
    'cart'    => 'Keranjang',
    'payment' => 'Pembayaran',
    'booking' => 'Form Booking',
    'account' => 'Akun Saya',
    'history' => 'Riwayat Pesanan',
    'settlement' => 'Pelunasan Pesanan',
    'invoice' => 'Invoice',
];
$title = $pageTitles[$page] ?? 'Nauli Dekor';
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $title ?> | Nauli Dekor</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= base_url('assets/css/customer.css') ?>"><?php if ($page === 'invoice'): ?><link rel="stylesheet" href="<?= base_url('assets/css/checkout-invoice.css') ?>"><?php endif; ?><?php if (in_array($page, ['payment', 'invoice', 'history', 'settlement'], true)): ?><link rel="stylesheet" href="<?= base_url('assets/css/payment-proof.css') ?>"><?php endif; ?>
<style>
.apriori-box {
    margin-top: 1.5rem;
    padding: 1.25rem 1.5rem;
    background: #faf7f2;
    border: 1px solid #e6dfd5;
    border-radius: 16px;
    width: fit-content;
    max-width: 100%;
}
.apriori-addon-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
}
.apriori-addon-left {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}
.apriori-addon-icon {
    color: #b88a44;
    font-size: 1rem;
    flex-shrink: 0;
}
.apriori-addon-left b {
    display: block;
    font-size: 0.95rem;
    color: #2c2520;
    margin-bottom: 0.15rem;
}
.apriori-addon-left p {
    font-size: 0.85rem;
    color: #6e6359;
    margin: 0;
}
.apriori-addon-link {
    font-size: 0.85rem;
    font-weight: 600;
    color: #8c6d46;
    text-decoration: none;
    white-space: nowrap;
}
.apriori-addon-link:hover {
    text-decoration: underline;
}
.apriori-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e6dfd5;
}
.apriori-card {
    width: 235px;
    flex: 0 0 235px;
    background: #ffffff;
    border: 1px solid #eae4dc;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.apriori-card-img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    background: #f4f0ea;
    display: block;
}
.apriori-card-body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    justify-content: space-between;
}
.apriori-badge {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    color: #8c6d46;
    background: #f3ede4;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    width: fit-content;
}
.apriori-card h4 {
    font-size: 1rem;
    color: #2c2520;
    margin: 0 0 0.35rem;
}
.apriori-desc {
    font-size: 0.8rem;
    color: #7d7268;
    margin: 0 0 0.85rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.apriori-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    border-top: 1px solid #f0ece6;
    margin-bottom: 0.75rem;
    font-size: 0.85rem;
}
.apriori-meta strong {
    color: #2c2520;
}
.apriori-meta span {
    font-size: 0.75rem;
    color: #7d7268;
}
.apriori-btn {
    width: 100%;
    padding: 0.55rem 0.75rem;
    background: #2c2520;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.apriori-btn:hover {
    background: #463b33;
}
@media (max-width: 640px) {
    .apriori-box {
        width: 100%;
    }
    .apriori-addon-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .apriori-card {
        width: 100%;
        flex: 1 1 100%;
    }
}
</style></head>
<body><?= view('partials/navbar', ['activeNav' => $page === 'extras' ? 'extras' : (in_array($page, ['cart', 'payment', 'booking'], true) ? 'cart' : ($page === 'account' || $page === 'history' ? 'account' : ''))]) ?>
<?php if ($page === 'extras'): ?>
<main>
    <section class="hero-mini">
        <p class="eyebrow">PERLENGKAPAN DEKORASI</p>
        <h1>Lengkapi momen istimewa Anda</h1>
        <p>Pilih perintilan tambahan yang paling sesuai untuk melengkapi dekorasi acara Anda.</p>
    </section>

    <section class="extras-section">
        <div class="section-top">
            <div>
                <p class="eyebrow">PILIHAN TAMBAHAN</p>
                <h2>Sentuhan yang membuat perbedaan</h2>
            </div>
            <p><?= count($extras ?? []) ?> pilihan tersedia</p>
        </div>

        <div class="extra-grid">
            <?php
            $extraVisualClasses = [
                'photobooth' => 'photo',
                'bunga salib' => 'cross',
                'bouquet' => 'bouquet',
                'kaca hias' => 'mirror',
                'gazebo' => 'gazebo',
            ];
            ?>

            <?php if (empty($extras)): ?>
                <p>Belum ada tambahan dekorasi yang tersedia.</p>
            <?php else: ?>
                <?php foreach ($extras as $index => $extra): ?>
                    <?php
                    $extraName = (string) ($extra['nama'] ?? '');
                    $visualClass = $extraVisualClasses[strtolower(trim($extraName))] ?? '';
                    $extraImageName = ! empty($extra['gambar']) ? basename((string) $extra['gambar']) : '';
                    $extraImageUrl = $extraImageName !== '' ? base_url('assets/images/' . rawurlencode($extraImageName)) : '';
                    $stock = (int) ($extra['stok'] ?? 0);
                    ?>
                    <article class="extra-card <?= esc($visualClass) ?>">
                        <div class="extra-img">
                            <?php if ($extraImageUrl !== ''): ?>
                                <img src="<?= esc($extraImageUrl, 'attr') ?>" alt="<?= esc($extraName) ?>" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                            <?php endif; ?>
                            <span style="z-index:1"><?= esc(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                        </div>
                        <div class="extra-content">
                            <p class="eyebrow">PERINTILAN TAMBAHAN</p>
                            <h3><?= esc($extraName) ?></h3>
                            <p><?= esc($extra['deskripsi'] ?? '') ?></p>
                            <div>
                                <b>Rp <?= number_format((float) ($extra['harga'] ?? 0), 0, ',', '.') ?></b>
                                <?php if ($stock > 0): ?>
                                    <form method="post" action="<?= base_url('keranjang/tambahan/' . (int) $extra['id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="add">Tambah <span>+</span></button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="add" disabled>Stok habis</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php elseif ($page === 'cart'): ?>
<main class="plain-page">
    <nav class="crumb"><a href="<?= base_url('dashboard') ?>">Beranda</a> / Keranjang</nav>
    <h1>Detail pilihan dekorasi</h1>

    <?php foreach (['success', 'error', 'warning'] as $messageType): ?>
        <?php $message = session()->getFlashdata($messageType); ?>
        <?php if ($message): ?>
            <p class="eyebrow" role="status"><?= esc($message) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="cart-layout">
                <section class="cart-list">
                    <?php if (empty($cart['items'])): ?>
                        <p>Keranjang Anda masih kosong. <a href="<?= base_url('paket-dekorasi') ?>">Lihat katalog paket</a></p>
                    <?php endif; ?>

                    <?php foreach ($cart['items'] as $item): ?>
                        <?php
                        $itemKey = (string) ($item['key'] ?? '');
                        $quantity = (int) ($item['jumlah'] ?? 1);
                        $price = (float) ($item['harga'] ?? 0);
                        $lineTotal = $price * $quantity;
                        $isExtra = ($item['type'] ?? '') === 'extra';
                        ?>
                        <article class="cart-item">
                            <div class="cart-thumb"></div>
                            <div>
                                <p class="eyebrow"><?= $isExtra ? 'TAMBAHAN DEKORASI' : 'PAKET DEKORASI' ?></p>
                                <h3><?= esc($item['nama'] ?? '') ?></h3>
                                <p class="cart-item-price-line" data-key="<?= esc($itemKey, 'attr') ?>">Rp <?= number_format($price, 0, ',', '.') ?> × <span class="cart-item-qty-display"><?= $quantity ?></span> = <span class="cart-item-line-total">Rp <?= number_format($lineTotal, 0, ',', '.') ?></span></p>

                                <?php if ($isExtra): ?>
                                    <div class="cart-qty-wrap">
                                        <form method="post" action="<?= base_url('keranjang/update/' . rawurlencode($itemKey)) ?>" class="cart-qty-form" data-key="<?= esc($itemKey, 'attr') ?>" data-price="<?= $price ?>" data-stock="<?= (int) ($item['stok'] ?? 1) ?>">
                                            <?= csrf_field() ?>
                                            <div class="cart-qty-stepper">
                                                <button type="button" class="btn-qty-step minus" aria-label="Kurangi jumlah" <?= $quantity <= 1 ? 'disabled' : '' ?>>−</button>
                                                <span class="cart-qty-display"><?= $quantity ?></span>
                                                <input
                                                    name="jumlah"
                                                    type="hidden"
                                                    class="cart-qty-input"
                                                    min="1"
                                                    max="<?= (int) ($item['stok'] ?? 1) ?>"
                                                    value="<?= $quantity ?>"
                                                >
                                                <button type="button" class="btn-qty-step plus" aria-label="Tambah jumlah" <?= $quantity >= (int) ($item['stok'] ?? 1) ? 'disabled' : '' ?>>+</button>
                                            </div>
                                        </form>
                                        <span class="cart-stock-hint">Stok: <?= (int) ($item['stok'] ?? 0) ?></span>
                                    </div>
                                <?php else: ?>
                                    <p>Jumlah paket: 1</p>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="<?= base_url('keranjang/hapus/' . rawurlencode($itemKey)) ?>">
                                <?= csrf_field() ?>
                                <button class="remove" type="submit" aria-label="Hapus <?= esc($item['nama'] ?? 'item') ?> dari keranjang">×</button>
                            </form>
                        </article>
                    <?php endforeach; ?>

                    <section class="apriori-box">
                        <div class="apriori-addon-header">
                            <div class="apriori-addon-left">
                                <span class="apriori-addon-icon">✦</span>
                                <div>
                                    <b>Tambahkan sentuhan personal</b>
                                    <p>Lengkapi pesanan Anda dengan perintilan dekorasi.</p>
                                </div>
                            </div>
                            <a href="<?= base_url('tambahan-dekorasi') ?>" class="apriori-addon-link">Pilih tambahan →</a>
                        </div>

                        <?php if (! empty($crossSellingItems)): ?>
                            <div class="apriori-grid">
                                <?php foreach ($crossSellingItems as $rec): ?>
                                    <?php
                                    $recImgName = ! empty($rec['gambar']) ? basename((string) $rec['gambar']) : '';
                                    $recImgUrl = $recImgName !== '' ? base_url('assets/images/' . rawurlencode($recImgName)) : '';
                                    ?>
                                    <article class="apriori-card">
                                        <?php if ($recImgUrl !== ''): ?>
                                            <img src="<?= esc($recImgUrl, 'attr') ?>"
                                                 alt="<?= esc($rec['nama']) ?>"
                                                 class="apriori-card-img"
                                                 loading="lazy"
                                                 onerror="this.style.display='none'">
                                        <?php endif; ?>

                                        <div class="apriori-card-body">
                                            <div>
                                                <span class="apriori-badge">
                                                    <?= number_format((float) $rec['nilai_confidence'], 0) ?>% pelanggan memilih ini
                                                </span>
                                                <h4><?= esc($rec['nama']) ?></h4>
                                                <p class="apriori-desc"><?= esc($rec['deskripsi'] ?? '') ?></p>
                                            </div>

                                            <div>
                                                <div class="apriori-meta">
                                                    <strong>Rp <?= number_format((float) $rec['harga'], 0, ',', '.') ?></strong>
                                                    <span>Stok: <?= (int) $rec['stok'] ?></span>
                                                </div>

                                                <form action="<?= base_url('keranjang/tambahan/' . (int) $rec['id']) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="apriori-btn">+ Tambahkan ke Pesanan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </section>

                <aside class="summary">
                    <p class="eyebrow">RINGKASAN PESANAN</p>
                    <div><span>Jumlah item</span><b id="cart-summary-count"><?= (int) $cart['count'] ?></b></div>
                    <hr>
                    <div class="total">
                        <span>Total sementara</span>
                        <b id="cart-summary-total">Rp <?= number_format((float) $cart['total'], 0, ',', '.') ?></b>
                    </div>
                    <?php if (! empty($cart['items'])): ?>
                        <a class="primary" href="<?= base_url('booking') ?>">Lanjutkan ke Form Booking <span>→</span></a>
                    <?php endif; ?>
                    <p class="note">Biaya pengiriman akan dihitung setelah lokasi acara diisi.</p>
                </aside>
            </div>
</main>

    <?php elseif ($page === 'payment'): ?>
<?php
    $bookingOngkos = max(0, (float) ($bookingDraft['ongkos_kirim'] ?? 0));
    $paymentTotal = (float) ($cart['total'] ?? 0) + $bookingOngkos;
    $selectedScheme = (string) old('skema_pembayaran', $paymentScheme ?: '');
    if (! in_array($selectedScheme, ['DP', 'Lunas'], true)) {
        $selectedScheme = '';
    }
    $selectedChannel = (string) old('kanal_pembayaran', $paymentChannel ?: '');
    if (! in_array($selectedChannel, ['QRIS', 'Transfer Bank'], true)) {
        $selectedChannel = '';
    }
    $selectedDue = $selectedScheme === 'DP' ? round($paymentTotal * 0.5) : ($selectedScheme === 'Lunas' ? $paymentTotal : 0);
    $selectedRemaining = $selectedScheme !== '' ? max(0, $paymentTotal - $selectedDue) : 0;
?>
<main class="plain-page">
    <nav class="crumb"><a href="<?= base_url('keranjang') ?>">Keranjang</a> / <a href="<?= base_url('booking') ?>">Form Booking</a> / Pembayaran</nav>
    <p class="eyebrow">LANGKAH 03</p>
    <h1>Pilih skema dan metode pembayaran</h1>
    <?php foreach (['success', 'error'] as $messageType): ?>
        <?php $message = session()->getFlashdata($messageType); ?>
        <?php if ($message): ?><p class="<?= $messageType === 'error' ? 'field-error' : 'eyebrow' ?>" role="status"><?= esc($message) ?></p><?php endif; ?>
    <?php endforeach; ?>
    <div class="booking-layout">
        <form class="booking-form" id="payment-form" data-total="<?= esc((string) $paymentTotal, 'attr') ?>" method="post" enctype="multipart/form-data" action="<?= base_url('pembayaran') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="checkout_token" value="<?= esc($checkoutToken, 'attr') ?>">
            <section>
                <h2><span>01</span> Skema Pembayaran</h2>
                <p>Pilih jumlah pembayaran awal untuk pesanan dekorasi Anda.</p>
                <div class="pay-options">
                    <label><input type="radio" name="skema_pembayaran" value="DP" <?= $selectedScheme === 'DP' ? 'checked' : '' ?> required> <b>Down Payment 50%</b><small>Bayar setengah dari total pesanan terlebih dahulu.</small></label>
                    <label><input type="radio" name="skema_pembayaran" value="Lunas" <?= $selectedScheme === 'Lunas' ? 'checked' : '' ?> required> <b>Bayar Lunas 100%</b><small>Bayar seluruh total pesanan sekaligus.</small></label>
                </div>
            </section>
            <section>
                <h2><span>02</span> Metode Pembayaran</h2>
                <p>Pilih kanal pembayaran yang akan digunakan.</p>
                <div class="pay-options">
                    <label><input type="radio" name="kanal_pembayaran" value="QRIS" <?= $selectedChannel === 'QRIS' ? 'checked' : '' ?> required> <b>QRIS</b><small>Pembayaran melalui aplikasi bank atau dompet digital yang mendukung QRIS. <span class="qris-view-link" style="display:inline-block; margin-top:4px; color:#bd922b; text-decoration:underline; font-weight:600; cursor:pointer;">Lihat Kode QRIS</span></small></label>
                    <label><input type="radio" name="kanal_pembayaran" value="Transfer Bank" <?= $selectedChannel === 'Transfer Bank' ? 'checked' : '' ?> required> <b>Transfer Bank</b><small>Instruksi rekening resmi diberikan oleh pihak Nauli Dekor.</small></label>
                </div>
            </section>
            <div class="payment-proof-section">
                <h3>Bukti Pembayaran</h3>
                <p>Unggah bukti transfer atau pembayaran QRIS. Jika belum mengunggah sekarang, Anda masih dapat mengunggahnya melalui halaman invoice.</p>
                <label class="proof-upload-box" for="payment-proof-file">
                    <span class="proof-upload-icon" aria-hidden="true">⇧</span>
                    <span class="proof-upload-caption" id="payment-proof-filename">Tarik file ke sini atau pilih dari perangkat</span>
                    <span class="proof-upload-button">Pilih File</span>
                    <small>JPG, JPEG, PNG, WEBP, atau PDF · Maksimal 5 MB</small>
                    <input id="payment-proof-file" type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                </label>
                <small class="proof-upload-hint">Tanpa bukti pembayaran, status tetap Belum Bayar. Setelah bukti diunggah, status menjadi Menunggu Verifikasi sampai admin memeriksanya.</small>
            </div>
            <button class="primary" type="submit">Konfirmasi Pembayaran & Buat Invoice →</button>
            <a class="back" href="<?= base_url('booking') ?>">← Kembali ke Form Booking</a>
        </form>
        <aside class="summary sticky" id="payment-summary" data-total="<?= esc((string) $paymentTotal, 'attr') ?>">
            <p class="eyebrow">RINGKASAN PESANAN</p>
            <?php foreach ($cart['items'] as $item): ?>
                <div><span><?= esc($item['nama']) ?> × <?= (int) $item['jumlah'] ?></span><b>Rp <?= number_format((float) $item['harga'] * (int) $item['jumlah'], 0, ',', '.') ?></b></div>
            <?php endforeach; ?>
            <?php if ($bookingOngkos > 0): ?>
                <div><span>Ongkos Kirim (<?= esc($bookingDraft['nama_kelurahan'] ?? '') ?>)</span><b>Rp <?= number_format($bookingOngkos, 0, ',', '.') ?></b></div>
            <?php endif; ?>
            <hr>
            <div><span>Total pesanan</span><b>Rp <?= number_format($paymentTotal, 0, ',', '.') ?></b></div>
            <div><span id="payment-due-label"><?= $selectedScheme === 'DP' ? 'DP 50% yang dibayar' : ($selectedScheme === 'Lunas' ? 'Pembayaran Lunas 100%' : 'Jumlah yang perlu dibayar') ?></span><b id="payment-due-value"><?= $selectedScheme !== '' ? 'Rp ' . number_format($selectedDue, 0, ',', '.') : '—' ?></b></div>
            <div><span>Sisa tagihan setelah pembayaran</span><b id="payment-remaining-value"><?= $selectedScheme !== '' ? 'Rp ' . number_format($selectedRemaining, 0, ',', '.') : '—' ?></b></div>
            <div><span>Metode pembayaran</span><b id="payment-channel-value"><?= $selectedChannel !== '' ? esc($selectedChannel) : 'Belum dipilih' ?></b></div>
            <small id="payment-summary-note"><?= $selectedScheme === 'DP' ? 'Pembayaran awal sebesar 50%. Sisa 50% dibayarkan sesuai kesepakatan dengan Nauli Dekor.' : ($selectedScheme === 'Lunas' ? 'Pembayaran sebesar 100% dari total pesanan.' : 'Silakan pilih skema dan metode pembayaran terlebih dahulu.') ?></small>
        </aside>
    </div>
    <div class="qris-popup" id="qris-payment-modal" aria-hidden="true">
        <button type="button" class="qris-popup-backdrop" data-close-qris aria-label="Tutup QRIS"></button>
        <section class="qris-popup-card" role="dialog" aria-modal="true" aria-labelledby="qris-popup-title">
            <button type="button" class="qris-popup-close" data-close-qris aria-label="Tutup">×</button>
            <p class="eyebrow">PEMBAYARAN QRIS</p>
            <h2 id="qris-popup-title">Scan QRIS Nauli Dekor</h2>
            <p class="qris-popup-description">Buka aplikasi mobile banking atau dompet digital, lalu pindai kode QR di bawah ini.</p>
            <div class="qris-popup-image-wrap">
                <img src="<?= esc(base_url('assets/images/Qris.jpeg'), 'attr') ?>" alt="Kode QRIS resmi Nauli Dekor">
            </div>
            <p class="qris-popup-note">Pastikan nama merchant dan nominal pembayaran sesuai sebelum menyelesaikan transaksi. Simpan bukti pembayaran untuk diunggah pada formulir ini.</p>
            <button type="button" class="qris-popup-done" data-close-qris>Sudah, Kembali ke Pembayaran</button>
        </section>
    </div>
</main>
<?php elseif ($page === 'booking'): ?>
<main class="plain-page">
    <nav class="crumb"><a href="<?= base_url('keranjang') ?>">Keranjang</a> / Form Booking</nav>
    <p class="eyebrow">LANGKAH 02</p>
    <h1>Lengkapi detail pesanan</h1>
    <?php foreach (['success', 'error'] as $messageType): ?>
        <?php $message = session()->getFlashdata($messageType); ?>
        <?php if ($message): ?><p class="<?= $messageType === 'error' ? 'field-error' : 'eyebrow' ?>" role="status"><?= esc($message) ?></p><?php endif; ?>
    <?php endforeach; ?>
    <?php if (session('errors')): ?>
        <div class="field-error" role="alert">
            <?php foreach ((array) session('errors') as $err): ?>
                <div><?= esc($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="booking-layout">
        <form class="booking-form" id="booking-form" method="post" action="<?= base_url('booking') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="checkout_token" value="<?= esc($checkoutToken, 'attr') ?>">
            <section>
                <h2><span>01</span> Informasi Pemesan</h2>
                <p>Pastikan informasi yang Anda masukkan sudah benar.</p>
                <div class="fields two">
                    <label>Nama pemesan<input name="nama_pemesan" value="<?= esc(old('nama_pemesan', $bookingDraft['nama_pemesan'] ?? session('user_name') ?? session('admin_name') ?? '')) ?>" required minlength="3" maxlength="150"></label>
                    <label>Nomor WhatsApp<input name="no_whatsapp" value="<?= esc(old('no_whatsapp', $bookingDraft['no_whatsapp'] ?? session('user_phone') ?? session('admin_phone') ?? '')) ?>" required minlength="8" maxlength="20"></label>
                </div>
            </section>
            <section>
                <h2><span>02</span> Detail Acara</h2>
                <?php
                $draftTanggal = old('tanggal_acara', $bookingDraft['tanggal_acara'] ?? '');
                $dateLockedFromDetail = (! empty($bookingDraft['tanggal_acara']) && empty(old('tanggal_acara'))) || (! empty($draftTanggal));
                ?>
                <?php if ($draftTanggal !== ''): ?>
                    <!-- Tanggal sudah dipilih dari halaman detail paket -->
                    <div class="date-locked-badge" id="date-locked-badge">
                        <span class="date-lock-icon">📅</span>
                        <div>
                            <b>Tanggal acara:</b>
                            <span id="date-locked-display"><?= date('d F Y', strtotime($draftTanggal)) ?></span>
                        </div>
                        <button type="button" class="date-change-btn" id="date-change-btn">Ubah Tanggal</button>
                    </div>
                    <input type="hidden" name="tanggal_acara" id="tanggal-acara-hidden" value="<?= esc($draftTanggal, 'attr') ?>">
                <?php else: ?>
                    <!-- Belum ada tanggal, tampilkan tombol pilih tanggal -->
                    <div class="date-locked-badge date-locked-empty" id="date-locked-badge" style="border-color:#ddd3c4; background:#fdfaf6;">

                        <div>
                            <b>Tanggal acara:</b>
                            <span id="date-locked-display" style="color:#9e8e7e; font-weight:400;">Belum dipilih</span>
                        </div>
                        <button type="button" class="date-change-btn" id="date-change-btn">Pilih Tanggal</button>

                    </div>
                    <input type="hidden" name="tanggal_acara" id="tanggal-acara-hidden" value="" required>
                    <small id="date-conflict-msg" class="field-error" style="display:none;"></small>
                <?php endif; ?>
                <div class="fields two">
                    <label>Kecamatan
                        <select name="kecamatan_id" id="booking-kecamatan" required>
                            <option value="">— Pilih Kecamatan —</option>
                            <?php foreach ($kecamatanList as $kec): ?>
                                <option value="<?= (int) $kec['id'] ?>" <?= (string) old('kecamatan_id', $bookingDraft['kecamatan_id'] ?? '') === (string) $kec['id'] ? 'selected' : '' ?>>
                                    <?= esc($kec['nama_kecamatan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Kelurahan (Aturan Ongkos Kirim)
                        <select name="kelurahan_id" id="booking-kelurahan" required data-selected="<?= esc(old('kelurahan_id', $bookingDraft['kelurahan_id'] ?? '')) ?>">
                            <option value="">— Pilih Kecamatan terlebih dahulu —</option>
                            <?php if (! empty($bookingDraft['kelurahan_id'])): ?>
                                <option value="<?= (int) $bookingDraft['kelurahan_id'] ?>" selected data-ongkos="<?= (float) ($bookingDraft['ongkos_kirim'] ?? 0) ?>">
                                    <?= esc($bookingDraft['nama_kelurahan'] ?? '') ?> (Rp <?= number_format((float) ($bookingDraft['ongkos_kirim'] ?? 0), 0, ',', '.') ?>)
                                </option>
                            <?php endif; ?>
                        </select>
                    </label>
                </div>
                <label>Lokasi acara<textarea name="lokasi_acara" required maxlength="5000" placeholder="Alamat lengkap acara, nama gedung / gereja / rumah"><?= esc(old('lokasi_acara', $bookingDraft['lokasi_acara'] ?? '')) ?></textarea></label>
            </section>
            <button class="primary" type="submit">Lanjutkan ke Pembayaran →</button>
            <a class="back" href="<?= base_url('keranjang') ?>">← Kembali ke Keranjang</a>
        </form>
        <?php $initialOngkos = (float) ($bookingDraft['ongkos_kirim'] ?? 0); ?>
        <aside class="summary sticky" id="booking-summary" data-cart-total="<?= (float) $cart['total'] ?>">
            <p class="eyebrow">RINGKASAN PESANAN</p>
            <?php foreach ($cart['items'] as $item): ?>
                <div><span><?= esc($item['nama']) ?> × <?= (int) $item['jumlah'] ?></span><b>Rp <?= number_format((float) $item['harga'] * (int) $item['jumlah'], 0, ',', '.') ?></b></div>
            <?php endforeach; ?>
            <hr>
            <div><span>Jumlah item</span><b><?= (int) $cart['count'] ?></b></div>
            <div id="booking-ongkos-row" <?= $initialOngkos > 0 ? 'style="display:flex;"' : 'style="display:none;"' ?>>
                <span id="booking-ongkos-label">Ongkos Kirim<?= ! empty($bookingDraft['nama_kelurahan']) ? ' (' . esc($bookingDraft['nama_kelurahan']) . ')' : '' ?></span>
                <b id="booking-ongkos-val">Rp <?= number_format($initialOngkos, 0, ',', '.') ?></b>
            </div>
            <div><span>Total pesanan</span><b id="booking-total-val">Rp <?= number_format((float) $cart['total'] + $initialOngkos, 0, ',', '.') ?></b></div>
            <small>Skema dan metode pembayaran akan dipilih pada langkah berikutnya.</small>
        </aside>
    </div>

    <!-- Calendar Overlay Popup for Booking -->
    <div class="cal-overlay" id="booking-cal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="booking-cal-title">
        <div class="cal-overlay-backdrop" id="booking-cal-backdrop"></div>
        <div class="cal-overlay-card">
            <div class="cal-overlay-header">
                <div>
                    <p class="eyebrow" style="margin:0 0 2px;">JADWAL PEMESANAN</p>
                    <h3 class="cal-overlay-title" id="booking-cal-title">Pilih Tanggal Acara</h3>
                </div>
                <button type="button" class="cal-overlay-close" id="booking-cal-close" aria-label="Tutup">×</button>
            </div>
            <?php if (isset($mainPackageName) && $mainPackageName !== ''): ?>
                <p class="cal-overlay-pkg-name">Paket: <strong><?= esc($mainPackageName) ?></strong></p>
            <?php endif; ?>
            <div class="cal-overlay-loading" id="booking-cal-loading" style="display:none;">
                <span class="cal-spinner"></span> Memuat data ketersediaan…
            </div>
            <div class="cal-nav">
                <button type="button" class="cal-nav-btn" id="booking-cal-prev">‹</button>
                <span class="cal-month-title" id="booking-cal-month">—</span>
                <button type="button" class="cal-nav-btn" id="booking-cal-next">›</button>
            </div>
            <div class="cal-weekdays">
                <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
            </div>
            <div class="cal-days-grid" id="booking-cal-grid"></div>
            <div class="cal-legend">
                <span class="cal-legend-item"><span class="cal-dot available"></span>Tersedia</span>
                <span class="cal-legend-item"><span class="cal-dot booked"></span>Sudah dipesan</span>
                <span class="cal-legend-item"><span class="cal-dot today"></span>Hari ini</span>
                <span class="cal-legend-item"><span class="cal-dot selected"></span>Dipilih</span>
            </div>
            <div class="cal-overlay-footer" id="booking-cal-footer" style="display:none;">
                <div class="cal-overlay-sel-info">
                    <span>Tanggal dipilih:</span>
                    <strong id="booking-cal-selected-display">—</strong>
                </div>
                <button type="button" class="cal-overlay-confirm" id="booking-cal-confirm">Gunakan Tanggal Ini →</button>
            </div>
        </div>
    </div>
<?php if (! empty($crossSellingItems)): ?>
    <section class="apriori-box">
        <div class="apriori-addon-header">
            <div class="apriori-addon-left">
                <span class="apriori-addon-icon">✦</span>
                <div>
                    <b>Tambahkan sentuhan personal</b>
                    <p>Lengkapi pesanan Anda dengan perintilan dekorasi.</p>
                </div>
            </div>
            <a href="<?= base_url('tambahan-dekorasi') ?>" class="apriori-addon-link">Pilih tambahan →</a>
        </div>

        <div class="apriori-grid">
            <?php foreach ($crossSellingItems as $rec): ?>
                <?php
                $recImgName = ! empty($rec['gambar']) ? basename((string) $rec['gambar']) : '';
                $recImgUrl = $recImgName !== '' ? base_url('assets/images/' . rawurlencode($recImgName)) : '';
                ?>
                <article class="apriori-card">
                    <?php if ($recImgUrl !== ''): ?>
                        <img src="<?= esc($recImgUrl, 'attr') ?>"
                             alt="<?= esc($rec['nama']) ?>"
                             class="apriori-card-img"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    <?php endif; ?>

                    <div class="apriori-card-body">
                        <div>
                            <span class="apriori-badge">
                                <?= number_format((float) $rec['nilai_confidence'], 0) ?>% pelanggan memilih ini
                            </span>
                            <h4><?= esc($rec['nama']) ?></h4>
                            <p class="apriori-desc"><?= esc($rec['deskripsi'] ?? '') ?></p>
                        </div>

                        <div>
                            <div class="apriori-meta">
                                <strong>Rp <?= number_format((float) $rec['harga'], 0, ',', '.') ?></strong>
                                <span>Stok: <?= (int) $rec['stok'] ?></span>
                            </div>

                            <form action="<?= base_url('keranjang/tambahan/' . (int) $rec['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="apriori-btn">+ Tambahkan ke Pesanan</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
</main>
    <?php elseif ($page === 'account' || $page === 'history'): ?>
<main class="account-page">
    <nav class="crumb"><a href="<?= base_url('dashboard') ?>">Beranda</a> / <?= $page === 'history' ? 'Riwayat Pesanan' : 'Akun Saya' ?></nav>
    <h1>Selamat datang, <?= esc($user['nama_lengkap'] ?? '') ?></h1>

    <?php foreach (['success', 'error', 'warning'] as $msgType): ?>
        <?php $msg = session()->getFlashdata($msgType); ?>
        <?php if ($msg): ?>
            <p class="<?= $msgType === 'error' ? 'field-error' : 'eyebrow' ?>" role="status" style="margin: 12px 0 20px; font-size: 14px;"><?= esc($msg) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (session('errors')): ?>
        <p class="field-error" style="margin: 12px 0 20px; font-size: 14px;"><?= esc(implode(' ', session('errors'))) ?></p>
    <?php endif; ?>

    <div class="account-layout">
        <aside class="account-nav">
            <div class="account-profile-header">
                <div class="avatar"><?= esc(strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1))) ?></div>
                <div class="account-user-meta">
                    <b><?= esc($user['nama_lengkap'] ?? '') ?></b>
                    <small><?= esc($user['no_whatsapp'] ?? '') ?></small>
                </div>
            </div>

            <div class="account-nav-menu">
                <a class="account-nav-btn <?= $page === 'account' ? 'active' : '' ?>" href="<?= base_url('akun') ?>">
                    <span class="nav-btn-text">Profil Saya</span>
                </a>
                <a class="account-nav-btn <?= $page === 'history' ? 'active' : '' ?>" href="<?= base_url('riwayat-pesanan') ?>">
                    <span class="nav-btn-text">Riwayat Pesanan</span>
                </a>
            </div>

            <div class="account-nav-footer">
                <form method="post" action="<?= base_url('logout') ?>" style="margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="account-logout-btn">
                        <span>Keluar dari Akun</span>
                    </button>
                </form>
            </div>
        </aside>

        <?php if ($page === 'account'): ?>
            <div class="profile-content-wrap">
                <form class="profile-form" method="post" action="<?= base_url('akun') ?>">
                    <?= csrf_field() ?>
                    <p class="eyebrow">PROFIL SAYA</p>
                    <h2>Informasi Pribadi</h2>

                    <label>Nama lengkap
                        <input name="nama_lengkap" value="<?= esc(old('nama_lengkap') ?: ($user['nama_lengkap'] ?? '')) ?>" required minlength="3">
                    </label>

                    <label>Nomor WhatsApp
                        <input name="no_whatsapp" value="<?= esc(old('no_whatsapp') ?: ($user['no_whatsapp'] ?? '')) ?>" required minlength="8">
                    </label>

                    <div class="profile-action-group">
                        <button class="primary" type="submit">Simpan Perubahan</button>
                        <button type="button" class="btn-open-password-modal" id="btn-open-password-modal">
                            Ganti Password
                        </button>
                    </div>
                </form>
            </div>

            <div class="password-overlay-modal" id="change-password-modal" aria-hidden="true" <?= session('password_modal_open') ? 'style="display:flex;"' : 'style="display:none;"' ?>>
                <div class="password-modal-backdrop" id="close-pass-backdrop"></div>
                <div class="password-modal-card">
                    <button type="button" class="password-modal-close" id="close-pass-btn" aria-label="Tutup">×</button>
                    <p class="eyebrow">KEAMANAN AKUN</p>
                    <h2>Ganti Password</h2>
                    <p class="password-modal-desc">Masukkan password Anda saat ini dan tentukan password baru yang aman.</p>

                    <form method="post" action="<?= base_url('akun/ganti-password') ?>" class="change-password-form">
                        <?= csrf_field() ?>
                        <label>
                            <span>Password Saat Ini</span>
                            <div class="pass-field-wrap">
                                <input type="password" name="password_saat_ini" id="input-curr-pass" required placeholder="Masukkan password saat ini" autocomplete="current-password">
                                <button type="button" class="btn-toggle-eye" data-target="input-curr-pass" title="Tampilkan/Sembunyikan password">👁</button>
                            </div>
                        </label>

                        <label>
                            <span>Password Baru</span>
                            <div class="pass-field-wrap">
                                <input type="password" name="password_baru" id="input-new-pass" required minlength="8" placeholder="Minimal 8 karakter" autocomplete="new-password">
                                <button type="button" class="btn-toggle-eye" data-target="input-new-pass" title="Tampilkan/Sembunyikan password">👁</button>
                            </div>
                        </label>

                        <label>
                            <span>Konfirmasi Password Baru</span>
                            <div class="pass-field-wrap">
                                <input type="password" name="konfirmasi_password" id="input-conf-pass" required minlength="8" placeholder="Ulangi password baru" autocomplete="new-password">
                                <button type="button" class="btn-toggle-eye" data-target="input-conf-pass" title="Tampilkan/Sembunyikan password">👁</button>
                            </div>
                        </label>

                        <div class="password-modal-actions">
                            <button type="submit" class="primary">Simpan Password Baru</button>
                            <button type="button" class="btn-cancel" id="btn-cancel-pass">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <section class="history">
                <div class="history-heading">
                    <div>
                        <p class="eyebrow">PESANAN SAYA</p>
                        <h2>Riwayat Pesanan</h2>
                    </div>
                </div>
                <?php if (! $orders): ?>
                    <p>Belum ada pesanan.</p>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $historyStatus = (string) ($order['status_pesanan'] ?? 'Pending');
                    $historyStatusClass = strtolower($historyStatus) === 'selesai' ? 'status done' : 'status';
                    $historyEventDate = ! empty($order['tanggal_acara']) ? date('d/m/Y', strtotime($order['tanggal_acara'])) : '-';
                    $latestSettlement = is_array($order['latest_settlement'] ?? null) ? $order['latest_settlement'] : null;
                    $settlementStatus = (string) ($latestSettlement['status'] ?? '');
                    $canSettle = ($order['status_pembayaran'] ?? '') === 'DP' && ($order['status_pesanan'] ?? '') === 'Diproses' && (float) ($order['remaining_amount'] ?? 0) > 0 && ! in_array($settlementStatus, ['Menunggu Verifikasi', 'Menunggu Pembayaran di Tempat'], true);
                    ?>
                    <article class="order">
                        <div class="order-img"></div>
                        <div>
                            <small><?= esc($order['kode_pesanan'] ?? '-') ?> · Tanggal acara <?= esc($historyEventDate) ?></small>
                            <h3><?= esc($order['display_package_name'] ?? 'Pesanan dekorasi') ?></h3>
                            <p><span class="<?= esc($historyStatusClass) ?>"><?= esc($historyStatus) ?></span> · <?= esc($order['status_pembayaran'] ?? 'Belum Bayar') ?> · Rp <?= number_format((float) ($order['total'] ?? 0), 0, ',', '.') ?></p>
                            <small>
                                <?php if ((int) ($order['extra_quantity'] ?? 0) > 0): ?>
                                    <?= (int) $order['extra_type_count'] ?> jenis tambahan · <?= (int) $order['extra_quantity'] ?> item tambahan
                                <?php else: ?>
                                    Tanpa tambahan dekorasi
                                <?php endif; ?>
                            </small>
                            <?php if (($order['status_pembayaran'] ?? '') === 'DP'): ?>
                                <small class="history-remaining">Sisa pelunasan: <b>Rp <?= number_format((float) ($order['remaining_amount'] ?? 0), 0, ',', '.') ?></b></small>
                            <?php endif; ?>
                            <?php if ($settlementStatus === 'Menunggu Verifikasi'): ?>
                                <small class="history-settlement-pending">Bukti pelunasan sedang diperiksa admin.</small>
                            <?php elseif ($settlementStatus === 'Menunggu Pembayaran di Tempat'): ?>
                                <small class="history-settlement-pending">Menunggu pembayaran di tempat acara.</small>
                            <?php elseif ($settlementStatus === 'Ditolak'): ?>
                                <small class="history-settlement-rejected">Bukti pelunasan ditolak: <?= esc($latestSettlement['catatan'] ?? 'Silakan ajukan ulang.') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="history-order-actions">
                            <a href="<?= base_url('invoice?id=' . (int) $order['id']) ?>">Detail →</a>
                            <?php if ($canSettle): ?>
                                <a class="settlement-button" href="<?= base_url('riwayat-pesanan/' . (int) $order['id'] . '/pelunasan') ?>">Melunasi</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
<?php elseif ($page === 'settlement'): ?>
<?php
    $settlementOrder = is_array($order ?? null) ? $order : [];
    $settlementRemaining = max(0, (float) ($remainingAmount ?? 0));
    $settlementMethod = (string) old('metode_pelunasan', 'QRIS');
    if (! in_array($settlementMethod, ['QRIS', 'Transfer Bank', 'Bayar di Tempat Acara'], true)) $settlementMethod = 'QRIS';
?>
<main class="plain-page settlement-page">
    <nav class="crumb"><a href="<?= base_url('riwayat-pesanan') ?>">Riwayat Pesanan</a> / Pelunasan</nav>
    <p class="eyebrow">PELUNASAN PESANAN</p>
    <h1>Selesaikan sisa pembayaran</h1>
    <?php foreach (['success', 'error'] as $messageType): ?><?php $message = session()->getFlashdata($messageType); ?><?php if ($message): ?><p class="<?= $messageType === 'error' ? 'field-error' : 'eyebrow' ?>" role="status"><?= esc($message) ?></p><?php endif; ?><?php endforeach; ?>
    <div class="booking-layout settlement-layout">
        <form class="booking-form settlement-form" method="post" enctype="multipart/form-data" action="<?= base_url('riwayat-pesanan/' . (int) ($settlementOrder['id'] ?? 0) . '/pelunasan') ?>">
            <?= csrf_field() ?>
            <section>
                <h2><span>01</span> Pilih metode pelunasan</h2>
                <p>Pesanan <?= esc($settlementOrder['kode_pesanan'] ?? '-') ?> · <?= esc($settlementOrder['display_package_name'] ?? $settlementOrder['package_name'] ?? 'Dekorasi Nauli') ?></p>
                <div class="pay-options settlement-options">
                    <label><input type="radio" name="metode_pelunasan" value="QRIS" <?= $settlementMethod === 'QRIS' ? 'checked' : '' ?> required> <b>QRIS</b><small>Bayar sisa tagihan menggunakan QRIS.</small></label>
                    <label><input type="radio" name="metode_pelunasan" value="Transfer Bank" <?= $settlementMethod === 'Transfer Bank' ? 'checked' : '' ?> required> <b>Transfer Bank</b><small>Transfer ke rekening resmi Nauli Dekor.</small></label>
                    <label><input type="radio" name="metode_pelunasan" value="Bayar di Tempat Acara" <?= $settlementMethod === 'Bayar di Tempat Acara' ? 'checked' : '' ?> required> <b>Bayar di Tempat Acara</b><small>Pelunasan dilakukan langsung kepada petugas Nauli Dekor saat acara.</small></label>
                </div>
            </section>
            <section id="settlement-qris-panel" class="settlement-qris-panel" <?= $settlementMethod !== 'QRIS' ? 'hidden' : '' ?>>
                <h2><span>02</span> QRIS Nauli Dekor</h2>
                <p>Pindai kode QR menggunakan aplikasi pembayaran Anda, lalu unggah bukti transaksi.</p>
                <img src="<?= base_url('assets/images/Qris.jpeg') ?>" alt="QRIS Nauli Dekor" class="settlement-qris-image">
            </section>
            <section id="settlement-proof-panel" class="invoice-proof-upload settlement-proof-panel" <?= $settlementMethod === 'Bayar di Tempat Acara' ? 'hidden' : '' ?>>
                <h2><span>03</span> Bukti Pelunasan</h2>
                <p>Wajib diunggah untuk metode QRIS dan Transfer Bank. Admin akan memeriksa bukti sebelum status berubah menjadi Lunas.</p>
                <label class="proof-upload-box" for="settlement-proof-file">
                    <span class="proof-upload-icon" aria-hidden="true">⇧</span>
                    <span class="proof-upload-caption" id="settlement-proof-filename">Pilih bukti pelunasan dari perangkat</span>
                    <span class="proof-upload-button">Pilih File</span>
                    <small>JPG, JPEG, PNG, WEBP, atau PDF · Maksimal 5 MB</small>
                    <input id="settlement-proof-file" type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                </label>
            </section>
            <section id="settlement-onsite-panel" <?= $settlementMethod !== 'Bayar di Tempat Acara' ? 'hidden' : '' ?>>
                <h2><span>04</span> Pembayaran di lokasi acara</h2>
                <p>Pengajuan ini akan dicatat oleh admin. Setelah pembayaran benar-benar diterima di lokasi, admin akan mengisi catatan dan menyelesaikan pesanan. Status tidak berubah menjadi Lunas sebelum dikonfirmasi admin.</p>
            </section>
            <button class="primary" type="submit">Kirim Pengajuan Pelunasan →</button>
            <a class="back" href="<?= base_url('riwayat-pesanan') ?>">← Kembali ke Riwayat Pesanan</a>
        </form>
        <aside class="summary sticky">
            <p class="eyebrow">RINGKASAN PELUNASAN</p>
            <div><span>Kode pesanan</span><b><?= esc($settlementOrder['kode_pesanan'] ?? '-') ?></b></div>
            <div><span>Total pesanan</span><b>Rp <?= number_format((float) ($settlementOrder['total'] ?? 0), 0, ',', '.') ?></b></div>
            <div><span>Sudah dibayar (DP)</span><b>Rp <?= number_format((float) ($settlementOrder['jumlah_dp'] ?? 0), 0, ',', '.') ?></b></div>
            <hr>
            <div class="total"><span>Sisa pelunasan</span><b>Rp <?= number_format($settlementRemaining, 0, ',', '.') ?></b></div>
            <small>Pelunasan akan dicatat setelah bukti diverifikasi admin atau pembayaran di tempat dikonfirmasi.</small>
        </aside>
    </div>
</main>
<?php elseif ($page === 'invoice'): ?>
<?php
    $createdAtRaw = trim((string) ($order['created_at'] ?? ''));
    $invoiceCreatedAt = $createdAtRaw !== '' && strtotime($createdAtRaw) !== false
        ? date('d/m/Y H:i', strtotime($createdAtRaw))
        : '-';

    // Format tanggal acara secara eksplisit dalam Bahasa Indonesia.
    // Kolom waktu_acara tidak diminta pada form booking, jadi tidak ditampilkan
    // sebagai "Waktu belum ditentukan" pada invoice.
    $eventDateRaw = substr(trim((string) ($order['tanggal_acara'] ?? '')), 0, 10);
    $eventDate = 'Tanggal acara belum tercatat';
    if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $eventDateRaw) === 1) {
        $eventDateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $eventDateRaw);
        if ($eventDateObject !== false && $eventDateObject->format('Y-m-d') === $eventDateRaw) {
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $eventDate = $eventDateObject->format('d') . ' '
                . $monthNames[(int) $eventDateObject->format('n')] . ' '
                . $eventDateObject->format('Y');
        }
    }

    $eventLocation = trim((string) ($order['lokasi_acara'] ?? ''));
    if ($eventLocation === '') {
        $eventLocation = 'Lokasi acara belum tercatat';
    }

    $orderTotal = (float) ($order['total'] ?? 0);
    $initialPayment = min($orderTotal, max(0, (float) ($order['jumlah_dp'] ?? 0)));
    $paymentStatus = (string) ($order['status_pembayaran'] ?? 'Belum Bayar');
    $paymentScheme = (string) ($order['metode_pembayaran'] ?? '');
    $paymentChannelLabel = (string) ($order['kanal_pembayaran'] ?? '-');
    $paymentSchemeLabel = $paymentScheme === 'DP'
        ? 'DP 50%'
        : ($paymentScheme === 'Lunas' ? 'Lunas 100%' : ($paymentScheme !== '' ? $paymentScheme : '-'));

    // Hanya pembayaran yang sudah diverifikasi admin dihitung sebagai uang diterima.
    if ($paymentStatus === 'Lunas') {
        $paidAmount = $orderTotal;
    } elseif ($paymentStatus === 'DP') {
        $paidAmount = $initialPayment;
    } else {
        $paidAmount = 0.0;
    }

    $remainingPayment = max(0, $orderTotal - $paidAmount);
    $selectedPaymentAmount = $paymentScheme === 'DP' ? $initialPayment : $orderTotal;
    $awaitingProof = in_array($paymentStatus, ['Belum Bayar', 'Menunggu Verifikasi', 'Ditolak'], true);
    $paymentDue = $awaitingProof ? $selectedPaymentAmount : $remainingPayment;
    $paymentDueLabel = match ($paymentStatus) {
        'Menunggu Verifikasi' => 'Nominal menunggu verifikasi',
        'Ditolak' => 'Tagihan yang perlu dibayar ulang',
        'Belum Bayar' => ($paymentScheme === 'DP' ? 'DP yang perlu dibayar' : 'Tagihan yang perlu dibayar'),
        default => 'Sisa tagihan',
    };
?>
<main class="invoice-wrap">
    <a class="back" href="<?= base_url('riwayat-pesanan') ?>">← Kembali ke riwayat pesanan</a>
    <?php if ($invoiceNoticeMessage !== ''): ?><div class="payment-proof-notice <?= esc($invoiceNoticeType) ?> no-print" role="status"><?= esc($invoiceNoticeMessage) ?></div><?php endif; ?>
    <article class="invoice" id="invoice-document">
        <header>
            <a class="logo" href="<?= base_url('dashboard') ?>"><span>✦</span> NAULI <small>DEKOR</small></a>
            <button type="button" onclick="window.print()">↓ Simpan / Cetak</button>
        </header>
        <div class="invoice-heading invoice-heading-reference">
            <div class="invoice-billed-to">
                <small>DITAGIHKAN KEPADA</small>
                <h2><?= esc($order['nama_pemesan'] ?? '-') ?></h2>
                <p>
                    <?= esc($order['no_whatsapp'] ?? '-') ?><br>
                    <?= esc($order['email'] ?? '-') ?>
                </p>
            </div>
            <div class="invoice-meta-block">
                <div class="invoice-issued-row">
                    <span>Tanggal terbit</span>
                    <b><?= esc($invoiceCreatedAt) ?></b>
                </div>
                <div class="invoice-issued-row">
                    <span>Status pembayaran</span>
                    <b class="invoice-status-badge <?= $paymentStatus === 'Lunas' ? 'is-paid' : ($paymentStatus === 'DP' ? 'is-partial' : (in_array($paymentStatus, ['Menunggu Verifikasi'], true) ? 'is-review' : ($paymentStatus === 'Ditolak' ? 'is-rejected' : 'is-pending'))) ?>"><?= esc($paymentStatus) ?></b>
                </div>
                <p class="invoice-order-status">Status pesanan: <strong><?= esc($order['status_pesanan'] ?? '-') ?></strong></p>
            </div>
        </div>
        <div class="invoice-number-row">
            <div>
                <small>INVOICE</small>
                <h1>#<?= esc($order['kode_pesanan'] ?? '-') ?></h1>
            </div>
            <div class="invoice-payment-meta">
                <span>Skema pembayaran <b><?= esc($paymentSchemeLabel) ?></b></span>
                <span>Metode pembayaran <b><?= esc($paymentChannelLabel) ?></b></span>
            </div>
        </div>
        <div class="event-line event-line-reference">
            <div class="event-detail-cell">
                <span class="event-detail-icon" aria-hidden="true">▦</span>
                <span><small>TANGGAL ACARA</small><strong><?= esc($eventDate) ?></strong></span>
            </div>
            <div class="event-detail-cell">
                <span class="event-detail-icon" aria-hidden="true">⌖</span>
                <span><small>LOKASI ACARA</small><strong><?= nl2br(esc($eventLocation)) ?></strong></span>
            </div>
        </div>
        <?php if ($paymentStatus === 'Menunggu Verifikasi'): ?>
            <div class="payment-proof-notice is-review" role="status">
                <strong>Bukti pembayaran sudah diterima.</strong>
                <span>Admin Nauli Dekor sedang memeriksa bukti pembayaran Anda. Status pesanan akan berubah menjadi Diproses setelah bukti disetujui.</span>
                <?php if (! empty($order['bukti_diupload_pada'])): ?><small>Diunggah: <?= esc(date('d/m/Y H:i', strtotime($order['bukti_diupload_pada']))) ?></small><?php endif; ?>
            </div>
        <?php elseif ($paymentStatus === 'Ditolak'): ?>
            <div class="payment-proof-notice is-rejected" role="alert">
                <strong>Pembayaran tidak berhasil.</strong>
                <span><?= esc($order['catatan_verifikasi'] ?? 'Admin belum dapat memverifikasi bukti pembayaran. Silakan unggah ulang bukti yang lebih jelas.') ?></span>
            </div>
        <?php elseif ($paymentStatus === 'Belum Bayar'): ?>
            <div class="payment-proof-notice is-pending">
                <strong>Belum ada bukti pembayaran.</strong>
                <span>Pesanan masih berstatus Belum Bayar. Unggah bukti pembayaran agar admin dapat melakukan verifikasi.</span>
            </div>
        <?php elseif (in_array($paymentStatus, ['DP', 'Lunas'], true)): ?>
            <div class="payment-proof-notice is-verified">
                <strong>Pembayaran telah diverifikasi admin.</strong>
                <span>Pesanan Anda sudah masuk ke tahap Diproses. <?= $paymentStatus === 'DP' ? 'Sisa pembayaran akan mengikuti kesepakatan dengan Nauli Dekor.' : 'Pembayaran pesanan sudah lunas.' ?></span>
            </div>
        <?php endif; ?>
        <?php if (is_array($latestSettlement)): ?>
            <?php if (($latestSettlement['status'] ?? '') === 'Menunggu Verifikasi'): ?>
                <div class="payment-proof-notice is-review" role="status"><strong>Bukti pelunasan sudah diterima.</strong><span>Admin sedang memeriksa bukti pelunasan sebesar Rp <?= number_format((float) ($latestSettlement['jumlah'] ?? 0), 0, ',', '.') ?>. Pesanan belum dinyatakan Lunas sampai verifikasi selesai.</span></div>
            <?php elseif (($latestSettlement['status'] ?? '') === 'Menunggu Pembayaran di Tempat'): ?>
                <div class="payment-proof-notice is-review" role="status"><strong>Pelunasan di tempat telah diajukan.</strong><span>Admin akan mencatat penerimaan pembayaran saat acara. Pesanan baru ditandai Selesai setelah pembayaran di tempat dikonfirmasi.</span></div>
            <?php elseif (($latestSettlement['status'] ?? '') === 'Ditolak'): ?>
                <div class="payment-proof-notice is-rejected" role="alert"><strong>Bukti pelunasan ditolak.</strong><span><?= esc($latestSettlement['catatan'] ?? 'Silakan ajukan ulang dengan bukti yang sesuai.') ?></span></div>
            <?php elseif (($latestSettlement['status'] ?? '') === 'Dikonfirmasi di Tempat'): ?>
                <div class="payment-proof-notice is-verified"><strong>Pelunasan di tempat telah dikonfirmasi.</strong><span><?= esc($latestSettlement['catatan'] ?? '') ?></span></div>
            <?php elseif (($latestSettlement['status'] ?? '') === 'Disetujui'): ?>
                <div class="payment-proof-notice is-verified"><strong>Bukti pelunasan telah disetujui admin.</strong><span><?= esc($latestSettlement['catatan'] ?? 'Sisa tagihan telah dinyatakan lunas.') ?></span></div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($paymentStatus === 'DP' && $remainingPayment > 0 && ($order['status_pesanan'] ?? '') === 'Diproses' && (! is_array($latestSettlement) || ! in_array(($latestSettlement['status'] ?? ''), ['Menunggu Verifikasi', 'Menunggu Pembayaran di Tempat'], true))): ?>
            <section class="invoice-proof-upload no-print"><h3>Pelunasan Sisa Tagihan</h3><p>Anda dapat melunasi sisa pembayaran melalui QRIS, transfer bank, atau bayar di tempat acara.</p><a class="primary" href="<?= base_url('riwayat-pesanan/' . (int) $order['id'] . '/pelunasan') ?>">Melunasi Sekarang →</a></section>
        <?php endif; ?>
        <?php if ($settlementPayments !== []): ?>
            <section class="settlement-history-invoice"><h3>Riwayat Pelunasan</h3><?php foreach ($settlementPayments as $settlementRow): ?><div><span><?= esc($settlementRow['metode_pembayaran'] ?? '-') ?> · <?= ! empty($settlementRow['created_at']) ? esc(date('d/m/Y H:i', strtotime($settlementRow['created_at']))) : '-' ?></span><b>Rp <?= number_format((float) ($settlementRow['jumlah'] ?? 0), 0, ',', '.') ?></b><small><?= esc($settlementRow['status'] ?? '-') ?><?php if (! empty($settlementRow['catatan'])): ?> · <?= esc($settlementRow['catatan']) ?><?php endif; ?></small></div><?php endforeach; ?></section>
        <?php endif; ?>
        <?php if (in_array($paymentStatus, ['Belum Bayar', 'Ditolak'], true)): ?>
            <section class="invoice-proof-upload no-print">
                <h3>Unggah Bukti Pembayaran</h3>
                <p><?= $paymentStatus === 'Ditolak' ? 'Silakan unggah ulang bukti pembayaran yang benar.' : 'Pilih file bukti pembayaran setelah menyelesaikan pembayaran.' ?></p>
                <form method="post" enctype="multipart/form-data" action="<?= base_url('invoice/' . (int) $order['id'] . '/bukti') ?>">
                    <?= csrf_field() ?>
                    <label class="proof-upload-box" for="invoice-proof-file">
                        <span class="proof-upload-icon" aria-hidden="true">⇧</span>
                        <span class="proof-upload-caption" id="invoice-proof-filename">Tarik file ke sini atau pilih dari perangkat</span>
                        <span class="proof-upload-button">Pilih File</span>
                        <small>JPG, JPEG, PNG, WEBP, atau PDF · Maksimal 5 MB</small>
                        <input id="invoice-proof-file" type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required>
                    </label>
                    <button class="primary" type="submit">Kirim Bukti untuk Diverifikasi</button>
                </form>
            </section>
        <?php endif; ?>
        <table>
            <thead>
                <tr><th>DESKRIPSI</th><th>JUMLAH</th><th>SUBTOTAL</th></tr>
            </thead>
            <tbody>
                <?php if (empty($orderItems)): ?>
                    <tr><td colspan="3">Rincian item untuk pesanan ini tidak tersedia.</td></tr>
                <?php else: ?>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <b><?= esc($item['nama']) ?></b><br>
                                <small><?= esc($item['keterangan']) ?> · Rp <?= number_format((float) $item['harga'], 0, ',', '.') ?> / item</small>
                            </td>
                            <td><?= (int) $item['jumlah'] ?></td>
                            <td>Rp <?= number_format((float) $item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (! empty($order['ongkos_kirim']) && (float) $order['ongkos_kirim'] > 0): ?>
                        <tr>
                            <td>
                                <b>Ongkos Kirim</b><br>
                                <small><?= esc($order['nama_kecamatan'] ?? '') ?><?= ! empty($order['nama_kelurahan']) ? ', ' . esc($order['nama_kelurahan']) : '' ?></small>
                            </td>
                            <td>1</td>
                            <td>Rp <?= number_format((float) $order['ongkos_kirim'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="invoice-total">
            <p>Total pesanan <b>Rp <?= number_format($orderTotal, 0, ',', '.') ?></b></p>
            <p>Skema pembayaran <b><?= esc($paymentSchemeLabel) ?></b></p>
            <p>Metode pembayaran <b><?= esc($paymentChannelLabel) ?></b></p>
            <p>Sudah dibayar <b>Rp <?= number_format($paidAmount, 0, ',', '.') ?></b></p>
            <h3><?= esc($paymentDueLabel) ?> <b>Rp <?= number_format($paymentDue, 0, ',', '.') ?></b></h3>
        </div>
        <footer>
            <b>Terima kasih telah mempercayakan momen Anda kepada Nauli Dekor.</b>
            <span>Silakan hubungi pihak Nauli Dekor untuk informasi lebih lanjut.</span>
        </footer>
    </article>
    <?php if ($invoiceSuccessMessage !== ''): ?>
        <div class="order-success-modal is-visible" id="order-success-modal" role="dialog" aria-modal="true" aria-labelledby="order-success-title">
            <div class="order-success-card">
                <button type="button" class="order-success-close" aria-label="Tutup" data-close-success>×</button>
                <span class="order-success-icon" aria-hidden="true">✓</span>
                <p class="eyebrow">PESANAN BERHASIL</p>
                <h2 id="order-success-title">Terima kasih telah mempercayai Nauli.</h2>
                <p>Pesanan <strong>#<?= esc($order['kode_pesanan'] ?? '-') ?></strong> telah kami terima. Tim Nauli Dekor akan memeriksa pembayaran dan menghubungi Anda melalui WhatsApp.</p>
                <div class="order-success-invoice">
                    <span>Invoice #<?= esc($order['kode_pesanan'] ?? '-') ?></span>
                    <button type="button" data-print-invoice>↓ Simpan / Cetak</button>
                </div>
                <div class="order-success-actions">
                    <a class="primary" href="#invoice-document" data-close-success>Lihat Invoice</a>
                    <a class="order-success-history" href="<?= base_url('riwayat-pesanan') ?>">Lihat Riwayat Pesanan</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php endif; ?>
<footer class="site-footer"><a class="logo" href="<?= base_url('dashboard') ?>"><span>✦</span> NAULI <small>DEKOR</small></a><p>Merangkai momen istimewa Anda.</p><p>© <?= date('Y') ?> Nauli Dekor</p></footer><script>
const menuToggle=document.querySelector('.menu-toggle');
if(menuToggle){menuToggle.addEventListener('click',()=>document.querySelector('.main-nav')?.classList.toggle('open'));}
(function(){
    const modal=document.getElementById('order-success-modal');
    if(!modal) return;
    const close=()=>modal.classList.remove('is-visible');
    modal.querySelectorAll('[data-close-success]').forEach((element)=>element.addEventListener('click',(event)=>{
        if(element.tagName==='A') event.preventDefault();
        close();
        if(element.tagName==='A') document.getElementById('invoice-document')?.scrollIntoView({behavior:'smooth'});
    }));
    modal.querySelector('[data-print-invoice]')?.addEventListener('click',()=>{
        close();
        window.setTimeout(()=>window.print(),120);
    });
})();
(function(){
    const form=document.getElementById('payment-form');
    const summary=document.getElementById('payment-summary');
    if(!form || !summary) return;
    const total=Number(form.dataset.total || 0);
    const money=new Intl.NumberFormat('id-ID',{maximumFractionDigits:0});
    const dueLabel=document.getElementById('payment-due-label');
    const dueValue=document.getElementById('payment-due-value');
    const remainingValue=document.getElementById('payment-remaining-value');
    const channelValue=document.getElementById('payment-channel-value');
    const note=document.getElementById('payment-summary-note');
    const format=(value)=>'Rp '+money.format(Math.max(0,Math.round(value)));

    function refreshPaymentSummary(){
        const scheme=form.querySelector('input[name="skema_pembayaran"]:checked');
        const channel=form.querySelector('input[name="kanal_pembayaran"]:checked');

        if(!scheme){
            dueLabel.textContent = 'Jumlah yang perlu dibayar';
            dueValue.textContent = '—';
            remainingValue.textContent = '—';
        } else {
            const isDp = scheme.value === 'DP';
            const due = isDp ? Math.round(total * 0.5) : total;
            dueLabel.textContent = isDp ? 'DP 50% yang dibayar' : 'Pembayaran Lunas 100%';
            dueValue.textContent = format(due);
            remainingValue.textContent = format(total - due);
        }

        channelValue.textContent = channel ? channel.value : 'Belum dipilih';

        if(!scheme && !channel){
            note.textContent = 'Silakan pilih skema dan metode pembayaran terlebih dahulu.';
        } else if(!scheme){
            note.textContent = 'Silakan pilih skema pembayaran (DP 50% atau Lunas 100%).';
        } else if(!channel){
            note.textContent = 'Silakan pilih metode pembayaran (QRIS atau Transfer Bank).';
        } else {
            note.textContent = scheme.value === 'DP'
                ? 'Pembayaran awal sebesar 50%. Sisa 50% dibayarkan sesuai kesepakatan dengan Nauli Dekor.'
                : 'Pembayaran sebesar 100% dari total pesanan.';
        }
    }

    form.querySelectorAll('input[name="skema_pembayaran"],input[name="kanal_pembayaran"]').forEach((input)=>{
        input.addEventListener('change',refreshPaymentSummary);
    });
    refreshPaymentSummary();

    // Popup QRIS muncul saat kanal QRIS dipilih atau link diklik
    const qrisModal=document.getElementById('qris-payment-modal');
    if(qrisModal){
        const closeButtons=qrisModal.querySelectorAll('[data-close-qris]');
        let previousFocus=null;
        const openQris=()=>{
            previousFocus=document.activeElement;
            qrisModal.classList.add('is-open');
            qrisModal.setAttribute('aria-hidden','false');
            document.body.classList.add('qris-modal-open');
            qrisModal.querySelector('.qris-popup-close')?.focus();
        };
        const closeQris=()=>{
            qrisModal.classList.remove('is-open');
            qrisModal.setAttribute('aria-hidden','true');
            document.body.classList.remove('qris-modal-open');
            if(previousFocus && typeof previousFocus.focus==='function') previousFocus.focus();
        };
        closeButtons.forEach((button)=>button.addEventListener('click',closeQris));
        document.addEventListener('keydown',(event)=>{
            if(event.key==='Escape' && qrisModal.classList.contains('is-open')) closeQris();
        });

        // Buka modal saat memilih QRIS
        form.querySelectorAll('input[name="kanal_pembayaran"]').forEach((input)=>{
            input.addEventListener('change',()=>{
                if(input.value==='QRIS') openQris();
            });
        });

        // Buka modal saat link "Lihat Kode QRIS" diklik
        document.querySelectorAll('.qris-view-link').forEach((link)=>{
            link.addEventListener('click',(e)=>{
                e.preventDefault();
                e.stopPropagation();
                const qrisRadio = form.querySelector('input[name="kanal_pembayaran"][value="QRIS"]');
                if (qrisRadio) {
                    qrisRadio.checked = true;
                    refreshPaymentSummary();
                }
                openQris();
            });
        });
    }
})();
(function(){
    const form=document.querySelector('.settlement-form');
    if(!form) return;
    const qris=document.getElementById('settlement-qris-panel');
    const proof=document.getElementById('settlement-proof-panel');
    const onsite=document.getElementById('settlement-onsite-panel');
    const file=document.getElementById('settlement-proof-file');
    const caption=document.getElementById('settlement-proof-filename');
    const refresh=()=>{
        const selected=form.querySelector('input[name="metode_pelunasan"]:checked')?.value || 'QRIS';
        if(qris) qris.hidden=selected!=='QRIS';
        if(proof) proof.hidden=selected==='Bayar di Tempat Acara';
        if(onsite) onsite.hidden=selected!=='Bayar di Tempat Acara';
        if(file) file.required=selected!=='Bayar di Tempat Acara';
    };
    form.querySelectorAll('input[name="metode_pelunasan"]').forEach(input=>input.addEventListener('change',refresh));
    file?.addEventListener('change',()=>{if(caption) caption.textContent=file.files?.[0]?.name || 'Pilih bukti pelunasan dari perangkat';});
    refresh();
})();
(function(){
    document.querySelectorAll('input[type="file"][name="bukti_pembayaran"]').forEach((input)=>{
        input.addEventListener('change',()=>{
            const target=document.getElementById(input.id==='payment-proof-file'?'payment-proof-filename':'invoice-proof-filename');
            if(target) target.textContent=input.files && input.files.length ? input.files[0].name : 'Tarik file ke sini atau pilih dari perangkat';
        });
    });
})();
(function(){
    const kecSelect = document.getElementById('booking-kecamatan');
    const kelSelect = document.getElementById('booking-kelurahan');
    const summary = document.getElementById('booking-summary');
    if (!kecSelect || !kelSelect || !summary) return;

    const cartTotal = Number(summary.dataset.cartTotal || 0);
    const ongkosRow = document.getElementById('booking-ongkos-row');
    const ongkosLabel = document.getElementById('booking-ongkos-label');
    const ongkosVal = document.getElementById('booking-ongkos-val');
    const totalVal = document.getElementById('booking-total-val');
    const money = new Intl.NumberFormat('id-ID', {maximumFractionDigits: 0});
    const format = (value) => 'Rp ' + money.format(Math.max(0, Math.round(value)));

    function updateSummary() {
        const selectedOpt = kelSelect.selectedOptions ? kelSelect.selectedOptions[0] : null;
        const ongkos = selectedOpt ? Number(selectedOpt.dataset.ongkos || 0) : 0;
        const kelText = selectedOpt && selectedOpt.value ? selectedOpt.textContent.split('(')[0].trim() : '';

        if (selectedOpt && selectedOpt.value && ongkos > 0) {
            ongkosRow.style.display = 'flex';
            ongkosLabel.textContent = 'Ongkos Kirim' + (kelText ? ' (' + kelText + ')' : '');
            ongkosVal.textContent = format(ongkos);
            totalVal.textContent = format(cartTotal + ongkos);
        } else if (selectedOpt && selectedOpt.value) {
            ongkosRow.style.display = 'flex';
            ongkosLabel.textContent = 'Ongkos Kirim' + (kelText ? ' (' + kelText + ')' : '');
            ongkosVal.textContent = 'Gratis / Rp 0';
            totalVal.textContent = format(cartTotal);
        } else {
            ongkosRow.style.display = 'none';
            totalVal.textContent = format(cartTotal);
        }
    }

    async function loadKelurahan(kecId, preselectedId = null) {
        if (!kecId) {
            kelSelect.innerHTML = '<option value="">— Pilih Kecamatan terlebih dahulu —</option>';
            updateSummary();
            return;
        }

        kelSelect.innerHTML = '<option value="">Memuat kelurahan...</option>';
        try {
            const res = await fetch('<?= rtrim(base_url('api/kelurahan'), '/') ?>/' + encodeURIComponent(kecId));
            if (!res.ok) throw new Error('Gagal mengambil data kelurahan');
            const data = await res.json();

            let html = '<option value="">— Pilih Kelurahan —</option>';
            if (!data || data.length === 0) {
                html = '<option value="">— Belum ada kelurahan untuk kecamatan ini —</option>';
            } else {
                data.forEach((item) => {
                    const ongkos = Number(item.ongkos_kirim || 0);
                    const isSelected = preselectedId && String(preselectedId) === String(item.id);
                    html += '<option value="' + item.id + '" data-ongkos="' + ongkos + '" ' + (isSelected ? 'selected' : '') + '>'
                        + item.nama_kelurahan + ' (' + format(ongkos) + ')</option>';
                });
            }
            kelSelect.innerHTML = html;
            updateSummary();
        } catch (err) {
            kelSelect.innerHTML = '<option value="">Gagal memuat kelurahan</option>';
            console.error(err);
        }
    }

    kecSelect.addEventListener('change', () => {
        loadKelurahan(kecSelect.value);
    });

    kelSelect.addEventListener('change', updateSummary);

    const preselectedKel = kelSelect.dataset.selected || kelSelect.value;
    if (kecSelect.value) {
        loadKelurahan(kecSelect.value, preselectedKel);
    }
})();
(function(){
    const passModal = document.getElementById('change-password-modal');
    if (!passModal) return;

    const openBtn = document.getElementById('btn-open-password-modal');
    const closeBtn = document.getElementById('close-pass-btn');
    const backdrop = document.getElementById('close-pass-backdrop');
    const cancelBtn = document.getElementById('btn-cancel-pass');

    const openModal = () => {
        passModal.style.display = 'flex';
        passModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        passModal.style.display = 'none';
        passModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && passModal.style.display === 'flex') {
            closeModal();
        }
    });

    // Toggle eye buttons untuk lihat / sembunyikan password
    passModal.querySelectorAll('.btn-toggle-eye').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const targetInput = document.getElementById(targetId);
            if (!targetInput) return;
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                btn.textContent = '🙈';
            } else {
                targetInput.type = 'password';
                btn.textContent = '👁';
            }
        });
    });
})();
// ===================== AJAX Cart Stepper =====================
(function(){
    const money = new Intl.NumberFormat('id-ID', {maximumFractionDigits: 0});
    const fmt = (v) => 'Rp ' + money.format(Math.max(0, Math.round(v)));

    function updateNavBadge(count) {
        document.querySelectorAll('.cart-badge-count').forEach(el => { el.textContent = count; });
        document.querySelectorAll('.mobile-only-links a[href*="keranjang"]').forEach(el => {
            el.textContent = 'Keranjang (' + count + ')';
        });
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="X-CSRF-TOKEN"]');
        if (meta) return meta.content;
        // Cari dari hidden input di form mana saja
        const inp = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return inp ? inp.value : '';
    }

    function getCsrfName() {
        return '<?= csrf_token() ?>';
    }

    async function sendUpdate(form, newQty) {
        const key = form.dataset.key;
        const price = parseFloat(form.dataset.price) || 0;
        const stock = parseInt(form.dataset.stock, 10) || 1;
        const input = form.querySelector('.cart-qty-input');
        const displayEl = form.querySelector('.cart-qty-display');
        const btnMinus = form.querySelector('.btn-qty-step.minus');
        const btnPlus = form.querySelector('.btn-qty-step.plus');

        // Optimistic UI update
        const oldQty = parseInt(input.value, 10) || 1;
        input.value = newQty;
        if (displayEl) displayEl.textContent = newQty;
        btnMinus.disabled = newQty <= 1;
        btnPlus.disabled = newQty >= stock;

        // Update line total optimistically
        const article = form.closest('article.cart-item');
        if (article) {
            const lineEl = article.querySelector('.cart-item-line-total');
            if (lineEl) lineEl.textContent = fmt(price * newQty);
        }

        try {
            const body = new URLSearchParams();
            body.set(getCsrfName(), getCsrfToken());
            body.set('jumlah', newQty);

            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: body,
                credentials: 'same-origin',
            });

            if (!res.ok) {
                // Rollback
                input.value = oldQty;
                if (displayEl) displayEl.textContent = oldQty;
                btnMinus.disabled = oldQty <= 1;
                btnPlus.disabled = oldQty >= stock;
                if (article) {
                    const lineEl = article.querySelector('.cart-item-line-total');
                    if (lineEl) lineEl.textContent = fmt(price * oldQty);
                }
                const data = await res.json().catch(() => ({}));
                if (data.message) alert(data.message);
                return;
            }

            const data = await res.json();
            if (data.success) {
                // Sync confirmed quantity from server
                const confirmedQty = data.quantity ?? newQty;
                input.value = confirmedQty;
                if (displayEl) displayEl.textContent = confirmedQty;
                btnMinus.disabled = confirmedQty <= 1;
                btnPlus.disabled = confirmedQty >= stock;

                if (article) {
                    const lineEl = article.querySelector('.cart-item-line-total');
                    if (lineEl) lineEl.textContent = data.lineTotalFormatted ?? fmt(price * confirmedQty);
                    // Update price line text
                    const priceLineEl = article.querySelector('.cart-item-price-line');
                    if (priceLineEl) {
                        const qtySpan = priceLineEl.querySelector('.cart-item-qty-display');
                        if (qtySpan) qtySpan.textContent = confirmedQty;
                    }
                }

                // Update summary
                const countEl = document.getElementById('cart-summary-count');
                const totalEl = document.getElementById('cart-summary-total');
                if (countEl && data.cartCount !== undefined) countEl.textContent = data.cartCount;
                if (totalEl && data.cartTotalFormatted !== undefined) totalEl.textContent = data.cartTotalFormatted;

                // Update nav badge
                if (data.cartCount !== undefined) updateNavBadge(data.cartCount);

                // Update CSRF token if rotated (session mode won't rotate but just in case)
                const newToken = data['<?= csrf_token() ?>'];
                if (newToken) {
                    document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(el => { el.value = newToken; });
                }
            }
        } catch (err) {
            // Rollback on network error
            input.value = oldQty;
            if (displayEl) displayEl.textContent = oldQty;
            btnMinus.disabled = oldQty <= 1;
            btnPlus.disabled = oldQty >= stock;
            console.error('Cart update gagal:', err);
        }
    }

    document.querySelectorAll('.cart-qty-form').forEach(form => {
        const input = form.querySelector('.cart-qty-input');
        const btnMinus = form.querySelector('.btn-qty-step.minus');
        const btnPlus = form.querySelector('.btn-qty-step.plus');
        if (!input || !btnMinus || !btnPlus) return;

        const max = parseInt(input.getAttribute('max'), 10) || 999;
        const min = parseInt(input.getAttribute('min'), 10) || 1;

        btnMinus.addEventListener('click', () => {
            const val = parseInt(input.value, 10) || 1;
            if (val > min) sendUpdate(form, val - 1);
        });

        btnPlus.addEventListener('click', () => {
            const val = parseInt(input.value, 10) || 1;
            if (val < max) sendUpdate(form, val + 1);
        });
    });
})();
// ===================== Booking Date — Calendar Overlay =====================
(function(){
    const overlay    = document.getElementById('booking-cal-overlay');
    const backdrop   = document.getElementById('booking-cal-backdrop');
    const closeBtn   = document.getElementById('booking-cal-close');
    const prevBtn    = document.getElementById('booking-cal-prev');
    const nextBtn    = document.getElementById('booking-cal-next');
    const monthTitle = document.getElementById('booking-cal-month');
    const grid       = document.getElementById('booking-cal-grid');
    const footer     = document.getElementById('booking-cal-footer');
    const selDisplay = document.getElementById('booking-cal-selected-display');
    const confirmBtn = document.getElementById('booking-cal-confirm');
    const loadingEl  = document.getElementById('booking-cal-loading');

    const changeBtn    = document.getElementById('date-change-btn');
    const hiddenInput  = document.getElementById('tanggal-acara-hidden');
    const lockedDisplay = document.getElementById('date-locked-display');
    const conflictMsg   = document.getElementById('date-conflict-msg');
    const badge = document.getElementById('date-locked-badge');

    if (!overlay || !changeBtn) return;

    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const bookedDates = <?= isset($bookedDates) ? json_encode($bookedDates) : '[]' ?>;
    const mainPackageId = <?= isset($mainPackageId) ? (int) $mainPackageId : 'null' ?>;
    const API_URL = mainPackageId ? '<?= rtrim(base_url('api/paket'), '/') ?>/' + mainPackageId + '/booked-dates' : null;

    let selectedDate = (hiddenInput && hiddenInput.value) ? hiddenInput.value : null;
    let currentYear, currentMonth;
    let resolvedBookedDates = [...bookedDates];

    function padZero(n) { return String(n).padStart(2,'0'); }
    function dateStr(y,m,d) { return y+'-'+padZero(m)+'-'+padZero(d); }
    function formatId(str) {
        if (!str) return '';
        const [y,m,d] = str.split('-');
        return parseInt(d)+' '+(monthNames[parseInt(m)-1]||m)+' '+y;
    }

    async function loadBookedDates() {
        if (!API_URL) return;
        if (loadingEl) loadingEl.style.display = 'flex';
        try {
            const res = await fetch(API_URL, { headers:{'Accept':'application/json'}, credentials:'same-origin' });
            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data.booked_dates)) resolvedBookedDates = data.booked_dates;
            }
        } catch(e) {}
        if (loadingEl) loadingEl.style.display = 'none';
        renderCalendar();
    }

    function renderCalendar() {
        if (!grid || !monthTitle) return;
        monthTitle.textContent = monthNames[currentMonth] + ' ' + currentYear;

        const now = new Date(); now.setHours(0,0,0,0);
        const todayStr = dateStr(now.getFullYear(), now.getMonth()+1, now.getDate());
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth+1, 0).getDate();

        let html = '';
        for (let i=0; i<firstDay; i++) html += '<span class="cal-day empty"></span>';
        for (let d=1; d<=daysInMonth; d++) {
            const ds         = dateStr(currentYear, currentMonth+1, d);
            const isToday    = ds === todayStr;
            const isPast     = ds < todayStr;
            const isBooked   = resolvedBookedDates.includes(ds);
            const isSelected = ds === selectedDate;

            let cls = 'cal-day';
            let noteText = 'Bisa';
            let noteDesc = 'Tersedia untuk dipesan';

            if (isPast) {
                cls += ' past';
                noteText = 'Lewat';
                noteDesc = 'Tanggal sudah terlewat';
            } else if (isBooked) {
                cls += ' booked';
                noteText = 'Penuh';
                noteDesc = 'Sudah dibooking untuk paket ini (tidak tersedia)';
            } else if (isSelected) {
                cls += ' selected';
                noteText = 'Dipilih';
                noteDesc = 'Tanggal terpilih untuk acara Anda';
            } else {
                cls += ' available';
                noteText = 'Ada';
                noteDesc = 'Tersedia untuk dipesan';
            }
            if (isToday && !isPast) cls += ' today';

            const disabled = isPast || isBooked;
            html += '<button type="button" class="' + cls + '" data-date="' + ds + '" title="' + formatId(ds) + ': ' + noteDesc + '"' +
                (disabled ? ' disabled aria-disabled="true"' : '') + '>' +
                '<span class="cal-day-num">' + d + '</span>' +
                '<span class="cal-day-note">' + noteText + '</span>' +
                '</button>';
        }
        grid.innerHTML = html;

        grid.querySelectorAll('.cal-day').forEach(btn => {
            const ds = btn.dataset.date;
            if (!ds) return;

            if (!btn.disabled) {
                btn.addEventListener('click', () => {
                    selectedDate = ds;
                    if (selDisplay) selDisplay.textContent = formatId(selectedDate);
                    if (footer) footer.style.display = 'flex';
                    renderCalendar();
                });
            }
        });
    }

    function navigate(dir) {
        currentMonth += dir;
        if (currentMonth > 11) { currentMonth=0; currentYear++; }
        if (currentMonth < 0)  { currentMonth=11; currentYear--; }
        renderCalendar();
    }
    prevBtn?.addEventListener('click', () => navigate(-1));
    nextBtn?.addEventListener('click', () => navigate(+1));

    // Open overlay
    function openOverlay() {
        const now = new Date();
        currentYear  = now.getFullYear();
        currentMonth = now.getMonth();

        // If existing date, navigate to its month
        if (selectedDate) {
            const [y,m] = selectedDate.split('-');
            currentYear  = parseInt(y);
            currentMonth = parseInt(m) - 1;
        }

        overlay.setAttribute('aria-hidden','false');
        overlay.classList.add('is-open');
        document.body.classList.add('cal-modal-open');

        if (resolvedBookedDates.length === 0 && API_URL) {
            loadBookedDates();
        } else {
            renderCalendar();
        }
        setTimeout(() => closeBtn?.focus(), 50);
    }

    function closeOverlay() {
        overlay.setAttribute('aria-hidden','true');
        overlay.classList.remove('is-open');
        document.body.classList.remove('cal-modal-open');
    }

    changeBtn.addEventListener('click', openOverlay);
    closeBtn?.addEventListener('click', closeOverlay);
    backdrop?.addEventListener('click', closeOverlay);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeOverlay(); });

    // Confirm
    confirmBtn?.addEventListener('click', () => {
        if (!selectedDate) return;
        if (hiddenInput)   hiddenInput.value = selectedDate;
        if (lockedDisplay) lockedDisplay.textContent = formatId(selectedDate);
        // Update badge style
        if (badge) {
            badge.style.borderColor = '';
            badge.style.background  = '';
            const emptySpan = badge.querySelector('span[style]');
            if (emptySpan) emptySpan.style.cssText = '';
        }
        if (conflictMsg) conflictMsg.style.display = 'none';
        // Make hidden input not required after selection
        if (hiddenInput) hiddenInput.removeAttribute('required');
        closeOverlay();
    });
})();
</script></body></html>
