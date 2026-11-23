<?php
helper('form');
$section = isset($section) ? (string) $section : 'dashboard';
$title = isset($title) ? (string) $title : 'Panel Admin';
$adminPhone = isset($adminPhone) ? (string) $adminPhone : '';
// Controller mengirim isEdit pada form tambah/ubah; default aman untuk mode tambah.
$isEdit = isset($isEdit) ? (bool) $isEdit : false;
$successMessage = session()->getFlashdata('success');
$errorMessage = session()->getFlashdata('error');
$warningMessage = session()->getFlashdata('warning');
$errors = session()->getFlashdata('errors') ?? [];
$currency = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
$statusClass = static function ($status): string {
    return match ((string) $status) {
        'Selesai', 'Lunas', 'Aktif' => 'status good',
        'Diproses', 'DP', 'Menunggu Verifikasi' => 'status progress',
        'Dibatalkan', 'Nonaktif', 'Ditolak' => 'status muted',
        default => 'status pending',
    };
};
$dekorLabel = static function ($status): string {
    return match ((string) $status) {
        'Diproses' => 'Diproses',
        'Selesai'  => 'Selesai',
        default    => 'Menunggu Pembayaran',
    };
};
$dekorClass = static function ($status): string {
    return match ((string) $status) {
        'Diproses' => 'status progress',
        'Selesai'  => 'status good',
        default    => 'status pending',
    };
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> | Nauli Dekor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <a class="admin-brand" href="<?= base_url('admin') ?>"><span>✦</span> NAULI <small>DEKOR</small></a>
        <p class="admin-sidebar-label">MENU PENGELOLA</p>
        <nav class="admin-nav">
            <a class="<?= $section === 'dashboard' ? 'active' : '' ?>" href="<?= base_url('admin') ?>"><span>⌂</span> Dashboard</a>
            <a class="<?= in_array($section, ['packages', 'package-form'], true) ? 'active' : '' ?>" href="<?= base_url('admin/paket') ?>"><span>✿</span> Paket Dekorasi</a>
            <a class="<?= in_array($section, ['extras', 'extra-form'], true) ? 'active' : '' ?>" href="<?= base_url('admin/tambahan') ?>"><span>◇</span> Tambahan Dekorasi</a>
            <a class="<?= in_array($section, ['orders', 'order-detail'], true) ? 'active' : '' ?>" href="<?= base_url('admin/pesanan') ?>"><span>▤</span> Pesanan</a>
            <a class="<?= $section === 'customers' ? 'active' : '' ?>" href="<?= base_url('admin/pelanggan') ?>"><span>♙</span> Pelanggan</a>
            <a class="<?= $section === 'financial-reports' ? 'active' : '' ?>" href="<?= base_url('admin/laporan') ?>"><span>▥</span> Laporan & Keuangan</a>
            <a class="<?= in_array($section, ['shipping-zones', 'shipping-kecamatan-form', 'shipping-kelurahan-form'], true) ? 'active' : '' ?>" href="<?= base_url('admin/ongkos-kirim') ?>"><span>🗺</span> Aturan Ongkos Kirim</a>
        </nav>
        <div class="admin-sidebar-bottom">
            <div class="admin-user-info">
                <span class="admin-user-label">Masuk sebagai admin</span>
                <b class="admin-user-phone"><?= esc($adminPhone) ?></b>
            </div>

            <!-- Tombol Keluar dari Panel & Masuk ke Halaman Web Nauli Dekor -->
            <a class="admin-btn-exit-panel" href="<?= base_url('dashboard') ?>" title="Keluar dari panel dan lihat website Nauli Dekor">
                <span class="btn-icon">✦</span>
                <span class="btn-text">
                    <span class="btn-title">Keluar dari Panel</span>
                    <small class="btn-sub">Pantau Web Nauli Dekor ↗</small>
                </span>
            </a>

            <!-- Tombol Logout Sesi Admin -->
            <form method="post" action="<?= base_url('admin/logout') ?>" class="admin-logout-form">
                <?= csrf_field() ?>
                <button class="admin-logout-link" type="submit" title="Keluar dari akun admin">
                    <span>↪</span> Logout Akun Admin
                </button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" id="admin-menu-toggle" aria-label="Buka menu">☰</button>
            <div><p class="admin-eyebrow">NAULI DEKOR · ADMINISTRATOR</p><h1><?= esc($title) ?></h1></div>
            <span class="admin-topbar-tag">PANEL PENGELOLA</span>
        </header>

        <?php if ($successMessage): ?><div class="admin-alert success"><?= esc($successMessage) ?></div><?php endif; ?>
        <?php if ($errorMessage): ?><div class="admin-alert error"><?= esc($errorMessage) ?></div><?php endif; ?>
        <?php if ($warningMessage): ?><div class="admin-alert warning"><?= esc($warningMessage) ?></div><?php endif; ?>
        <?php if (is_array($errors) && $errors !== []): ?><div class="admin-alert error"><ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <div class="admin-content">
            <?php if ($section === 'dashboard'): ?>
                <?php $stats = is_array($stats ?? null) ? ($stats ?? []) : [];
                $recentOrders = is_array($recentOrders ?? null) ? ($recentOrders ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">RINGKASAN USAHA</p><h2>Ikhtisar Nauli Dekor</h2><p>Pantau aktivitas pesanan dan jadwal pengerjaan dekorasi secara langsung.</p></div></div>
                <div class="admin-stat-grid">
                    <article class="admin-stat">
                        <span>Status Proses</span>
                        <strong><?= (int) ($stats['proses'] ?? 0) ?></strong>
                        <small>Pesanan dalam proses dekorasi</small>
                    </article>
                    <article class="admin-stat">
                        <span>Status Selesai</span>
                        <strong><?= (int) ($stats['selesai'] ?? 0) ?></strong>
                        <small>Dekorasi telah diselesaikan</small>
                    </article>
                    <article class="admin-stat">
                        <span>Total Pesanan</span>
                        <strong><?= (int) ($stats['orders'] ?? 0) ?></strong>
                        <small>Seluruh pesanan yang tercatat</small>
                    </article>
                    <article class="admin-stat">
                        <span>Nilai Total Pesanan</span>
                        <strong class="stat-currency"><?= esc($currency($stats['orderValue'] ?? 0)) ?></strong>
                        <small>Total nilai seluruh pesanan</small>
                    </article>
                </div>

                <!-- Kalender Interaktif Pengerjaan Dekorasi (Langsung Tampak / Inline) -->
                <section class="admin-panel-card admin-calendar-card" id="admin-calendar-card">
                    <div class="admin-card-heading">
                        <div>
                            <p class="admin-eyebrow">AGENDA & JADWAL DEKORASI</p>
                            <h2>Kalender Pengerjaan Dekorasi</h2>
                            <p class="detail-note" style="margin-top:2px;">Pantau tenggat waktu pengerjaan dekorasi tiap tanggal. Klik tanggal untuk melihat daftar paket yang harus diselesaikan.</p>
                        </div>
                        <div class="cal-nav admin-cal-nav">
                            <button type="button" class="cal-nav-btn" id="admin-cal-prev" title="Bulan sebelumnya">&lt;</button>
                            <span class="cal-month-title" id="admin-cal-month-title">—</span>
                            <button type="button" class="cal-nav-btn" id="admin-cal-next" title="Bulan berikutnya">&gt;</button>
                        </div>
                    </div>

                    <div class="admin-cal-wrap">
                        <div class="cal-weekdays admin-cal-weekdays">
                            <span>Minggu</span><span>Senin</span><span>Selasa</span><span>Rabu</span><span>Kamis</span><span>Jumat</span><span>Sabtu</span>
                        </div>
                        <div class="cal-days-grid admin-cal-days-grid" id="admin-cal-days-grid"></div>
                    </div>

                    <div class="cal-legend admin-cal-legend">
                        <span class="cal-legend-item"><span class="status progress" style="font-size:11px; padding:3px 8px; border-radius:3px;">Diproses</span> Perlu Dikerjakan</span>
                        <span class="cal-legend-item"><span class="status good" style="font-size:11px; padding:3px 8px; border-radius:3px;">Selesai</span> Dekorasi Selesai</span>
                        <span class="cal-legend-item"><span class="status pending" style="font-size:11px; padding:3px 8px; border-radius:3px; border:1px solid #bd922b;">Hari Ini</span> Tanggal Hari Ini</span>
                        <span class="cal-legend-item"><span class="status muted" style="font-size:11px; padding:3px 8px; border-radius:3px;">Kosong</span> Bebas / Tidak Ada Jadwal</span>
                    </div>

                    <div class="cal-hover-note admin-cal-hover-note" id="admin-cal-hover-note">
                        Arahkan kursor atau klik tanggal untuk melihat rincian paket dekorasi yang harus diselesaikan.
                    </div>
                </section>

                <!-- Pop up Modal Rincian Paket Dekorasi per Tanggal Acara -->
                <div class="admin-schedule-modal" id="admin-schedule-modal" aria-hidden="true" role="dialog" aria-modal="true">
                    <div class="admin-schedule-backdrop" id="admin-schedule-backdrop"></div>
                    <div class="admin-schedule-card">
                        <div class="admin-schedule-header">
                            <div>
                                <p class="admin-eyebrow" style="margin:0 0 2px;">TENGGAT PENGERJAAN DEKORASI</p>
                                <h3 class="admin-schedule-title" id="admin-modal-title">Jadwal Acara</h3>
                            </div>
                            <button type="button" class="admin-schedule-close" id="admin-schedule-close" aria-label="Tutup">Tutup</button>
                        </div>
                        <div class="admin-schedule-body" id="admin-schedule-body">
                            <!-- Diisi dinamis oleh JavaScript -->
                        </div>
                    </div>
                </div>

                <script id="admin-calendar-data" type="application/json"><?= json_encode($calendarSchedule ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>

                <section class="admin-panel-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">AKTIVITAS TERBARU</p><h2>Pesanan terbaru</h2></div><a href="<?= base_url('admin/pesanan') ?>">Kelola pesanan →</a></div>
                    <?php if ($recentOrders === []): ?><p class="admin-empty">Belum ada pesanan di database.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kode pesanan</th><th>Pelanggan</th><th>Tanggal acara</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
                        <?php foreach ($recentOrders as $row): ?><tr><td><b><?= esc($row['kode_pesanan'] ?? '-') ?></b><small><?= esc($row['created_at'] ?? '-') ?></small></td><td><?= esc($row['nama_pemesan'] ?? $row['account_name'] ?? '-') ?></td><td><?= ! empty($row['tanggal_acara']) ? esc(date('d/m/Y', strtotime($row['tanggal_acara']))) : '-' ?></td><td><?= esc($currency($row['total'] ?? 0)) ?></td><td><span class="<?= esc($statusClass($row['status_pesanan'] ?? '')) ?>"><?= esc($row['status_pesanan'] ?? 'Pending') ?></span></td><td><a class="table-link" href="<?= base_url('admin/pesanan/' . (int) $row['id']) ?>">Detail</a></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>

            <?php elseif ($section === 'packages'): ?>
                <?php $rows = is_array($rows ?? null) ? ($rows ?? []) : [];
                $filters = is_array($filters ?? null) ? ($filters ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">KATALOG</p><h2>Daftar paket dekorasi</h2><p>Kelola nama, kategori, jenis, harga, dan ketersediaan paket.</p></div><a class="admin-button primary" href="<?= base_url('admin/paket/tambah') ?>">+ Tambah Paket</a></div>
                <form class="admin-filter-form" method="get" action="<?= base_url('admin/paket') ?>"><input type="search" name="q" placeholder="Cari nama atau lokasi paket" value="<?= esc($filters['q'] ?? '') ?>"><select name="kategori"><option value="">Semua kategori</option><option value="pernikahan" <?= ($filters['kategori'] ?? '') === 'pernikahan' ? 'selected' : '' ?>>Pernikahan</option><option value="duka" <?= ($filters['kategori'] ?? '') === 'duka' ? 'selected' : '' ?>>Rumah Duka</option></select><select name="jenis"><option value="">Semua jenis</option><?php foreach (['biasa' => 'Biasa', 'lengkap' => 'Lengkap', 'tambahan' => 'Tambahan'] as $key => $label): ?><option value="<?= esc($key) ?>" <?= ($filters['jenis'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select><button class="admin-button secondary" type="submit">Terapkan</button></form>
                <section class="admin-panel-card"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Nama paket</th><th>Kategori / jenis</th><th>Lokasi</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                <?php if ($rows === []): ?><tr><td colspan="6" class="admin-empty-cell">Tidak ada paket yang sesuai.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?><tr><td><b><?= esc($row['nama_paket'] ?? '-') ?></b><small>ID #<?= (int) $row['id'] ?></small></td><td><?= esc(ucfirst((string) ($row['kategori'] ?? '-'))) ?><small><?= esc(ucfirst((string) ($row['jenis_paket'] ?? '-'))) ?></small></td><td><?= esc($row['lokasi'] ?? '-') ?></td><td><?= esc($currency($row['harga'] ?? 0)) ?></td><td><span class="<?= (int) ($row['aktif'] ?? 0) === 1 ? 'status good' : 'status muted' ?>"><?= (int) ($row['aktif'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></span></td><td class="admin-actions"><a class="table-link" href="<?= base_url('admin/paket/' . (int) $row['id'] . '/edit') ?>">Ubah</a><form method="post" action="<?= base_url('admin/paket/' . (int) $row['id'] . '/status') ?>"><?= csrf_field() ?>><button class="table-button" type="submit"><?= (int) ($row['aktif'] ?? 0) === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr><?php endforeach; ?>
                </tbody></table></div></section>

            <?php elseif ($section === 'package-form'): ?>
                <?php $item = is_array($item ?? null) ? ($item ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">KATALOG</p><h2><?= ! empty($isEdit) ? 'Perbarui informasi paket' : 'Informasi paket baru' ?></h2><p>Harga dan informasi di sini akan digunakan oleh katalog pelanggan.</p></div><a class="admin-button secondary" href="<?= base_url('admin/paket') ?>">← Kembali</a></div>
                <form class="admin-panel-card admin-data-form" method="post" enctype="multipart/form-data" action="<?= esc($formAction ?? '#', 'attr') ?>"><?= csrf_field() ?>
                    <?php $packageImageName = basename((string) ($item['gambar'] ?? '')); $packageImageExists = $packageImageName !== '' && is_file(FCPATH . 'assets/images/' . $packageImageName); ?>
                    <div class="admin-form-grid"><label>Nama paket<input name="nama_paket" maxlength="180" required value="<?= esc(old('nama_paket', $item['nama_paket'] ?? '')) ?>"></label><label>Lokasi / cakupan<input name="lokasi" maxlength="120" required value="<?= esc(old('lokasi', $item['lokasi'] ?? '')) ?>"></label>
                    <label>Kategori<select name="kategori" id="package-category" required><option value="pernikahan" <?= old('kategori', $item['kategori'] ?? 'pernikahan') === 'pernikahan' ? 'selected' : '' ?>>Pernikahan</option><option value="duka" <?= old('kategori', $item['kategori'] ?? '') === 'duka' ? 'selected' : '' ?>>Rumah Duka</option></select></label>
                    <label>Jenis paket<select name="jenis_paket" id="package-type" required><?php foreach (['biasa' => 'Biasa', 'lengkap' => 'Lengkap', 'tambahan' => 'Tambahan'] as $key => $label): ?><option value="<?= esc($key) ?>" <?= old('jenis_paket', $item['jenis_paket'] ?? 'biasa') === $key ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select><small>Rumah Duka menggunakan jenis Biasa atau Tambahan.</small></label>
                    <label>Harga (Rp)<input type="number" name="harga" min="0" step="1000" required value="<?= esc(old('harga', $item['harga'] ?? '')) ?>"></label>
                    <div class="admin-image-upload form-span-two">
                        <label for="package-image-file">Upload gambar paket</label>
                        <input class="admin-image-input" id="package-image-file" type="file" name="gambar_file" accept="image/jpeg,image/png,image/webp" <?= (! $isEdit || ! $packageImageExists) ? 'required' : '' ?> data-preview-target="package-image-preview">
                        <small>Format JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB. Gambar ini akan tampil pada katalog dan detail paket.</small>
                        <div class="admin-image-preview" id="package-image-preview">
                            <?php if ($packageImageExists): ?>
                                <img src="<?= esc(base_url('assets/images/' . rawurlencode($packageImageName)), 'attr') ?>" alt="Gambar paket saat ini">
                            <?php else: ?>
                                <span>Belum ada gambar. Pilih file untuk melihat preview.</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($packageImageExists): ?><small>Gambar saat ini: <?= esc($packageImageName) ?>. Biarkan kosong untuk mempertahankan gambar ini.</small><?php endif; ?>
                    </div>
                    <label class="form-span-two">Deskripsi<textarea name="deskripsi" rows="4"><?= esc(old('deskripsi', $item['deskripsi'] ?? '')) ?></textarea></label>
                    <label>Status<select name="aktif"><option value="1" <?= (string) old('aktif', $item['aktif'] ?? 1) === '1' ? 'selected' : '' ?>>Aktif</option><option value="0" <?= (string) old('aktif', $item['aktif'] ?? 1) === '0' ? 'selected' : '' ?>>Nonaktif</option></select></label></div>
                    <div class="admin-form-actions"><button class="admin-button primary" type="submit">Simpan Paket</button><a class="admin-button secondary" href="<?= base_url('admin/paket') ?>">Batal</a></div>
                </form>

            <?php elseif ($section === 'extras'): ?>
                <?php $rows = is_array($rows ?? null) ? ($rows ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">KATALOG TAMBAHAN</p><h2>Daftar tambahan dekorasi</h2><p>Atur harga, stok, deskripsi, dan ketersediaan item tambahan.</p></div><a class="admin-button primary" href="<?= base_url('admin/tambahan/tambah') ?>">+ Tambah Item</a></div>
                <form class="admin-filter-form" method="get" action="<?= base_url('admin/tambahan') ?>"><input type="search" name="q" placeholder="Cari tambahan dekorasi" value="<?= esc($keyword ?? '') ?>"><button class="admin-button secondary" type="submit">Cari</button></form>
                <section class="admin-panel-card"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Nama tambahan</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                <?php if ($rows === []): ?><tr><td colspan="5" class="admin-empty-cell">Belum ada tambahan dekorasi.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?><tr><td><b><?= esc($row['nama'] ?? '-') ?></b><small><?= esc(mb_strimwidth((string) ($row['deskripsi'] ?? ''), 0, 90, '…')) ?></small></td><td><?= esc($currency($row['harga'] ?? 0)) ?></td><td><?= (int) ($row['stok'] ?? 0) ?></td><td><span class="<?= (int) ($row['aktif'] ?? 0) === 1 ? 'status good' : 'status muted' ?>"><?= (int) ($row['aktif'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></span></td><td class="admin-actions"><a class="table-link" href="<?= base_url('admin/tambahan/' . (int) $row['id'] . '/edit') ?>">Ubah</a><form method="post" action="<?= base_url('admin/tambahan/' . (int) $row['id'] . '/status') ?>"><?= csrf_field() ?>><button class="table-button" type="submit"><?= (int) ($row['aktif'] ?? 0) === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr><?php endforeach; ?>
                </tbody></table></div></section>

            <?php elseif ($section === 'extra-form'): ?>
                <?php $item = is_array($item ?? null) ? ($item ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">KATALOG TAMBAHAN</p><h2><?= ! empty($isEdit) ? 'Perbarui tambahan dekorasi' : 'Tambahan dekorasi baru' ?></h2><p>Perubahan harga dan stok akan digunakan pada pemilihan item berikutnya.</p></div><a class="admin-button secondary" href="<?= base_url('admin/tambahan') ?>">← Kembali</a></div>
                <form class="admin-panel-card admin-data-form" method="post" enctype="multipart/form-data" action="<?= esc($formAction ?? '#', 'attr') ?>"><?= csrf_field() ?>
                    <?php $extraImageName = basename((string) ($item['gambar'] ?? '')); $extraImageExists = $extraImageName !== '' && is_file(FCPATH . 'assets/images/' . $extraImageName); ?>
                    <div class="admin-form-grid"><label>Nama tambahan<input name="nama" maxlength="120" required value="<?= esc(old('nama', $item['nama'] ?? '')) ?>"></label><label>Harga (Rp)<input type="number" name="harga" min="0" step="1000" required value="<?= esc(old('harga', $item['harga'] ?? '')) ?>"></label><label>Stok tersedia<input type="number" name="stok" min="0" step="1" required value="<?= esc(old('stok', $item['stok'] ?? 0)) ?>"></label>
                    <div class="admin-image-upload form-span-two">
                        <label for="extra-image-file">Upload gambar tambahan dekorasi</label>
                        <input class="admin-image-input" id="extra-image-file" type="file" name="gambar_file" accept="image/jpeg,image/png,image/webp" <?= (! $isEdit || ! $extraImageExists) ? 'required' : '' ?> data-preview-target="extra-image-preview">
                        <small>Format JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB. Gambar ini akan tampil pada halaman tambahan dekorasi.</small>
                        <div class="admin-image-preview" id="extra-image-preview">
                            <?php if ($extraImageExists): ?>
                                <img src="<?= esc(base_url('assets/images/' . rawurlencode($extraImageName)), 'attr') ?>" alt="Gambar tambahan saat ini">
                            <?php else: ?>
                                <span>Belum ada gambar. Pilih file untuk melihat preview.</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($extraImageExists): ?><small>Gambar saat ini: <?= esc($extraImageName) ?>. Biarkan kosong untuk mempertahankan gambar ini.</small><?php endif; ?>
                    </div>
                    <label class="form-span-two">Deskripsi<textarea name="deskripsi" rows="4"><?= esc(old('deskripsi', $item['deskripsi'] ?? '')) ?></textarea></label><label>Status<select name="aktif"><option value="1" <?= (string) old('aktif', $item['aktif'] ?? 1) === '1' ? 'selected' : '' ?>>Aktif</option><option value="0" <?= (string) old('aktif', $item['aktif'] ?? 1) === '0' ? 'selected' : '' ?>>Nonaktif</option></select></label></div>
                    <div class="admin-form-actions"><button class="admin-button primary" type="submit">Simpan Tambahan</button><a class="admin-button secondary" href="<?= base_url('admin/tambahan') ?>">Batal</a></div>
                </form>

            <?php elseif ($section === 'orders'): ?>
                <?php $rows = is_array($rows ?? null) ? ($rows ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">TRANSAKSI</p><h2>Semua pesanan</h2><p>Periksa data pemesan, jadwal acara, dan status transaksi.</p></div></div>
                <form class="admin-filter-form" method="get" action="<?= base_url('admin/pesanan') ?>"><input type="search" name="q" placeholder="Cari kode, nama, atau WhatsApp" value="<?= esc($keyword ?? '') ?>"><select name="status"><option value="">Semua status</option><?php foreach (['Pending', 'Diproses', 'Selesai', 'Dibatalkan'] as $value): ?><option value="<?= esc($value) ?>" <?= ($statusFilter ?? '') === $value ? 'selected' : '' ?>><?= esc($value) ?></option><?php endforeach; ?></select><button class="admin-button secondary" type="submit">Terapkan</button></form>
                <?php
                $payLabel = static function(string $bayar, string $pesanan): string {
                    if (in_array($bayar, ['DP', 'Lunas'], true)) return 'Pembayaran Berhasil';
                    if ($bayar === 'Ditolak')                      return 'Pembayaran Gagal';
                    if ($bayar === 'Menunggu Verifikasi')           return 'Menunggu Verifikasi';
                    if ($pesanan === 'Selesai')                     return 'Selesai';
                    if ($pesanan === 'Dibatalkan')                  return 'Dibatalkan';
                    return 'Belum Bayar';
                };
                $payLabelClass = static function(string $bayar, string $pesanan): string {
                    if (in_array($bayar, ['DP', 'Lunas'], true)) return 'status good';
                    if ($bayar === 'Ditolak')                      return 'status muted';
                    if ($bayar === 'Menunggu Verifikasi')           return 'status progress';
                    if ($pesanan === 'Selesai')                     return 'status good';
                    if ($pesanan === 'Dibatalkan')                  return 'status muted';
                    return 'status pending';
                };
                ?>
                <section class="admin-panel-card"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kode pesanan</th><th>Pemesan</th><th>Acara</th><th>Total</th><th>Status Pembayaran</th><th>Status Dekorasi</th><th>Aksi</th></tr></thead><tbody>
                <?php if ($rows === []): ?><tr><td colspan="7" class="admin-empty-cell">Belum ada pesanan yang sesuai.</td></tr><?php endif; ?>
<?php foreach ($rows as $row): ?><tr><td><b><?= esc($row['kode_pesanan'] ?? '-') ?></b><small><?= esc($row['created_at'] ?? '-') ?></small></td><td><?= esc($row['nama_pemesan'] ?? '-') ?><small><?= esc($row['no_whatsapp'] ?? '-') ?></small></td><td><?= ! empty($row['tanggal_acara']) ? esc(date('d/m/Y', strtotime($row['tanggal_acara']))) : '-' ?></td><td><?= esc($currency($row['total'] ?? 0)) ?></td><td><span class="<?= esc($payLabelClass((string)($row['status_pembayaran'] ?? ''), (string)($row['status_pesanan'] ?? ''))) ?>"><?= esc($payLabel((string)($row['status_pembayaran'] ?? ''), (string)($row['status_pesanan'] ?? ''))) ?></span></td><td><span class="<?= esc($dekorClass((string)($row['status_dekorasi'] ?? ''))) ?>"><?= esc($dekorLabel((string)($row['status_dekorasi'] ?? ''))) ?></span></td><td><div style="display:flex; flex-direction:column; align-items:flex-start; gap:6px;"><?php if ((string)($row['status_dekorasi'] ?? '') === 'Diproses'): ?><form method="post" action="<?= base_url('admin/pesanan/' . (int) $row['id'] . '/tandai-selesai') ?>" style="margin:0;"><?= csrf_field() ?><button type="submit" onclick="return confirm('Tandai pesanan ini sebagai Selesai?')" style="font-family:inherit; font-size:12px; font-weight:600; color:#8c6818; background:#fbf6eb; border:1px solid #bd922b; border-radius:4px; padding:4px 10px; cursor:pointer; white-space:nowrap; line-height:1.3;">Tandai Selesai</button></form><?php endif; ?><a class="table-link" href="<?= base_url('admin/pesanan/' . (int) $row['id']) ?>">Detail</a></div></td></tr><?php endforeach; ?>
            <?php elseif ($section === 'order-detail'): ?>
                <?php $order = is_array($order ?? null) ? ($order ?? []) : [];
                $orderExtras = is_array($orderExtras ?? null) ? ($orderExtras ?? []) : []; $total = (float) ($order['total'] ?? 0); $plannedDp = (float) ($order['jumlah_dp'] ?? 0); $packagePrice = (float) ($order['display_package_price'] ?? $order['package_price'] ?? 0); $extrasTotal = 0.0; foreach ($orderExtras as $extra) { $extrasTotal += (float) ($extra['harga'] ?? 0) * (int) ($extra['jumlah'] ?? 0); } ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">DETAIL TRANSAKSI</p><h2><?= esc($order['kode_pesanan'] ?? '-') ?></h2><p>Pesanan dibuat pada <?= ! empty($order['created_at']) ? esc(date('d/m/Y H:i', strtotime($order['created_at']))) : '-' ?>.</p></div><a class="admin-button secondary" href="<?= base_url('admin/pesanan') ?>">← Kembali</a></div>
                <div class="admin-detail-grid">
                    <section class="admin-panel-card">
                        <p class="admin-eyebrow">INFORMASI PEMESAN</p>
                        <h3><?= esc($order['nama_pemesan'] ?? '-') ?></h3>
                        <p><?= esc($order['no_whatsapp'] ?? '-') ?><br><?= esc($order['email'] ?? '') ?></p>
                        <p class="detail-label">Tanggal acara</p>
                        <b><?= ! empty($order['tanggal_acara']) ? esc(date('d/m/Y', strtotime($order['tanggal_acara']))) : '-' ?></b>
                        <?php if (! empty($order['nama_kecamatan']) || ! empty($order['nama_kelurahan'])): ?>
                            <p class="detail-label">Wilayah (Kecamatan / Kelurahan)</p>
                            <p><b><?= esc($order['nama_kecamatan'] ?? '-') ?></b> - <?= esc($order['nama_kelurahan'] ?? '-') ?></p>
                        <?php endif; ?>
                        <p class="detail-label">Lokasi acara</p>
                        <p><?= nl2br(esc($order['lokasi_acara'] ?? '-')) ?></p>

                        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--admin-line);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <p class="detail-label" style="margin: 0;">Status Dekorasi</p>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="<?= esc($dekorClass((string)($order['status_dekorasi'] ?? ''))) ?>"><?= esc($dekorLabel((string)($order['status_dekorasi'] ?? ''))) ?></span>
<?php if ((string)($order['status_dekorasi'] ?? '') === 'Diproses'): ?>
    <form method="post" action="<?= base_url('admin/pesanan/' . (int) $order['id'] . '/tandai-selesai') ?>" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" onclick="return confirm('Tandai dekorasi pesanan ini sebagai Selesai?')" style="font-family:inherit; font-size:12px; font-weight:600; color:#8c6818; background:#fbf6eb; border:1px solid #bd922b; border-radius:4px; padding:4px 10px; cursor:pointer; white-space:nowrap; line-height:1.3;">Tandai Selesai</button>
    </form>
<?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-verify-actions" style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--admin-line);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <p class="detail-label" style="margin: 0;">Verifikasi Pembayaran</p>
                                <span class="<?= esc($statusClass($order['status_pembayaran'] ?? '')) ?>"><?= esc($order['status_pembayaran'] ?? 'Pending') ?></span>
                            </div>
                            <?php
                            $payStatus = (string) ($order['status_pembayaran'] ?? '');
                            $sudahDiverifikasi = ! empty($order['diverifikasi_pada']) && in_array($payStatus, ['DP', 'Lunas', 'Ditolak'], true);
                            $canVerify = $payStatus === 'Menunggu Verifikasi' && ! empty($order['bukti_pembayaran']);
                            ?>
                            <?php if ($sudahDiverifikasi): ?>
                                <?php if (in_array($payStatus, ['DP', 'Lunas'], true)): ?>
                                    <p class="detail-note" style="margin-top:4px; padding:10px 14px; background:#f0f7ee; border-radius:6px; border-left:3px solid #4f7246;">
                                        <span style="color:#4f7246; font-weight:600;">Pembayaran telah diverifikasi pada <?= esc(date('d/m/Y H:i', strtotime((string) $order['diverifikasi_pada']))) ?>.</span>
                                    </p>
                                <?php elseif ($payStatus === 'Ditolak'): ?>
                                    <p class="detail-note" style="margin-top:4px; padding:10px 14px; background:#fdf0ee; border-radius:6px; border-left:3px solid #a64a3e;">
                                        <span style="color:#a64a3e; font-weight:600;">Verifikasi gagal pada <?= esc(date('d/m/Y H:i', strtotime((string) $order['diverifikasi_pada']))) ?>.</span><?= ! empty($order['catatan_verifikasi']) ? ' Catatan: ' . esc($order['catatan_verifikasi']) : '' ?>
                                    </p>
                                <?php endif; ?>
                            <?php elseif ($canVerify): ?>
                                <form method="post" action="<?= base_url('admin/pesanan/' . (int) $order['id'] . '/verifikasi') ?>">
                                    <?= csrf_field() ?>
                                    <textarea name="catatan_verifikasi" maxlength="500"
                                        placeholder="Catatan (opsional — wajib diisi jika menolak)"
                                        style="width:100%; padding:9px 12px; border:1px solid #d5cbba; border-radius:4px; font-size:13px; font-family:inherit; resize:vertical; min-height:60px; margin-bottom:10px; box-sizing:border-box; background:#fff;"></textarea>
                                    <div style="display:flex; gap:10px;">
                                        <button class="admin-button primary" type="submit" name="keputusan_verifikasi" value="approve"
                                            style="flex:1; background:#bd922b; border-color:#bd922b; color:#fff;">
                                            Pembayaran Berhasil
                                        </button>
                                        <button class="admin-button secondary" type="submit" name="keputusan_verifikasi" value="reject"
                                            style="flex:1; background:#fff8ec; color:#9e781e; border-color:#bd922b;">
                                            Pembayaran Gagal
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="detail-note" style="margin-top:6px; padding:10px 14px; background:#fbf9f5; border-radius:6px; border:1px dashed #d5cbba; color:#6e655f;">
                                    Menunggu pelanggan mengunggah bukti pembayaran.
                                </p>
                            <?php endif; ?>
                        </div>
                    </section>
                    <section class="admin-panel-card">
                        <p class="admin-eyebrow">RINGKASAN BIAYA</p>
                        <div class="admin-detail-line"><span><?= esc($order['display_package_name'] ?? $order['package_name'] ?? 'Paket dekorasi') ?></span><b><?= esc($currency($packagePrice)) ?></b></div>
                        <?php foreach ($orderExtras as $extra): ?>
                            <div class="admin-detail-line"><span><?= esc($extra['nama'] ?? '-') ?> × <?= (int) ($extra['jumlah'] ?? 0) ?></span><b><?= esc($currency((float) ($extra['harga'] ?? 0) * (int) ($extra['jumlah'] ?? 0))) ?></b></div>
                        <?php endforeach; ?>
                        <?php if (! empty($order['ongkos_kirim']) && (float) $order['ongkos_kirim'] > 0): ?>
                            <div class="admin-detail-line"><span>Ongkos Kirim (<?= esc($order['nama_kecamatan'] ?? '') ?><?= ! empty($order['nama_kelurahan']) ? ', ' . esc($order['nama_kelurahan']) : '' ?>)</span><b><?= esc($currency($order['ongkos_kirim'])) ?></b></div>
                        <?php endif; ?>
                        <div class="admin-detail-total"><span>Total pesanan</span><b><?= esc($currency($total)) ?></b></div>
                        <p class="detail-label">Rencana pembayaran awal</p>
                        <b><?= esc($currency($plannedDp)) ?></b>
                        <p class="detail-note">Skema: <strong><?= esc($order['metode_pembayaran'] ?? 'DP') ?></strong> · Kanal: <strong><?= esc($order['kanal_pembayaran'] ?? 'QRIS') ?></strong></p>

                        <?php if (! empty($order['bukti_pembayaran'])): ?>
                            <?php
                            $proofFile = (string) $order['bukti_pembayaran'];
                            $isPdf = strtolower(pathinfo($proofFile, PATHINFO_EXTENSION)) === 'pdf';
                            $proofUrl = base_url('admin/pesanan/' . (int) $order['id'] . '/bukti');
                            ?>
                            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--admin-line);">
                                <p class="detail-label" style="margin-bottom:8px;">Bukti Pembayaran</p>
                                <p class="detail-note" style="margin-bottom:10px;">Diunggah pada <?= ! empty($order['bukti_diupload_pada']) ? esc(date('d/m/Y H:i', strtotime($order['bukti_diupload_pada']))) : '-' ?><?= $isPdf ? '' : ' · Klik gambar untuk zoom' ?>.</p>
                                <?php if ($isPdf): ?>
                                    <a class="admin-button secondary" href="<?= $proofUrl ?>" target="_blank" rel="noopener">📄 Buka PDF Bukti Pembayaran</a>
                                <?php else: ?>
                                    <div class="admin-proof-box">
                                        <div class="admin-proof-clickable" data-open-proof="<?= esc($proofUrl, 'attr') ?>" title="Klik untuk zoom">
                                            <img src="<?= esc($proofUrl, 'attr') ?>" alt="Bukti Pembayaran #<?= esc($order['kode_pesanan'] ?? '-') ?>" class="admin-proof-thumb">
                                            <span class="admin-proof-badge">🔍 Klik untuk Zoom</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

            <?php elseif ($section === 'financial-reports'): ?>
                <?php
                $viewScope = get_defined_vars();
$report = isset($viewScope['report']) && is_array($viewScope['report']) ? $viewScope['report'] : [];
unset($viewScope);
                $filters = is_array($report['filters'] ?? null) ? $report['filters'] : [];
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
                $transactions = is_array($report['transactions'] ?? null) ? $report['transactions'] : [];
                $expenseRows = is_array($report['expenseRows'] ?? null) ? $report['expenseRows'] : [];
                $ordersForExpense = is_array($report['ordersForExpense'] ?? null) ? $report['ordersForExpense'] : [];
                $methodRows = is_array($report['methodRows'] ?? null) ? $report['methodRows'] : [];
                $chart = is_array($report['chart'] ?? null) ? $report['chart'] : [];
                $chartMax = max(1, (float) ($report['chartMax'] ?? 0));
                $filterQuery = is_array($report['filterQuery'] ?? null) ? $report['filterQuery'] : [];
                ?>
                <div class="admin-page-intro">
                    <div><p class="admin-eyebrow">KEUANGAN USAHA</p><h2>Laporan dan Keuangan</h2><p>Rekap transaksi, penerimaan terverifikasi, harga pokok perolehan, dan biaya aktual usaha.</p></div>
                    <a class="admin-button primary" href="<?= base_url('admin/laporan/pdf?' . http_build_query($filterQuery)) ?>" target="_blank" rel="noopener">▣ Export / Cetak PDF</a>
                </div>

                <section class="finance-period-note">
                    <strong>Periode laporan: <?= esc(date('d/m/Y', strtotime((string) ($filters['tanggal_awal_hasil'] ?? date('Y-m-01'))))) ?> – <?= esc(date('d/m/Y', strtotime((string) ($filters['tanggal_akhir_hasil'] ?? date('Y-m-d'))))) ?></strong>
                    <span>Pendapatan hanya menghitung pembayaran yang sudah diverifikasi admin. Filter metode dan status berlaku untuk tabel transaksi dan pendapatan; biaya dihitung berdasarkan tanggal biaya.</span>
                </section>

                <div class="finance-kpi-grid">
                    <article class="admin-stat"><span>Total Transaksi</span><strong><?= number_format((int) ($summary['transactionCount'] ?? 0), 0, ',', '.') ?></strong><small>Jumlah pesanan unik dalam hasil filter</small></article>
                    <article class="admin-stat"><span>Total Pendapatan Terverifikasi</span><strong class="stat-currency"><?= esc($currency($summary['revenue'] ?? 0)) ?></strong><small><?= (int) ($summary['verifiedPaymentCount'] ?? 0) ?> catatan pembayaran terverifikasi</small></article>
                    <article class="admin-stat"><span>Total Harga Perolehan / HPP</span><strong class="stat-currency"><?= esc($currency($summary['hpp'] ?? 0)) ?></strong><small>Biaya langsung yang dicatat pada pesanan</small></article>
                    <article class="admin-stat"><span>Keuntungan Kotor</span><strong class="stat-currency"><?= esc($currency($summary['grossProfit'] ?? 0)) ?></strong><small>Pendapatan terverifikasi dikurangi HPP</small></article>
                    <article class="admin-stat"><span>Biaya Aktual Usaha</span><strong class="stat-currency"><?= esc($currency($summary['totalExpenses'] ?? 0)) ?></strong><small>Akumulasi HPP dan biaya operasional</small></article>
                    <article class="admin-stat"><span>Hasil Bersih Berbasis Kas</span><strong class="stat-currency"><?= esc($currency($summary['netCashResult'] ?? 0)) ?></strong><small>Pendapatan dikurangi HPP dan biaya operasional</small></article>
                </div>

                <section class="admin-panel-card finance-filter-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">FILTER LAPORAN</p><h2>Atur periode dan transaksi</h2></div></div>
                    <form class="finance-filter-grid" method="get" action="<?= base_url('admin/laporan') ?>">
                        <label>Periode laporan<select name="periode" id="finance-period"><option value="harian" <?= ($filters['periode'] ?? '') === 'harian' ? 'selected' : '' ?>>Harian</option><option value="bulanan" <?= ($filters['periode'] ?? 'bulanan') === 'bulanan' ? 'selected' : '' ?>>Bulanan</option><option value="tahunan" <?= ($filters['periode'] ?? '') === 'tahunan' ? 'selected' : '' ?>>Tahunan</option><option value="custom" <?= ($filters['periode'] ?? '') === 'custom' ? 'selected' : '' ?>>Rentang tanggal</option></select></label>
                        <label>Tanggal (harian)<input type="date" name="tanggal" value="<?= esc($filters['tanggal'] ?? date('Y-m-d')) ?>"></label>
                        <label>Bulan<input type="month" name="bulan" value="<?= esc($filters['bulan'] ?? date('Y-m')) ?>"></label>
                        <label>Tahun<input type="number" name="tahun" min="2000" max="2100" value="<?= (int) ($filters['tahun'] ?? date('Y')) ?>"></label>
                        <label>Dari tanggal<input type="date" name="tanggal_mulai" value="<?= esc($filters['tanggal_mulai'] ?? date('Y-m-01')) ?>"></label>
                        <label>Sampai tanggal<input type="date" name="tanggal_selesai" value="<?= esc($filters['tanggal_selesai'] ?? date('Y-m-d')) ?>"></label>
                        <label>Metode pembayaran<select name="kanal"><option value="">Semua metode</option><?php foreach (['QRIS', 'Transfer Bank', 'Bayar di Tempat Acara', 'Tidak diketahui'] as $value): ?><option value="<?= esc($value) ?>" <?= ($filters['kanal'] ?? '') === $value ? 'selected' : '' ?>><?= esc($value) ?></option><?php endforeach; ?></select></label>
                        <label>Status transaksi<select name="status_transaksi"><option value="">Semua status</option><?php foreach (['Terverifikasi', 'Belum Bayar', 'Menunggu Verifikasi', 'Ditolak', 'Menunggu Pembayaran di Tempat'] as $value): ?><option value="<?= esc($value) ?>" <?= ($filters['status_transaksi'] ?? '') === $value ? 'selected' : '' ?>><?= esc($value) ?></option><?php endforeach; ?></select></label>
                        <div class="finance-filter-actions"><button class="admin-button primary" type="submit">Terapkan Filter</button><a class="admin-button secondary" href="<?= base_url('admin/laporan') ?>">Reset</a></div>
                    </form>
                </section>

                <div class="finance-analysis-grid">
                    <section class="admin-panel-card">
                        <div class="admin-card-heading"><div><p class="admin-eyebrow">TREN KEUANGAN</p><h2>Pendapatan, HPP, dan biaya usaha</h2></div></div>
                        <?php if ($chart === []): ?><p class="admin-empty">Belum ada data untuk grafik periode ini.</p><?php else: ?>
                            <div class="finance-chart-legend"><span><i class="revenue"></i>Pendapatan</span><span><i class="hpp"></i>HPP</span><span><i class="operational"></i>Operasional</span></div>
                            <div class="finance-chart">
                                <?php foreach ($chart as $point): ?>
                                    <div class="finance-chart-column"><div class="finance-chart-bars">
                                        <span class="finance-bar revenue" style="height:<?= max(2, ((float) $point['revenue'] / $chartMax) * 100) ?>%" title="Pendapatan: <?= esc($currency($point['revenue'])) ?>"></span>
                                        <span class="finance-bar hpp" style="height:<?= max(2, ((float) $point['hpp'] / $chartMax) * 100) ?>%" title="HPP: <?= esc($currency($point['hpp'])) ?>"></span>
                                        <span class="finance-bar operational" style="height:<?= max(2, ((float) $point['operational'] / $chartMax) * 100) ?>%" title="Operasional: <?= esc($currency($point['operational'])) ?>"></span>
                                    </div><small><?= esc($point['label']) ?></small></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                    <section class="admin-panel-card">
                        <div class="admin-card-heading"><div><p class="admin-eyebrow">KANAL PEMBAYARAN</p><h2>Pendapatan per metode</h2></div></div>
                        <?php if ($methodRows === []): ?><p class="admin-empty">Belum ada catatan transaksi pada filter ini.</p><?php else: ?>
                            <?php $methodMax = 1.0; foreach ($methodRows as $methodRow) $methodMax = max($methodMax, (float) ($methodRow['verified_amount'] ?? 0)); ?>
                            <div class="finance-method-list"><?php foreach ($methodRows as $methodRow): ?>
                                <div class="finance-method-item"><div><b><?= esc($methodRow['metode'] ?: 'Tidak diketahui') ?></b><strong><?= esc($currency($methodRow['verified_amount'] ?? 0)) ?></strong></div><div class="finance-method-track"><span style="width:<?= min(100, ((float) ($methodRow['verified_amount'] ?? 0) / $methodMax) * 100) ?>%"></span></div><small><?= (int) ($methodRow['record_count'] ?? 0) ?> catatan pembayaran</small></div>
                            <?php endforeach; ?></div>
                        <?php endif; ?>
                    </section>
                </div>

                <section class="admin-panel-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">BUKU TRANSAKSI</p><h2>Rincian pembayaran</h2><p class="finance-card-subtitle">Baris pembayaran awal berasal dari orders; pelunasan berasal dari order_payments. Jumlah pendapatan hanya memasukkan status Terverifikasi. Nominal pada baris yang belum terverifikasi adalah nilai pengajuan/tagihan, bukan uang yang sudah diterima.</p></div><span class="admin-topbar-tag"><?= count($transactions) ?> baris ditampilkan</span></div>
                    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tanggal</th><th>Kode pesanan</th><th>Pemesan</th><th>Jenis</th><th>Metode</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
                    <?php if ($transactions === []): ?><tr><td colspan="7" class="admin-empty-cell">Tidak ada transaksi pada filter yang dipilih.</td></tr><?php endif; ?>
                    <?php foreach ($transactions as $transaction): ?><tr><td><?= ! empty($transaction['tanggal_transaksi']) ? esc(date('d/m/Y H:i', strtotime($transaction['tanggal_transaksi']))) : '-' ?></td><td><a class="table-link" href="<?= base_url('admin/pesanan/' . (int) $transaction['order_id']) ?>"><?= esc($transaction['kode_pesanan'] ?? '-') ?></a></td><td><?= esc($transaction['nama_pemesan'] ?? '-') ?></td><td><?= esc($transaction['jenis_transaksi'] ?? '-') ?></td><td><?= esc($transaction['metode'] ?? '-') ?></td><td><?= esc($currency($transaction['jumlah'] ?? 0)) ?></td><td><span class="<?= esc($statusClass($transaction['status_transaksi'] ?? '')) ?>"><?= esc($transaction['status_transaksi'] ?? '-') ?></span></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                    <?php if (count($transactions) >= 500): ?><p class="detail-note">Tabel menampilkan maksimal 500 baris terbaru. Ringkasan nilai tetap dihitung dari seluruh data yang sesuai filter.</p><?php endif; ?>
                </section>

                <section class="admin-panel-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">PENCATATAN BIAYA AKTUAL</p><h2>Catat harga perolehan dan biaya usaha</h2><p class="finance-card-subtitle">HPP wajib dikaitkan dengan pesanan. Biaya operasional dapat dicatat tanpa pesanan.</p></div></div>
                    <form class="finance-expense-form" method="post" action="<?= base_url('admin/laporan/biaya') ?>">
                        <?= csrf_field() ?>
                        <label>Jenis biaya<select name="jenis_biaya" id="expense-kind" required><option value="HPP">Harga Perolehan / HPP</option><option value="Operasional">Biaya Operasional</option></select></label>
                        <label>Kategori<select name="kategori" required><?php foreach (['Bunga dan Bahan Dekorasi', 'Tenaga Kerja dan Pemasangan', 'Transportasi dan Pengiriman', 'Sewa Perlengkapan', 'Operasional Lainnya'] as $value): ?><option value="<?= esc($value) ?>"><?= esc($value) ?></option><?php endforeach; ?></select></label>
                        <label class="finance-order-field">Pesanan terkait<select name="order_id" id="expense-order"><option value="">Pilih pesanan</option><?php foreach ($ordersForExpense as $expenseOrder): ?><option value="<?= (int) $expenseOrder['id'] ?>"><?= esc(($expenseOrder['kode_pesanan'] ?? ('#' . $expenseOrder['id'])) . ' · ' . ($expenseOrder['nama_pemesan'] ?? '')) ?></option><?php endforeach; ?></select><small>Wajib dipilih untuk biaya HPP.</small></label>
                        <label>Jumlah biaya (Rp)<input type="number" name="jumlah" min="0.01" step="0.01" required placeholder="Contoh: 1250000"></label>
                        <label>Tanggal biaya<input type="date" name="tanggal_biaya" value="<?= date('Y-m-d') ?>" required></label>
                        <label class="finance-description-field">Keterangan<input type="text" name="deskripsi" maxlength="255" minlength="3" required placeholder="Contoh: pembelian bunga segar untuk acara"></label>
                        <div class="finance-expense-actions"><button class="admin-button primary" type="submit">+ Simpan Biaya</button></div>
                    </form>
                </section>

                <section class="admin-panel-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">BUKU PENGELUARAN</p><h2>Riwayat harga perolehan dan biaya aktual</h2></div></div>
                    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tanggal</th><th>Jenis / kategori</th><th>Pesanan</th><th>Keterangan</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($expenseRows === []): ?><tr><td colspan="7" class="admin-empty-cell">Belum ada biaya yang dicatat pada periode ini.</td></tr><?php endif; ?>
                    <?php foreach ($expenseRows as $expense): ?><tr><td><?= esc(date('d/m/Y', strtotime($expense['tanggal_biaya']))) ?></td><td><b><?= esc($expense['jenis_biaya']) ?></b><small><?= esc($expense['kategori']) ?></small></td><td><?= esc($expense['kode_pesanan'] ?? '-') ?></td><td><?= esc($expense['deskripsi']) ?><?php if (($expense['status'] ?? '') === 'Dibatalkan' && ! empty($expense['alasan_pembatalan'])): ?><small>Alasan: <?= esc($expense['alasan_pembatalan']) ?></small><?php endif; ?></td><td><?= esc($currency($expense['jumlah'])) ?></td><td><span class="<?= ($expense['status'] ?? '') === 'Aktif' ? 'status good' : 'status muted' ?>"><?= esc($expense['status']) ?></span></td><td><?php if (($expense['status'] ?? '') === 'Aktif'): ?><form method="post" action="<?= base_url('admin/laporan/biaya/' . (int) $expense['id'] . '/batalkan') ?>" class="finance-void-form"><?= csrf_field() ?><input type="text" name="alasan_pembatalan" minlength="5" maxlength="500" placeholder="Alasan pembatalan" required><button class="table-button" type="submit" onclick="return confirm('Batalkan catatan biaya ini? Catatan tidak akan dihapus.')">Batalkan</button></form><?php else: ?><small>Catatan dipertahankan</small><?php endif; ?></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                    <p class="detail-note">Catatan biaya yang dibatalkan tidak masuk perhitungan HPP atau operasional, tetapi tetap tersimpan untuk jejak audit. Maksimal 300 catatan terbaru ditampilkan.</p>
                </section>
            
            <?php elseif ($section === 'shipping-zones'): ?>
                <?php
                $allKecamatan = is_array($allKecamatan ?? null) ? ($allKecamatan ?? []) : [];
                $allKelurahan = is_array($allKelurahan ?? null) ? ($allKelurahan ?? []) : [];
                ?>
                <div class="admin-page-intro">
                    <div><p class="admin-eyebrow">LOGISTIK</p><h2>Aturan Ongkos Kirim</h2><p>Kelola kecamatan dan kelurahan beserta biaya ongkos kirim untuk setiap wilayah.</p></div>
                    <div style="display:flex;gap:10px;">
                        <a class="admin-button primary" href="<?= base_url('admin/ongkos-kirim/kecamatan/tambah') ?>">+ Kecamatan Baru</a>
                        <a class="admin-button secondary" href="<?= base_url('admin/ongkos-kirim/kelurahan/tambah') ?>">+ Kelurahan Baru</a>
                    </div>
                </div>
                <section class="admin-panel-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">DAFTAR KECAMATAN</p><h2>Kecamatan</h2></div></div>
                    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Nama Kecamatan</th><th>Jumlah Kelurahan</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($allKecamatan === []): ?><tr><td colspan="3" class="admin-empty-cell">Belum ada kecamatan. Tambahkan kecamatan pertama.</td></tr><?php endif; ?>
                    <?php foreach ($allKecamatan as $kec): ?>
                        <?php $kelCount = count(array_filter($allKelurahan, fn($k) => (int)($k['kecamatan_id'] ?? 0) === (int)$kec['id'])); ?>
                        <tr>
                            <td><b><?= esc($kec['nama_kecamatan'] ?? '-') ?></b><small>ID #<?= (int)$kec['id'] ?></small></td>
                            <td><?= $kelCount ?> kelurahan</td>
                            <td class="admin-actions">
                                <a class="table-link" href="<?= base_url('admin/ongkos-kirim/kecamatan/' . (int)$kec['id'] . '/edit') ?>">Ubah</a>
                                <form method="post" action="<?= base_url('admin/ongkos-kirim/kecamatan/' . (int)$kec['id'] . '/hapus') ?>">
                                    <?= csrf_field() ?>
                                    <button class="table-button danger" type="submit" onclick="return confirm('Hapus kecamatan dan seluruh kelurahan di bawahnya?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </section>
                <section class="admin-panel-card">
                    <div class="admin-card-heading"><div><p class="admin-eyebrow">DAFTAR KELURAHAN & ONGKOS KIRIM</p><h2>Kelurahan</h2></div></div>
                    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kecamatan</th><th>Kelurahan</th><th>Ongkos Kirim</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($allKelurahan === []): ?><tr><td colspan="4" class="admin-empty-cell">Belum ada kelurahan. Tambahkan kelurahan baru.</td></tr><?php endif; ?>
                    <?php foreach ($allKelurahan as $kel): ?>
                        <tr>
                            <td><?= esc($kel['nama_kecamatan'] ?? '-') ?></td>
                            <td><b><?= esc($kel['nama_kelurahan'] ?? '-') ?></b></td>
                            <td><b><?= esc($currency($kel['ongkos_kirim'] ?? 0)) ?></b></td>
                            <td class="admin-actions">
                                <a class="table-link" href="<?= base_url('admin/ongkos-kirim/kelurahan/' . (int)$kel['id'] . '/edit') ?>">Ubah</a>
                                <form method="post" action="<?= base_url('admin/ongkos-kirim/kelurahan/' . (int)$kel['id'] . '/hapus') ?>">
                                    <?= csrf_field() ?>
                                    <button class="table-button danger" type="submit" onclick="return confirm('Hapus kelurahan ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </section>

            <?php elseif ($section === 'shipping-kecamatan-form'): ?>
                <?php $kecItem = is_array($kecItem ?? null) ? ($kecItem ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">ONGKOS KIRIM</p><h2><?= !empty($isEdit) ? 'Ubah Kecamatan' : 'Tambah Kecamatan Baru' ?></h2></div><a class="admin-button secondary" href="<?= base_url('admin/ongkos-kirim') ?>">← Kembali</a></div>
                <section class="admin-panel-card admin-data-form">
                    <form method="post" action="<?= esc($formAction ?? '#', 'attr') ?>">
                        <?= csrf_field() ?>
                        <div class="admin-form-grid">
                            <label class="form-span-two">Nama Kecamatan<input name="nama_kecamatan" maxlength="120" required value="<?= esc(old('nama_kecamatan', $kecItem['nama_kecamatan'] ?? '')) ?>" placeholder="Contoh: Medan Tuntungan"></label>
                        </div>
                        <div class="admin-form-actions">
                            <button class="admin-button primary" type="submit">Simpan Kecamatan</button>
                            <a class="admin-button secondary" href="<?= base_url('admin/ongkos-kirim') ?>">Batal</a>
                        </div>
                    </form>
                </section>

            <?php elseif ($section === 'shipping-kelurahan-form'): ?>
                <?php
                $kelItem = is_array($kelItem ?? null) ? ($kelItem ?? []) : [];
                $kecamatanList = is_array($kecamatanList ?? null) ? ($kecamatanList ?? []) : [];
                ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">ONGKOS KIRIM</p><h2><?= !empty($isEdit) ? 'Ubah Kelurahan' : 'Tambah Kelurahan Baru' ?></h2></div><a class="admin-button secondary" href="<?= base_url('admin/ongkos-kirim') ?>">← Kembali</a></div>
                <section class="admin-panel-card admin-data-form">
                    <form method="post" action="<?= esc($formAction ?? '#', 'attr') ?>">
                        <?= csrf_field() ?>
                        <div class="admin-form-grid">
                            <label>Kecamatan
                                <select name="kecamatan_id" required>
                                    <option value="">— Pilih Kecamatan —</option>
                                    <?php foreach ($kecamatanList as $kec): ?>
                                        <option value="<?= (int)$kec['id'] ?>" <?= (string)old('kecamatan_id', $kelItem['kecamatan_id'] ?? '') === (string)$kec['id'] ? 'selected' : '' ?>><?= esc($kec['nama_kecamatan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Pilih kecamatan induk kelurahan ini. Jika belum ada, <a href="<?= base_url('admin/ongkos-kirim/kecamatan/tambah') ?>">buat kecamatan baru</a> terlebih dahulu.</small>
                            </label>
                            <label>Nama Kelurahan<input name="nama_kelurahan" maxlength="120" required value="<?= esc(old('nama_kelurahan', $kelItem['nama_kelurahan'] ?? '')) ?>" placeholder="Contoh: Tani"></label>
                            <label class="form-span-two">Ongkos Kirim (Rp)<input type="number" name="ongkos_kirim" min="0" step="1000" required value="<?= esc(old('ongkos_kirim', $kelItem['ongkos_kirim'] ?? '0')) ?>"><small>Set 0 jika gratis / tidak ada biaya pengiriman ke kelurahan ini.</small></label>
                        </div>
                        <div class="admin-form-actions">
                            <button class="admin-button primary" type="submit">Simpan Kelurahan</button>
                            <a class="admin-button secondary" href="<?= base_url('admin/ongkos-kirim') ?>">Batal</a>
                        </div>
                    </form>
                </section>

            <?php elseif ($section === 'customers'): ?>
                <?php $rows = is_array($rows ?? null) ? ($rows ?? []) : []; ?>
                <div class="admin-page-intro"><div><p class="admin-eyebrow">DATA PELANGGAN</p><h2>Daftar pelanggan</h2><p>Data pelanggan ditampilkan dari akun yang terdaftar dan jumlah pesanannya.</p></div></div>
                <form class="admin-filter-form" method="get" action="<?= base_url('admin/pelanggan') ?>"><input type="search" name="q" placeholder="Cari nama, WhatsApp, atau email" value="<?= esc($keyword ?? '') ?>"><button class="admin-button secondary" type="submit">Cari</button></form>
                <section class="admin-panel-card"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Pelanggan</th><th>WhatsApp</th><th>Email</th><th>Terdaftar</th><th>Pesanan</th></tr></thead><tbody>
                <?php if ($rows === []): ?><tr><td colspan="5" class="admin-empty-cell">Belum ada data pelanggan.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?><tr><td><b><?= esc($row['nama_lengkap'] ?? '-') ?></b><small>ID #<?= (int) $row['user_id'] ?></small></td><td><?= esc($row['no_whatsapp'] ?? '-') ?></td><td><?= esc($row['email'] ?? '-') ?></td><td><?= ! empty($row['created_at']) ? esc(date('d/m/Y', strtotime($row['created_at']))) : '-' ?></td><td><?= (int) ($row['order_count'] ?? 0) ?></td></tr><?php endforeach; ?>
                </tbody></table></div></section>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
const adminToggle = document.getElementById('admin-menu-toggle');
const adminSidebar = document.getElementById('admin-sidebar');
if (adminToggle && adminSidebar) adminToggle.addEventListener('click', () => adminSidebar.classList.toggle('open'));
const expenseKind = document.getElementById('expense-kind');
const expenseOrder = document.getElementById('expense-order');
const updateExpenseOrderRequirement = () => {
    if (!expenseKind || !expenseOrder) return;
    const hppSelected = expenseKind.value === 'HPP';
    expenseOrder.required = hppSelected;
    expenseOrder.disabled = false;
    const field = expenseOrder.closest('.finance-order-field');
    if (field) field.classList.toggle('is-optional', !hppSelected);
};
if (expenseKind) {
    expenseKind.addEventListener('change', updateExpenseOrderRequirement);
    updateExpenseOrderRequirement();
}

const packageCategory = document.getElementById('package-category');
const packageType = document.getElementById('package-type');
if (packageCategory && packageType) {
    const updatePackageTypes = () => {
        const completeOption = packageType.querySelector('option[value="lengkap"]');
        if (!completeOption) return;
        completeOption.disabled = packageCategory.value === 'duka';
        if (completeOption.disabled && packageType.value === 'lengkap') packageType.value = 'biasa';
    };
    packageCategory.addEventListener('change', updatePackageTypes);
    updatePackageTypes();
}

document.querySelectorAll('.admin-image-input').forEach((input) => {
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const preview = document.getElementById(input.dataset.previewTarget);
        if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
            input.value = '';
            alert('Pilih gambar JPG, PNG, atau WEBP dengan ukuran maksimal 5 MB.');
            return;
        }
        if (!preview) return;

        let image = preview.querySelector('img');
        if (!image) {
            preview.textContent = '';
            image = document.createElement('img');
            image.alt = 'Preview gambar yang dipilih';
            preview.appendChild(image);
        }
        const objectUrl = URL.createObjectURL(file);
        image.onload = () => URL.revokeObjectURL(objectUrl);
        image.src = objectUrl;
    });
});

// Admin Image Zoom Overlay
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('admin-zoom-overlay');
    const zoomImg = document.getElementById('admin-zoom-img');
    const zoomLevelEl = document.getElementById('admin-zoom-level');
    const viewport = document.getElementById('admin-zoom-viewport');
    if (!overlay || !zoomImg || !viewport) return;

    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;

    const updateTransform = () => {
        zoomImg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        if (zoomLevelEl) zoomLevelEl.textContent = Math.round(scale * 100) + '%';
    };

    const resetZoom = () => {
        scale = 1;
        translateX = 0;
        translateY = 0;
        updateTransform();
    };

    const openOverlay = (src) => {
        zoomImg.src = '';
        zoomImg.src = src;
        resetZoom();
        overlay.classList.add('is-active');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeOverlay = () => {
        overlay.classList.remove('is-active');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        setTimeout(() => { zoomImg.src = ''; }, 300);
    };

    // Delegated click — works even for elements added after page load
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-proof]');
        if (trigger) {
            e.preventDefault();
            const src = trigger.dataset.openProof;
            if (src) openOverlay(src);
        }
    });

    document.getElementById('admin-zoom-backdrop')?.addEventListener('click', closeOverlay);
    document.getElementById('admin-zoom-close')?.addEventListener('click', closeOverlay);

    document.getElementById('admin-zoom-in')?.addEventListener('click', () => {
        scale = Math.min(5, Number((scale + 0.25).toFixed(2)));
        updateTransform();
    });

    document.getElementById('admin-zoom-out')?.addEventListener('click', () => {
        scale = Math.max(0.3, Number((scale - 0.25).toFixed(2)));
        updateTransform();
    });

    document.getElementById('admin-zoom-reset')?.addEventListener('click', resetZoom);

    // Mousewheel zoom
    viewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.15 : 0.15;
        scale = Math.min(5, Math.max(0.3, Number((scale + delta).toFixed(2))));
        updateTransform();
    }, { passive: false });

    // Drag to pan
    viewport.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        viewport.style.cursor = 'grabbing';
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            viewport.style.cursor = 'grab';
        }
    });

    // Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('is-active')) {
            closeOverlay();
        }
    });
});
</script>

<?php if ($section === 'dashboard'): ?>
<script>
// =========================================================================
// Interactive Admin Dashboard Calendar — Inline Mode with Schedule Modal
// =========================================================================
(function() {
    const grid = document.getElementById('admin-cal-days-grid');
    if (!grid) return;

    const dataEl = document.getElementById('admin-calendar-data');
    let schedule = {};
    if (dataEl) {
        try { schedule = JSON.parse(dataEl.textContent || '{}'); } catch(e) { schedule = {}; }
    }

    const monthTitle = document.getElementById('admin-cal-month-title');
    const prevBtn    = document.getElementById('admin-cal-prev');
    const nextBtn    = document.getElementById('admin-cal-next');
    const hoverNote  = document.getElementById('admin-cal-hover-note');

    // Modal elements
    const modal         = document.getElementById('admin-schedule-modal');
    const modalBackdrop = document.getElementById('admin-schedule-backdrop');
    const modalClose    = document.getElementById('admin-schedule-close');
    const modalTitle    = document.getElementById('admin-modal-title');
    const modalBody     = document.getElementById('admin-schedule-body');

    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    const now = new Date();
    let currentYear  = now.getFullYear();
    let currentMonth = now.getMonth();

    // Check if there are scheduled dates in the current or upcoming months
    const dateKeys = Object.keys(schedule).sort();
    if (dateKeys.length > 0) {
        function pad2(n) { return String(n).padStart(2, '0'); }
        const todayStr = now.getFullYear() + '-' + pad2(now.getMonth() + 1) + '-' + pad2(now.getDate());
        const upcoming = dateKeys.find(k => k >= todayStr) || dateKeys[dateKeys.length - 1];
        if (upcoming) {
            const parts = upcoming.split('-');
            currentYear = parseInt(parts[0], 10);
            currentMonth = parseInt(parts[1], 10) - 1;
        }
    }

    function pad2(n) { return String(n).padStart(2, '0'); }
    function dateStr(y, m, d) { return y + '-' + pad2(m) + '-' + pad2(d); }

    function formatDisplayIndo(ds) {
        const parts = ds.split('-');
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const d = parseInt(parts[2], 10);
        const dt = new Date(y, m, d);
        const dayName = dayNames[dt.getDay()];
        return dayName + ', ' + d + ' ' + monthNames[m] + ' ' + y;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function openModal(ds) {
        if (!modal) return;
        const items = schedule[ds] || [];
        modalTitle.textContent = 'Jadwal Acara — ' + formatDisplayIndo(ds);

        if (items.length === 0) {
            modalBody.innerHTML = `
                <div class="admin-schedule-empty">
                    <h4 style="margin:0 0 6px; font-size:16px; color:#29231f;">Tidak Ada Jadwal Dekorasi</h4>
                    <p style="margin:0; font-size:13px; color:#796f66;">Belum ada pesanan dekorasi yang pembayarannya diterima pada tanggal ini.</p>
                </div>
            `;
        } else {
            let html = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; padding-bottom:10px; border-bottom:1px solid #ede5da;">
                    <span style="font-size:13px; font-weight:700; color:#bd922b;">${items.length} Paket Dekorasi Terjadwal</span>
                    <small style="color:#796f66; font-size:12px;">Tenggat Waktu: ${formatDisplayIndo(ds)}</small>
                </div>
            `;

            items.forEach(ord => {
                const isDiproses = ord.status_dekorasi === 'Diproses';
                const isSelesai  = ord.status_dekorasi === 'Selesai';
                const dekorClass = isSelesai ? 'status good' : (isDiproses ? 'status progress' : 'status pending');
                const csrfToken  = '<?= csrf_token() ?>';
                const csrfHash   = '<?= csrf_hash() ?>';

                html += `
                    <div class="admin-sched-order-card">
                        <div class="admin-sched-order-top">
                            <div>
                                <span class="admin-sched-order-code">Pesanan #${escapeHtml(ord.kode_pesanan)}</span>
                            </div>
                            <div class="admin-sched-badges">
                                <span class="${dekorClass}">Dekorasi: ${escapeHtml(ord.status_dekorasi)}</span>
                                <span class="status muted" style="font-size:11px;">Bayar: ${escapeHtml(ord.status_pembayaran)}</span>
                            </div>
                        </div>

                        <div class="admin-sched-order-main">
                            <div class="admin-sched-info-item">
                                <small>Paket Dekorasi</small>
                                <strong>${escapeHtml(ord.package_name)}</strong>
                            </div>
                            <div class="admin-sched-info-item">
                                <small>Pemesan & Kontak</small>
                                <span>${escapeHtml(ord.nama_pemesan)}</span>
                                ${ord.no_whatsapp ? `<br><a href="https://wa.me/${ord.no_whatsapp.replace(/[^0-9]/g, '')}" target="_blank" rel="noopener" style="color:#bd922b; text-decoration:none; font-size:12px; font-weight:600;">WhatsApp: ${escapeHtml(ord.no_whatsapp)}</a>` : ''}
                            </div>
                            <div class="admin-sched-info-item" style="grid-column: 1 / -1;">
                                <small>Lokasi Acara</small>
                                <span>${escapeHtml(ord.lokasi_acara || '-')}${ord.nama_kecamatan ? ' (' + escapeHtml(ord.nama_kecamatan) + (ord.nama_kelurahan ? ', ' + escapeHtml(ord.nama_kelurahan) : '') + ')' : ''}</span>
                                ${ord.waktu_acara ? `<span style="display:inline-block; margin-left:8px; color:#bd922b; font-weight:600;">Pukul ${escapeHtml(ord.waktu_acara)}</span>` : ''}
                            </div>
                        </div>

                        <div class="admin-sched-actions">
                            <span style="margin-right:auto; font-weight:700; color:#29231f; font-size:14px;">${escapeHtml(ord.total_formatted)}</span>
                            ${isDiproses ? `
                                <form method="post" action="<?= base_url('admin/pesanan') ?>/${ord.id}/tandai-selesai" style="margin:0;">
                                    <input type="hidden" name="${csrfToken}" value="${csrfHash}">
                                    <button class="table-button" type="submit" onclick="return confirm('Tandai pesanan ${escapeHtml(ord.kode_pesanan)} sebagai Selesai?')" style="font-family:inherit; font-size:12px; font-weight:600; color:#8c6818; background:#fbf6eb; border:1px solid #bd922b; border-radius:4px; padding:5px 12px; cursor:pointer; white-space:nowrap; line-height:1.3;">Tandai Selesai</button>
                                </form>
                            ` : ''}
                            <a class="table-link" href="<?= base_url('admin/pesanan') ?>/${ord.id}" style="font-weight:600;">Lihat Detail</a>
                        </div>
                    </div>
                `;
            });

            modalBody.innerHTML = html;
        }

        modal.classList.add('is-active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    modalClose?.addEventListener('click', closeModal);
    modalBackdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal?.classList.contains('is-active')) {
            closeModal();
        }
    });

    function renderCalendar() {
        if (!grid || !monthTitle) return;
        monthTitle.textContent = monthNames[currentMonth] + ' ' + currentYear;

        const todayObj = new Date();
        const todayStr = dateStr(todayObj.getFullYear(), todayObj.getMonth() + 1, todayObj.getDate());
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        let html = '';
        for (let i = 0; i < firstDay; i++) {
            html += '<span class="admin-cal-day empty"></span>';
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const ds = dateStr(currentYear, currentMonth + 1, d);
            const isToday = ds === todayStr;
            const isPast = ds < todayStr;
            const items = schedule[ds] || [];
            const hasBooking = items.length > 0;

            let cls = 'admin-cal-day';
            let noteText = '—';
            let noteDesc = 'Tidak ada jadwal dekorasi';

            if (hasBooking) {
                const countDiproses = items.filter(x => x.status_dekorasi === 'Diproses').length;
                const countSelesai  = items.filter(x => x.status_dekorasi === 'Selesai').length;

                if (countDiproses > 0) {
                    cls += ' has-diproses';
                    noteText = items.length + ' Paket';
                    noteDesc = items.length + ' paket dekorasi harus diselesaikan (Klik untuk rincian)';
                } else if (countSelesai === items.length) {
                    cls += ' all-selesai';
                    noteText = 'Selesai';
                    noteDesc = items.length + ' dekorasi selesai dikerjakan (Klik untuk rincian)';
                } else {
                    cls += ' has-diproses';
                    noteText = items.length + ' Paket';
                    noteDesc = items.length + ' pesanan dekorasi (Klik untuk rincian)';
                }
            } else if (isToday) {
                cls += ' today available';
                noteText = 'Hari Ini';
                noteDesc = 'Hari ini — tidak ada jadwal dekorasi';
            } else if (isPast) {
                cls += ' past';
                noteText = 'Lewat';
                noteDesc = 'Tanggal sudah terlewat';
            } else {
                cls += ' available';
                noteText = 'Kosong';
                noteDesc = 'Tersedia / belum ada booking acara';
            }

            if (isToday) cls += ' today';

            html += `
                <button type="button" class="${cls}" data-date="${ds}" data-desc="${escapeHtml(noteDesc)}" title="${escapeHtml(noteDesc)}">
                    <span class="cal-day-num">${d}</span>
                    <span class="cal-day-note">${noteText}</span>
                </button>
            `;
        }

        grid.innerHTML = html;

        // Event listeners on day cells
        grid.querySelectorAll('.admin-cal-day').forEach(btn => {
            const ds = btn.dataset.date;
            const desc = btn.dataset.desc;
            if (!ds) return;

            btn.addEventListener('mouseenter', () => {
                if (hoverNote) {
                    hoverNote.innerHTML = `<strong>${formatDisplayIndo(ds)}:</strong> ${desc}`;
                }
            });

            btn.addEventListener('mouseleave', () => {
                if (hoverNote) {
                    hoverNote.textContent = 'Arahkan kursor atau klik tanggal untuk melihat rincian paket dekorasi yang harus diselesaikan.';
                }
            });

            btn.addEventListener('click', () => {
                openModal(ds);
            });
        });
    }

    prevBtn?.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar();
    });

    nextBtn?.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar();
    });

    renderCalendar();
})();
</script>
<?php endif; ?>

<div class="admin-zoom-overlay" id="admin-zoom-overlay" aria-hidden="true">
    <div class="admin-zoom-backdrop" id="admin-zoom-backdrop"></div>
    <div class="admin-zoom-container">
        <div class="admin-zoom-toolbar">
            <button type="button" class="admin-zoom-btn" id="admin-zoom-out" title="Perkecil (Zoom Out)">－</button>
            <span class="admin-zoom-level" id="admin-zoom-level">100%</span>
            <button type="button" class="admin-zoom-btn" id="admin-zoom-in" title="Perbesar (Zoom In)">＋</button>
            <button type="button" class="admin-zoom-btn" id="admin-zoom-reset" title="Kembalikan (Reset Zoom)">↺</button>
            <button type="button" class="admin-zoom-btn close-btn" id="admin-zoom-close" title="Tutup (Esc)">✕</button>
        </div>
        <div class="admin-zoom-viewport" id="admin-zoom-viewport">
            <img src="" alt="Bukti Pembayaran Penuh" id="admin-zoom-img" class="admin-zoom-img">
        </div>
        <p class="admin-zoom-hint">Scroll mouse atau gunakan tombol ＋ / － untuk zoom in/out. Klik dan seret gambar untuk menggeser posisi.</p>
    </div>
</div>
</body>
</html>
