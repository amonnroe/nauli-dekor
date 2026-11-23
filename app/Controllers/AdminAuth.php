<?php

namespace App\Controllers;

use App\Models\AdminModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class AdminAuth extends BaseController
{
    public function login()
    {
        $session = session();
        $adminId = (int) $session->get('admin_id');
        $userId = (int) $session->get('user_id');

        if ($adminId > 0 && $userId < 1 && (new AdminModel())->find($adminId) !== null) {
            return redirect()->to('/admin');
        }
        if ($userId > 0 && $adminId < 1 && (new UserModel())->find($userId) !== null) {
            return redirect()->to('/dashboard');
        }

        // Hindari sesi admin/pelanggan yang tercampur atau sesi admin yang sudah tidak valid.
        if ($adminId > 0 || $userId > 0) {
            $session->remove(['admin_id', 'admin_phone', 'admin_name', 'user_id', 'user_name', 'user_phone', 'cart', 'checkout_token']);
            $session->regenerate(true);
        }

        return view('admin/login');
    }

    public function attempt(): RedirectResponse
    {
        if (! $this->validate([
            'no_telepon' => 'required|min_length[4]|max_length[20]',
            'password'   => 'required|min_length[6]|max_length[255]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $phone = $this->normalizePhone((string) $this->request->getPost('no_telepon'));
        $admin = (new AdminModel())->findByPhone($phone);

        if ($admin === null || ! password_verify((string) $this->request->getPost('password'), (string) $admin['password'])) {
            return redirect()->back()->withInput()->with('error', 'Nomor telepon atau password admin tidak sesuai.');
        }

        // Satu browser hanya memegang satu jenis sesi agar hak akses tidak tercampur.
        session()->regenerate(true);
        session()->remove(['user_id', 'user_name', 'user_phone', 'cart', 'checkout_token']);
        session()->set([
            'admin_id'    => (int) $admin['user_id'],
            'admin_phone' => (string) $admin['no_whatsapp'],
            'admin_name'  => (string) ($admin['nama_lengkap'] ?? 'Admin'),
        ]);

        return redirect()->to('/admin')->with('success', 'Selamat datang di panel Nauli Dekor.');
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/admin/login')->with('success', 'Anda telah keluar dari panel admin.');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone) ?? '';
        return str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;
    }
}
