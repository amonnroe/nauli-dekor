<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = (int) $session->get('user_id');
        $adminId = (int) $session->get('admin_id');

        // Jika login sebagai admin, izinkan admin memantau seluruh tampilan website (dashboard, katalog, paket, dll.)
        if ($adminId > 0 && (new \App\Models\AdminModel())->find($adminId) !== null) {
            return null;
        }

        // Satu sesi pelanggan hanya boleh jika user_id valid
        if ($userId < 1) {
            return redirect()->to('/login')->with('error', 'Silakan masuk sebagai pelanggan terlebih dahulu.');
        }

        if ((new UserModel())->find($userId) === null) {
            $session->remove(['user_id', 'user_name', 'user_phone', 'cart', 'checkout_token']);
            $session->regenerate(true);
            return redirect()->to('/login')->with('error', 'Sesi pelanggan tidak valid. Silakan masuk kembali.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada perubahan response setelah controller selesai.
    }
}
