

<?php
helper('form');
$errors = session()->getFlashdata('errors') ?? [];
$errorMessage = session()->getFlashdata('error');
$successMessage = session()->getFlashdata('success');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Admin | Nauli Dekor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/admin.css')) ?>?v=20261119-2">
</head>
<body class="admin-login-body">
    <main class="admin-login-shell">
        <section class="admin-login-card">
            <a class="admin-brand" href="<?= esc(site_url('login')) ?>"><span>✦</span> NAULI <small>DEKOR</small></a>
            <p class="admin-eyebrow">AREA PENGELOLA</p>
            <h1>Selamat datang kembali.</h1>
            <p class="admin-login-intro">Masuk untuk mengelola paket, tambahan dekorasi, pelanggan, dan pesanan Nauli Dekor.</p>

            <?php if ($errorMessage): ?>
                <div class="admin-alert error"><?= esc($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($successMessage): ?>
                <div class="admin-alert success"><?= esc($successMessage) ?></div>
            <?php endif; ?>
            <?php if (is_array($errors) && $errors !== []): ?>
                <div class="admin-alert error">
                    <ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= esc(site_url('admin/login')) ?>" class="admin-form">
                <?= csrf_field() ?>
                <label for="no_telepon">Nomor telepon admin</label>
                <input id="no_telepon" name="no_telepon" type="tel" inputmode="numeric" autocomplete="username" value="<?= esc(old('no_telepon') ?? '') ?>" required>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="admin-button primary full" type="submit">Masuk ke Panel Admin</button>
            </form>
            <a class="admin-back-link" href="<?= esc(site_url('login')) ?>">← Kembali ke halaman pelanggan</a>
        </section>
    </main>
</body>
</html>
