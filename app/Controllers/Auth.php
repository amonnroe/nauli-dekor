<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        $session = session();
        $userId = (int) $session->get('user_id');
        $adminId = (int) $session->get('admin_id');

        if ($userId > 0 && $adminId < 1 && (new UserModel())->find($userId) !== null) {
            return redirect()->to('/dashboard');
        }
        if ($adminId > 0 && $userId < 1 && (new \App\Models\AdminModel())->find($adminId) !== null) {
            return redirect()->to('/admin');
        }

        // Bersihkan sesi tidak valid atau sesi role yang tercampur.
        if ($userId > 0 || $adminId > 0) {
            $session->remove(['user_id', 'user_name', 'user_phone', 'cart', 'checkout_token', 'admin_id', 'admin_phone']);
            $session->regenerate(true);
        }

        return view('auth/login');
    }

    public function attempt()
    {
        if (! $this->validate(['no_whatsapp' => 'required|min_length[4]|max_length[20]', 'password' => 'required|min_length[6]'])) return redirect()->to('/login')->withInput()->with('errors', $this->validator->getErrors());
        $phone = $this->normalizePhone((string) $this->request->getPost('no_whatsapp'));
        $user = (new UserModel())->where('no_whatsapp', $phone)->first();
        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password'])) return redirect()->to('/login')->withInput()->with('error', 'Nomor WhatsApp atau password tidak sesuai.');

        // Jika akun merupakan role Admin, arahkan langsung ke panel admin dengan aman
        if (($user['role'] ?? 'Pelanggan') === 'Admin') {
            session()->regenerate(true);
            session()->remove(['user_id', 'user_name', 'user_phone', 'cart', 'checkout_token']);
            session()->set([
                'admin_id'    => (int) $user['user_id'],
                'admin_phone' => (string) $user['no_whatsapp'],
                'admin_name'  => (string) ($user['nama_lengkap'] ?? 'Admin'),
            ]);
            return redirect()->to('/admin')->with('success', 'Selamat datang di panel admin, ' . $user['nama_lengkap'] . '.');
        }

        session()->regenerate(true);
        session()->remove(['admin_id', 'admin_phone', 'cart', 'checkout_token']);
        session()->set(['user_id' => $user['user_id'], 'user_name' => $user['nama_lengkap'], 'user_phone' => $user['no_whatsapp']]);
        return redirect()->to('/dashboard')->with('success', 'Selamat datang, ' . $user['nama_lengkap'] . '.');
    }

    public function register()
    {
        $rules = ['nama_lengkap' => 'required|min_length[3]|max_length[150]', 'no_whatsapp' => 'required|min_length[8]|max_length[20]', 'password' => 'required|min_length[8]', 'konfirmasi_password' => 'required|matches[password]'];
        if (! $this->validate($rules)) return redirect()->to('/login')->withInput()->with('register_open', true)->with('errors', $this->validator->getErrors());
        $phone = $this->normalizePhone((string) $this->request->getPost('no_whatsapp'));
        $users = new UserModel();
        if ($users->where('no_whatsapp', $phone)->first()) return redirect()->to('/login')->withInput()->with('register_open', true)->with('error', 'Nomor WhatsApp sudah terdaftar. Silakan masuk.');
        $id = $users->insert(['nama_lengkap' => trim((string) $this->request->getPost('nama_lengkap')), 'no_whatsapp' => $phone, 'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT), 'role' => 'Pelanggan']);
        if ($id === false) {
            return redirect()->to('/login')->withInput()->with('register_open', true)->with('error', 'Pendaftaran gagal disimpan. Silakan coba kembali.');
        }
        session()->regenerate(true);
        session()->remove(['admin_id', 'admin_phone', 'cart', 'checkout_token']);
        session()->set(['user_id' => $id, 'user_name' => trim((string) $this->request->getPost('nama_lengkap')), 'user_phone' => $phone]);
        return redirect()->to('/dashboard')->with('success', 'Pendaftaran berhasil. Selamat datang di Nauli Dekor!');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda telah keluar dari akun.');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;
    }
}
