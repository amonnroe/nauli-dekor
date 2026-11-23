<?php

namespace App\Filters;

use App\Models\AdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $adminId = (int) $session->get('admin_id');

        if ($adminId < 1 || (int) $session->get('user_id') > 0) {
            return redirect()->to('/admin/login')->with('error', 'Silakan masuk sebagai admin untuk melanjutkan.');
        }

        if ((new AdminModel())->find($adminId) === null) {
            $session->remove(['admin_id', 'admin_phone']);
            $session->regenerate(true);
            return redirect()->to('/admin/login')->with('error', 'Sesi admin tidak valid. Silakan masuk kembali.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada perubahan response setelah controller selesai.
    }
}
