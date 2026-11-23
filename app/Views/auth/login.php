<?php helper('form'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | Nauli Dekor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
</head>
<body>
    <main class="auth-page">
        <section class="visual-panel" aria-label="Dekorasi bunga Nauli Dekor">
            <div class="visual-overlay"></div>
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">✦</span>
                <span>NAULI<br><small>DEKOR</small></span>
            </div>
            <div class="visual-copy">
                <h1>Setiap momen,<br>layak dikenang.</h1>
                <p>Wujudkan momen istimewa bersama tim dekorasi pilihan kami.</p>
            </div>
            <p class="copyright">© <?= date('Y') ?> Nauli Dekor</p>
        </section>

        <section class="form-panel">
            <div class="form-wrap">
                <a class="back-link" href="<?= base_url('/') ?>">
                    <span aria-hidden="true">←</span> Kembali ke beranda
                </a>
                <p class="eyebrow">SELAMAT DATANG</p>
                <h2>Masuk ke akun Anda</h2>
                <p class="intro">Lanjutkan untuk mengelola pesanan dekorasi Anda.</p>

                <div class="tabs" role="tablist" aria-label="Akses akun">
                    <button class="tab is-active" type="button" role="tab" aria-selected="true" data-tab="login">Masuk</button>
                    <button class="tab" type="button" role="tab" aria-selected="false" data-tab="register">Daftar</button>
                </div>

                <?php $errors = session('errors') ?? []; ?>
                <?php if (session('error')): ?><div class="form-message error"><?= esc(session('error')) ?></div><?php endif; ?>
                <?php if (session('success')): ?><div class="form-message success"><?= esc(session('success')) ?></div><?php endif; ?>
                <form class="auth-form <?= session('register_open') ? '' : 'is-active' ?>" id="login-form" action="<?= base_url('login') ?>" method="post"><?= csrf_field() ?><?= csrf_field() ?>
                    <label for="login-whatsapp">Nomor WhatsApp</label>
                    <div class="input-group">
                        <span class="prefix">+62</span>
                        <input id="login-whatsapp" name="no_whatsapp" type="tel" inputmode="numeric" placeholder="812 3456 7890" autocomplete="tel" value="<?= esc(old('no_whatsapp')) ?>" required>
                    </div>

                    <label for="login-password">Password</label>
                    <div class="input-group password-group">
                        <input id="login-password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        <button class="show-password" type="button" aria-label="Tampilkan password" data-toggle-password="login-password">◉</button>
                    </div>
                    <a class="forgot-link" href="#">Lupa password?</a>
                    <?php if (isset($errors['no_whatsapp']) || isset($errors['password'])): ?><small class="field-error"><?= esc($errors['no_whatsapp'] ?? $errors['password']) ?></small><?php endif; ?>
                    <button class="primary-button" type="submit">Masuk <span aria-hidden="true">→</span></button>
                </form>

                <form class="auth-form <?= session('register_open') ? 'is-active' : '' ?>" id="register-form" action="<?= base_url('daftar') ?>" method="post"><?= csrf_field() ?><?= csrf_field() ?>
                    <label for="register-name">Nama lengkap</label>
                    <input id="register-name" name="nama_lengkap" type="text" placeholder="Masukkan nama lengkap" autocomplete="name" value="<?= esc(old('nama_lengkap')) ?>" required>
                    <label for="register-whatsapp">Nomor WhatsApp</label>
                    <div class="input-group">
                        <span class="prefix">+62</span>
                        <input id="register-whatsapp" name="no_whatsapp" type="tel" inputmode="numeric" placeholder="812 3456 7890" autocomplete="tel" value="<?= esc(old('no_whatsapp')) ?>" required>
                    </div>
                    <label for="register-password">Password</label>
                    <input id="register-password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                    <label for="register-confirm-password">Konfirmasi password</label>
                    <input id="register-confirm-password" name="konfirmasi_password" type="password" placeholder="Ulangi password" autocomplete="new-password" required>
                    <?php if (isset($errors['nama_lengkap']) || isset($errors['konfirmasi_password'])): ?><small class="field-error"><?= esc($errors['nama_lengkap'] ?? $errors['konfirmasi_password']) ?></small><?php endif; ?>
                    <button class="primary-button" type="submit">Daftar <span aria-hidden="true">→</span></button>
                </form>

                <p class="terms">Dengan melanjutkan, Anda menyetujui <a href="#">Syarat &amp; Ketentuan</a> dan <a href="#">Kebijakan Privasi</a>.</p>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('.tab').forEach((tab) => {
            tab.addEventListener('click', () => {
                const register = tab.dataset.tab === 'register';
                document.querySelectorAll('.tab').forEach((item) => {
                    item.classList.toggle('is-active', item === tab);
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                });
                document.querySelector('#login-form').classList.toggle('is-active', !register);
                document.querySelector('#register-form').classList.toggle('is-active', register);
            });
        });
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
</body>
</html>
