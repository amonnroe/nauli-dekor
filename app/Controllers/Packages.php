<?php

namespace App\Controllers;

use App\Models\ExtraModel;
use App\Models\PackageModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Packages extends BaseController
{
    public function index(): string
    {
        $packages = (new PackageModel())
            ->where('aktif', 1)
            ->orderBy('harga', 'ASC')
            ->findAll();

        $extras = (new ExtraModel())
            ->where('aktif', 1)
            ->orderBy('nama', 'ASC')
            ->findAll();

        return view('packages/index', [
            'pageTitle' => 'Pilihan Paket Dekorasi',
            'packageDescription' => 'Rangkaian dekorasi yang dirancang untuk menyempurnakan setiap momen berharga Anda.',
            'packages' => $packages,
            'extras' => $extras,
        ]);
    }

    public function detail(int $id): string
    {
        $package = (new PackageModel())
            ->where('aktif', 1)
            ->find($id);

        if ($package === null) {
            throw PageNotFoundException::forPageNotFound('Paket tidak ditemukan atau sudah tidak tersedia.');
        }

        return view('packages/detail', [
            'package' => $package,
        ]);
    }
}
