<?php
helper('form');
/** @var array<string, mixed> $package */
$imageName = ! empty($package['gambar']) ? basename((string) $package['gambar']) : '';
$imageUrl = $imageName !== '' ? base_url('assets/images/' . rawurlencode($imageName)) : '';
$categoryLabel = ($package['kategori'] ?? '') === 'duka' ? 'Rumah Duka' : 'Pernikahan';
$typeLabels = [
    'biasa' => 'Paket Biasa',
    'lengkap' => 'Paket Lengkap',
    'tambahan' => 'Paket Tambahan',
];
$typeLabel = $typeLabels[$package['jenis_paket'] ?? ''] ?? ucfirst((string) ($package['jenis_paket'] ?? ''));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($package['nama_paket']) ?> | Nauli Dekor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/packages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/package-detail.css') ?>">
</head>
<body>
    <?= view('partials/navbar', ['activeNav' => 'packages']) ?>

    <main>
        <section class="detail-heading">
            <div>
                <p class="eyebrow">KOLEKSI NAULI DEKOR</p>
                <h1>Detail Paket Dekorasi</h1>
            </div>
            <nav class="breadcrumbs">
                <a href="<?= base_url('dashboard') ?>">Beranda</a><span>/</span>
                <a href="<?= base_url('paket-dekorasi') ?>">Katalog Paket</a><span>/</span>
                <b>Detail</b>
            </nav>
        </section>

        <section class="detail-container">
            <div class="detail-main">
                <div class="detail-photo">
                    <?php if ($imageUrl !== ''): ?>
                        <img src="<?= esc($imageUrl) ?>" alt="<?= esc($package['nama_paket']) ?>">
                    <?php else: ?>
                        <div class="detail-no-image">Foto paket belum tersedia</div>
                    <?php endif; ?>
                </div>

                <div class="detail-summary">
                    <p class="eyebrow">PAKET DEKORASI</p>
                    <h1><?= esc($package['nama_paket']) ?></h1>
                    <p class="detail-description"><?= esc($package['deskripsi'] ?: 'Informasi deskripsi paket belum tersedia.') ?></p>

                    <div class="detail-price">
                        <span>Harga paket</span>
                        <strong>Rp <?= number_format((float) $package['harga'], 0, ',', '.') ?></strong>
                    </div>

                    <div class="detail-facts">
                        <div><span>Kategori</span><b>Dekorasi Acara</b></div>
                        <div><span>Lokasi dekorasi</span><b><?= esc($package['lokasi']) ?></b></div>
                    </div>

                    <!-- Tanggal terpilih info + tombol Pilih Tanggal -->
                    <div class="pkg-date-trigger-section">
                        
                        <button type="button" class="btn-pilih-tanggal" id="btn-pilih-tanggal">
                         Pilih Tanggal Acara
                        </button>
                    </div>

                    <div class="detail-actions">
                        <form method="post" action="<?= base_url('pesan-sekarang/' . (int) $package['id']) ?>" class="detail-action-form" id="form-pesan-sekarang">
                            <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                            <input type="hidden" name="tanggal_acara" id="hidden-date-pesan" value="">
                            <button class="btn-pesan-sekarang" type="submit">Pesan Sekarang</button>
                        </form>
                        <button class="btn-tambah-keranjang" type="button" id="btn-tambah-keranjang">Tambah ke Keranjang</button>
                        <form method="post" action="<?= base_url('keranjang/paket/' . (int) $package['id']) ?>" class="detail-action-form" id="form-keranjang" style="display:none;">
                            <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                            <input type="hidden" name="tanggal_acara" id="hidden-date-keranjang" value="">
                        </form>
                    </div>

                    <!-- Toast Notification -->
                    <div class="cart-toast" id="cart-toast" role="status" aria-live="polite"></div>

                    <!-- Calendar Overlay Popup -->
                    <div class="cal-overlay" id="cal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cal-overlay-title">
                        <div class="cal-overlay-backdrop" id="cal-overlay-backdrop"></div>
                        <div class="cal-overlay-card">
                            <div class="cal-overlay-header">
                                <div>
                                    <p class="eyebrow" style="margin:0 0 2px;">KETERSEDIAAN PAKET</p>
                                    <h3 class="cal-overlay-title" id="cal-overlay-title">Pilih Tanggal Acara</h3>
                                </div>
                                <button type="button" class="cal-overlay-close" id="cal-overlay-close" aria-label="Tutup">×</button>
                            </div>
                            <div class="cal-overlay-loading" id="cal-loading-hint" style="display:none;">
                                <span class="cal-spinner"></span> Memuat data ketersediaan…
                            </div>
                            <div class="cal-nav">
                                <button type="button" class="cal-nav-btn" id="cal-prev">‹</button>
                                <span class="cal-month-title" id="cal-month-title">—</span>
                                <button type="button" class="cal-nav-btn" id="cal-next">›</button>
                            </div>
                            <div class="cal-weekdays">
                                <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                            </div>
                            <div class="cal-days-grid" id="cal-days-grid"></div>
                            <div class="cal-legend">
                                <span class="cal-legend-item"><span class="cal-dot available"></span>Tersedia</span>
                                <span class="cal-legend-item"><span class="cal-dot booked"></span>Sudah dipesan</span>
                                <span class="cal-legend-item"><span class="cal-dot today"></span>Hari ini</span>
                                <span class="cal-legend-item"><span class="cal-dot selected"></span>Dipilih</span>
                            </div>
                            <div class="cal-overlay-footer" id="cal-overlay-footer" style="display:none;">
                                <div class="cal-overlay-sel-info">
                                    <span>Tanggal dipilih:</span>
                                    <strong id="cal-overlay-selected-display">—</strong>
                                </div>
                                <button type="button" class="cal-overlay-confirm" id="cal-overlay-confirm">Gunakan Tanggal Ini →</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="detail-information">
                <div>
                    <p class="eyebrow">TENTANG PAKET</p>
                    <h2>Rincian dekorasi</h2>
                    <p><?= esc($package['deskripsi'] ?: 'Belum ada deskripsi tambahan untuk paket ini.') ?></p>
                </div>
                <div class="detail-note">
                    <span>✦</span>
                    <div>
                        <b>Butuh informasi lebih lanjut?</b>
                        <p>Silakan hubungi Nauli Dekor untuk mendiskusikan kebutuhan acara dan detail paket ini.</p>
                        <a href="<?= base_url('akun') ?>">Hubungi melalui akun Anda →</a>
                    </div>
                </div>
            </section>
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

        // ===================== Interactive Availability Calendar — Overlay Mode =====================
        (function() {
            const PACKAGE_ID = <?= (int) $package['id'] ?>;
            const API_URL    = '<?= base_url('api/paket/' . (int) $package['id'] . '/booked-dates') ?>';
            const CSRF_NAME  = '<?= csrf_token() ?>';

            let bookedDates  = [];
            let selectedDate = null;
            let currentYear, currentMonth;
            let datesLoaded  = false;

            const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

            // DOM refs — overlay
            const overlay    = document.getElementById('cal-overlay');
            const backdrop   = document.getElementById('cal-overlay-backdrop');
            const closeBtn   = document.getElementById('cal-overlay-close');
            const prevBtn    = document.getElementById('cal-prev');
            const nextBtn    = document.getElementById('cal-next');
            const monthTitle = document.getElementById('cal-month-title');
            const grid       = document.getElementById('cal-days-grid');
            const footer     = document.getElementById('cal-overlay-footer');
            const selDisplay = document.getElementById('cal-overlay-selected-display');
            const confirmBtn = document.getElementById('cal-overlay-confirm');
            const loadingEl  = document.getElementById('cal-loading-hint');

            // DOM refs — trigger + actions
            const triggerBtn     = document.getElementById('btn-pilih-tanggal');
            const calSelInfo     = document.getElementById('cal-selected-info');
            const calSelDisplay  = document.getElementById('cal-selected-display');
            const calClearBtn    = document.getElementById('cal-clear-btn');
            const hiddenPesan    = document.getElementById('hidden-date-pesan');
            const hiddenKeranjang = document.getElementById('hidden-date-keranjang');
            const formPesan      = document.getElementById('form-pesan-sekarang');
            const btnTambah      = document.getElementById('btn-tambah-keranjang');
            const formKeranjang  = document.getElementById('form-keranjang');
            const toast          = document.getElementById('cart-toast');

            // Utils
            function padZero(n)  { return String(n).padStart(2,'0'); }
            function dateStr(y,m,d) { return y+'-'+padZero(m)+'-'+padZero(d); }
            function formatDisplay(str) {
                if (!str) return '—';
                const [y,m,d] = str.split('-');
                return parseInt(d)+' '+(monthNames[parseInt(m)-1]||m)+' '+y;
            }

            // Load booked dates from API
            async function loadBookedDates() {
                if (datesLoaded) return;
                if (loadingEl) loadingEl.style.display = 'flex';
                try {
                    const res = await fetch(API_URL, { headers:{'Accept':'application/json'}, credentials:'same-origin' });
                    if (res.ok) {
                        const data = await res.json();
                        if (Array.isArray(data.booked_dates)) bookedDates = data.booked_dates;
                    }
                } catch(e) {}
                datesLoaded = true;
                if (loadingEl) loadingEl.style.display = 'none';
                renderCalendar();
            }

            // Render calendar grid with notes on each date
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
                    const isBooked   = bookedDates.includes(ds);
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
                        noteDesc = 'Sudah dibooking (tidak tersedia)';
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
                    html += '<button type="button" class="' + cls + '" data-date="' + ds + '" title="' + formatDisplay(ds) + ': ' + noteDesc + '"' +
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
                            if (selDisplay) selDisplay.textContent = formatDisplay(selectedDate);
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
                if (selectedDate) {
                    const [y,m] = selectedDate.split('-');
                    currentYear  = parseInt(y);
                    currentMonth = parseInt(m) - 1;
                }
                overlay.setAttribute('aria-hidden','false');
                overlay.classList.add('is-open');
                document.body.classList.add('cal-modal-open');
                loadBookedDates();
                setTimeout(() => closeBtn?.focus(), 50);
            }

            function closeOverlay() {
                overlay.setAttribute('aria-hidden','true');
                overlay.classList.remove('is-open');
                document.body.classList.remove('cal-modal-open');
            }

            triggerBtn?.addEventListener('click', openOverlay);
            closeBtn?.addEventListener('click', closeOverlay);
            backdrop?.addEventListener('click', closeOverlay);
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && overlay?.classList.contains('is-open')) closeOverlay();
            });

            // Confirm date from overlay
            confirmBtn?.addEventListener('click', () => {
                if (!selectedDate) return;
                if (hiddenPesan)    hiddenPesan.value    = selectedDate;
                if (hiddenKeranjang) hiddenKeranjang.value = selectedDate;
                if (calSelDisplay)  calSelDisplay.textContent = formatDisplay(selectedDate);
                if (calSelInfo)     calSelInfo.style.display = 'flex';
                // Update trigger button text
                if (triggerBtn) triggerBtn.innerHTML = '<span>📅</span> Ubah Tanggal — ' + formatDisplay(selectedDate);
                closeOverlay();
            });

            // Clear date
            calClearBtn?.addEventListener('click', () => {
                selectedDate = null;
                if (hiddenPesan)    hiddenPesan.value    = '';
                if (hiddenKeranjang) hiddenKeranjang.value = '';
                if (calSelInfo)     calSelInfo.style.display = 'none';
                if (triggerBtn) triggerBtn.innerHTML = '<span>📅</span> Pilih Tanggal Acara';
                renderCalendar();
            });

            // Validate before submit
            formPesan?.addEventListener('submit', (e) => {
                if (!selectedDate) {
                    e.preventDefault();
                    showToast('Silakan pilih tanggal acara terlebih dahulu.', 'error');
                    openOverlay();
                }
            });

            // === Tambah ke Keranjang AJAX ===
            function showToast(msg, type) {
                if (!toast) return;
                toast.textContent = msg;
                toast.className = 'cart-toast visible ' + (type === 'error' ? 'toast-error' : 'toast-success');
                clearTimeout(toast._t);
                toast._t = setTimeout(() => { toast.className = 'cart-toast'; }, 3200);
            }

            function getCsrfValue() {
                const inp = document.querySelector('input[name="' + CSRF_NAME + '"]');
                return inp ? inp.value : '';
            }

            btnTambah?.addEventListener('click', async () => {
                if (!selectedDate) {
                    showToast('Silakan pilih tanggal acara terlebih dahulu.', 'error');
                    openOverlay();
                    return;
                }
                btnTambah.disabled = true;
                btnTambah.textContent = 'Menambahkan…';

                const body = new URLSearchParams();
                body.set(CSRF_NAME, getCsrfValue());
                body.set('tanggal_acara', selectedDate);

                try {
                    const res = await fetch(formKeranjang.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: body,
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('✓ Paket berhasil ditambahkan ke keranjang!', 'success');
                        if (data.cartCount !== undefined) {
                            document.querySelectorAll('.cart-badge-count').forEach(el => el.textContent = data.cartCount);
                        }
                    } else {
                        showToast(data.message || 'Gagal menambahkan ke keranjang.', 'error');
                    }
                } catch (err) {
                    showToast('Gagal terhubung ke server. Coba lagi.', 'error');
                } finally {
                    btnTambah.disabled = false;
                    btnTambah.textContent = 'Tambah ke Keranjang';
                }
            });
        })();
    </script>
</body>
</html>
