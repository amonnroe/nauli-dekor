<?php
/**
 * Shared Consistent Navbar for Nauli Dekor
 * Variables:
 *   $activeNav: 'home' | 'packages' | 'extras' | 'cart' | 'account'
 */
$activeNav = isset($activeNav) ? (string) $activeNav : '';

// Hitung jumlah item keranjang langsung dari sesi
$storedCart = session('cart');
$navCartCount = 0;
if (is_array($storedCart)) {
    foreach ($storedCart as $item) {
        if (is_array($item)) {
            $navCartCount += max(0, (int) ($item['jumlah'] ?? 0));
        }
    }
}

// Data pengguna yang login
$navUser = session('user_name') ?? session('admin_name') ?? null;
$navFirstName = $navUser ? explode(' ', trim((string) $navUser))[0] : null;
$isAdmin = (bool) session('admin_id');
?>

<!-- Pastikan stylesheet navbar termuat -->
<link rel="stylesheet" href="<?= base_url('assets/css/navbar.css') ?>?v=20261122-1">

<?php if ($isAdmin): ?>
    <aside class="admin-monitor-banner">
        <div class="banner-inner">
            <div class="banner-text">
                <span class="banner-sparkle">✦</span>
                <span><b>Mode Pemantauan Admin:</b> Anda sedang melihat tampilan live website Nauli Dekor.</span>
            </div>
            <a class="banner-return-link" href="<?= base_url('admin') ?>">
                ⚙ Kembali ke Panel Admin
            </a>
        </div>
    </aside>
<?php endif; ?>

<header class="site-header">
    <div class="header-inner">
        <!-- Brand / Logo -->
        <a class="logo" href="<?= base_url('dashboard') ?>">
            <span>✦</span> NAULI <small>DEKOR</small>
        </a>

        <!-- Mobile Menu Toggle Button -->
        <button class="menu-toggle" aria-label="Buka navigasi menu" type="button" onclick="const n=this.closest('.site-header').querySelector('.main-nav');if(n)n.classList.toggle('open');">
            <span>☰</span>
        </button>

        <!-- Main Navigation Links: Beranda, Produk, Tambahan Dekorasi -->
        <nav class="main-nav">
            <a class="<?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
                Beranda
            </a>
            <a class="<?= $activeNav === 'packages' ? 'active' : '' ?>" href="<?= base_url('paket-dekorasi') ?>">
                Produk
            </a>
            <a class="<?= $activeNav === 'extras' ? 'active' : '' ?>" href="<?= base_url('tambahan-dekorasi') ?>">
                Tambahan Dekorasi
            </a>
            <!-- Mobile-only quick links -->
            <div class="mobile-only-links">
                <a class="<?= $activeNav === 'cart' ? 'active' : '' ?>" href="<?= base_url('keranjang') ?>">
                    Keranjang (<?= $navCartCount ?>)
                </a>
                <a class="<?= $activeNav === 'history' ? 'active' : '' ?>" href="<?= base_url('riwayat-pesanan') ?>">
                    Riwayat Pemesanan
                </a>
                <?php if ($isAdmin): ?>
                    <a href="<?= base_url('admin') ?>">Panel Admin</a>
                    <a class="mobile-logout-link" href="<?= base_url('admin/logout') ?>">Keluar dari Panel Admin</a>
                <?php elseif ($navFirstName): ?>
                    <a class="<?= $activeNav === 'account' ? 'active' : '' ?>" href="<?= base_url('akun') ?>">
                        Akun Saya (Hi, <?= esc($navFirstName) ?>!)
                    </a>
                    <a class="mobile-logout-link" href="<?= base_url('logout') ?>">Keluar (Logout)</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>">Masuk</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Right Side Actions: Cart Icon + Receipt Icon + Hi, User! Button + Keluar (side-by-side) -->
        <div class="header-actions">
            <!-- Icon Cart with Badge -->
            <a class="cart-action-btn <?= $activeNav === 'cart' ? 'active' : '' ?>" href="<?= base_url('keranjang') ?>" title="Keranjang Belanja">
                <svg class="cart-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span class="cart-badge-count"><?= $navCartCount ?></span>
            </a>

            <!-- Icon Struk / Invoice (Riwayat Pemesanan) -->
            <a class="receipt-action-btn <?= $activeNav === 'history' ? 'active' : '' ?>" href="<?= base_url('riwayat-pesanan') ?>" title="Riwayat Pemesanan & Struk">
                <svg class="receipt-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"></path>
                    <line x1="8" y1="7" x2="16" y2="7"></line>
                    <line x1="8" y1="11" x2="16" y2="11"></line>
                    <line x1="8" y1="15" x2="13" y2="15"></line>
                </svg>
            </a>

            <!-- User Button: Hi! [User] or Masuk (tanpa icon profil) -->
            <?php if ($isAdmin): ?>
                <a class="user-action-btn admin <?= $activeNav === 'account' ? 'active' : '' ?>" href="<?= base_url('akun') ?>" title="Lihat tampilan halaman akun pelanggan">
                    <span class="user-btn-text">Hi, Admin!</span>
                </a>
                <a class="nav-logout-btn" href="<?= base_url('admin/logout') ?>" title="Keluar dari Panel Admin">
                    Keluar
                </a>
            <?php elseif ($navFirstName): ?>
                <a class="user-action-btn <?= $activeNav === 'account' ? 'active' : '' ?>" href="<?= base_url('akun') ?>" title="Buka Profil Akun">
                    <span class="user-btn-text">Hi, <?= esc($navFirstName) ?>!</span>
                </a>
                <a class="nav-logout-btn" href="<?= base_url('logout') ?>" title="Keluar dari Akun">
                    Keluar
                </a>
            <?php else: ?>
                <a class="user-action-btn login" href="<?= base_url('login') ?>">
                    <span class="user-btn-text">Masuk</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
