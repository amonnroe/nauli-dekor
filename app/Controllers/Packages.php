<?php

namespace App\Controllers;

use App\Models\ExtraModel;
use App\Models\PackageModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Packages extends BaseController
{
    public function index(): string
    {
        $requestedCategory = (string) $this->request->getGet('kategori');
        $category = in_array($requestedCategory, ['pernikahan', 'duka'], true)
            ? $requestedCategory
            : 'pernikahan';

        // Nilai filter mengikuti nilai jenis_paket yang benar-benar digunakan di tabel packages.
        $typeOptions = $category === 'duka'
            ? [
                ['value' => 'biasa', 'label' => 'Paket Biasa', 'subtitle' => 'Dekorasi penghormatan'],
                ['value' => 'tambahan', 'label' => 'Paket Tambahan', 'subtitle' => 'Rangkaian pelengkap'],
            ]
            : [
                ['value' => 'biasa', 'label' => 'Paket Biasa', 'subtitle' => 'Dekorasi utama'],
                ['value' => 'lengkap', 'label' => 'Paket Lengkap', 'subtitle' => 'Pilihan dekorasi lengkap'],
                ['value' => 'tambahan', 'label' => 'Paket Tambahan', 'subtitle' => 'Pilih sesuai kebutuhan'],
            ];

        $allowedTypes = array_column($typeOptions, 'value');
        $requestedType = (string) $this->request->getGet('tipe');
        $type = in_array($requestedType, $allowedTypes, true)
            ? $requestedType
            : $allowedTypes[0];

        $selectedType = $typeOptions[0];
        foreach ($typeOptions as $option) {
            if ($option['value'] === $type) {
                $selectedType = $option;
                break;
            }
        }

        $pageTitles = [
            'pernikahan' => [
                'biasa' => 'Paket Biasa',
                'lengkap' => 'Paket Lengkap',
                'tambahan' => 'Paket Tambahan',
            ],
            'duka' => [
                'biasa' => 'Paket Biasa Rumah Duka',
                'tambahan' => 'Paket Tambahan Rumah Duka',
            ],
        ];

        $descriptions = [
            'pernikahan' => [
                'biasa' => 'Pilihan dekorasi utama untuk menghidupkan momen pernikahan Anda.',
                'lengkap' => 'Pilihan dekorasi lengkap untuk menyempurnakan momen pernikahan Anda.',
                'tambahan' => 'Pilihan paket tambahan sesuai kebutuhan acara pernikahan Anda.',
            ],
            'duka' => [
                'biasa' => 'Pilihan rangkaian duka yang dibuat dengan penuh penghormatan.',
                'tambahan' => 'Pilihan rangkaian tambahan untuk melengkapi penghormatan.',
            ],
        ];

        $packages = (new PackageModel())
            ->where('aktif', 1)
            ->where('kategori', $category)
            ->where('jenis_paket', $type)
            ->orderBy('harga', 'ASC')
            ->findAll();

        // Data perlengkapan pada bagian bawah katalog juga berasal dari tabel extras.
        $extras = (new ExtraModel())
            ->where('aktif', 1)
            ->orderBy('nama', 'ASC')
            ->findAll();

        return view('packages/index', [
            'category' => $category,
            'type' => $type,
            'typeOptions' => $typeOptions,
            'selectedTypeLabel' => $selectedType['label'],
            'pageTitle' => $pageTitles[$category][$type],
            'packageDescription' => $descriptions[$category][$type],
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
