<?php

namespace App\Controllers;

use App\Models\ExtraModel;
use App\Models\PackageModel;
use Throwable;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $storedCart = session('cart');
        $cartCount = 0;

        if (is_array($storedCart)) {
            foreach ($storedCart as $item) {
                if (is_array($item)) {
                    $cartCount += max(0, (int) ($item['jumlah'] ?? 0));
                }
            }
        }

        $data = [
            'userName' => (string) (session('user_name') ?? session('admin_name') ?? (session('admin_id') ? 'Admin' : '')),
            'cartCount' => $cartCount,
            'packageCount' => null,
            'extraCount' => null,
            'featuredPackages' => [],
            'dashboardDataAvailable' => true,
        ];

        try {
            $packageModel = new PackageModel();

            $data['packageCount'] = (new PackageModel())
                ->where('aktif', 1)
                ->countAllResults();

            $data['extraCount'] = (new ExtraModel())
                ->where('aktif', 1)
                ->countAllResults();

            // Kartu koleksi mengambil maksimal lima paket aktif dari database.
            // Urutan ID dibuat stabil agar susunan kartu tidak berubah acak.
            $data['featuredPackages'] = $packageModel
                ->where('aktif', 1)
                ->orderBy('id', 'ASC')
                ->findAll(5);
        } catch (Throwable $exception) {
            $data['dashboardDataAvailable'] = false;
            $data['packageCount'] = null;
            $data['extraCount'] = null;
            $data['featuredPackages'] = [];

            log_message(
                'error',
                'Dashboard gagal mengambil data katalog: {message}',
                ['message' => $exception->getMessage()]
            );
        }

        return view('dashboard/index', $data);
    }
}
