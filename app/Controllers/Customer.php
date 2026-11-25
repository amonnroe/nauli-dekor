<?php

namespace App\Controllers;

use App\Models\ExtraModel;
use App\Models\PackageModel;
use App\Models\OrderModel;
use App\Models\UserModel;

class Customer extends BaseController
{
    /**
     * Ambil isi keranjang dari session, lalu sinkronkan nama, harga,
     * status aktif, dan stok dari database.
     *
     * Harga dari browser/session tidak pernah dijadikan sumber kebenaran.
     */
    private function refreshCartFromDatabase(): array
    {
        $storedCart = session('cart');
        if (! is_array($storedCart)) {
            $storedCart = [];
        }

        $freshCart = [];
        $warnings = [];
        $packageAlreadyAdded = false;

        $packageModel = new PackageModel();
        $extraModel = new ExtraModel();

        foreach ($storedCart as $storedKey => $storedItem) {
            if (! is_array($storedItem)) {
                $warnings[] = 'Item keranjang yang tidak valid telah dihapus.';
                continue;
            }

            $type = $storedItem['type'] ?? '';
            $id = filter_var($storedItem['id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = max(1, (int) ($storedItem['jumlah'] ?? 1));

            if ($id === false || $id < 1) {
                $warnings[] = 'Item keranjang yang tidak valid telah dihapus.';
                continue;
            }

            if ($type === 'package') {
                // Tabel orders hanya menyediakan satu kolom package_id.
                // Karena itu satu pesanan hanya boleh memiliki satu paket utama.
                if ($packageAlreadyAdded) {
                    $warnings[] = 'Keranjang hanya mendukung satu paket utama. Paket lain yang tersimpan lebih dari satu telah dilepas.';
                    continue;
                }

                $package = $packageModel
                    ->where('aktif', 1)
                    ->find($id);

                if ($package === null) {
                    $warnings[] = 'Paket yang sudah tidak tersedia telah dihapus dari keranjang.';
                    continue;
                }

                $key = 'package_' . (int) $package['id'];
                $freshCart[$key] = [
                    'key'    => $key,
                    'type'   => 'package',
                    'id'     => (int) $package['id'],
                    'nama'   => (string) $package['nama_paket'],
                    'harga'  => (float) $package['harga'],
                    'jumlah' => 1,
                    'stok'   => null,
                ];

                $packageAlreadyAdded = true;
                continue;
            }

            if ($type === 'extra') {
                $extra = $extraModel
                    ->where('aktif', 1)
                    ->find($id);

                if ($extra === null) {
                    $warnings[] = 'Tambahan dekorasi yang sudah tidak tersedia telah dihapus dari keranjang.';
                    continue;
                }

                $stock = (int) ($extra['stok'] ?? 0);
                if ($stock < 1) {
                    $warnings[] = 'Tambahan dekorasi ' . $extra['nama'] . ' sedang kehabisan stok dan telah dihapus dari keranjang.';
                    continue;
                }

                if ($quantity > $stock) {
                    $quantity = $stock;
                    $warnings[] = 'Jumlah ' . $extra['nama'] . ' disesuaikan dengan stok yang tersedia.';
                }

                $key = 'extra_' . (int) $extra['id'];
                $freshCart[$key] = [
                    'key'    => $key,
                    'type'   => 'extra',
                    'id'     => (int) $extra['id'],
                    'nama'   => (string) $extra['nama'],
                    'harga'  => (float) $extra['harga'],
                    'jumlah' => $quantity,
                    'stok'   => $stock,
                ];
                continue;
            }

            $warnings[] = 'Jenis item keranjang yang tidak dikenal telah dihapus.';
        }

        // Simpan kembali versi yang sudah divalidasi terhadap database.
        session()->set('cart', $freshCart);

        if ($warnings !== []) {
            session()->setFlashdata('warning', implode(' ', array_unique($warnings)));
        }

        return $freshCart;
    }

    /** Membentuk data ringkasan dari item yang sudah disinkronkan. */
    private function makeCartSummary(array $items): array
    {
        $items = array_values($items);

        $count = 0;
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (int) ($item['jumlah'] ?? 0);
            $price = (float) ($item['harga'] ?? 0);

            $count += $quantity;
            $total += $price * $quantity;
        }

        return [
            'items' => $items,
            'count' => $count,
            'total' => round($total, 2),
        ];
    }

    private function show(string $page): string
    {
        $items = $this->refreshCartFromDatabase();
        $data = [
            'page' => $page,
            'cart' => $this->makeCartSummary($items),
        ];

        if ($page === 'booking' || $page === 'payment') {
            $data['paymentScheme'] = (string) session()->get('payment_scheme');
            $data['paymentChannel'] = (string) session()->get('payment_channel');
            $draft = session()->get('booking_draft');
            $data['bookingDraft'] = is_array($draft) ? $draft : [];
        }

        if ($page === 'booking') {
            $data['kecamatanList'] = (new \App\Models\ShippingZoneModel())->getAllKecamatan();
            $mainPackageId = null;
            $mainPackageName = '';
            foreach ($items as $item) {
                if (($item['type'] ?? '') === 'package') {
                    $mainPackageId = (int) $item['id'];
                    $mainPackageName = (string) $item['nama'];
                    break;
                }
            }
            $data['mainPackageId'] = $mainPackageId;
            $data['mainPackageName'] = $mainPackageName;
            $bookedDates = [];
            if ($mainPackageId !== null) {
                $rows = db_connect()->table('orders')
                    ->select('tanggal_acara')
                    ->where('package_id', $mainPackageId)
                    ->where('tanggal_acara >=', date('Y-m-d'))
                    ->whereNotIn('status_pesanan', ['Dibatalkan'])
                    ->whereNotIn('status_pembayaran', ['Ditolak'])
                    ->get()
                    ->getResultArray();
                $bookedDates = array_values(array_unique(array_filter(array_column($rows, 'tanggal_acara'))));
            }
            $data['bookedDates'] = $bookedDates;
        }

        if ($page === 'booking' || $page === 'payment') {
            $checkoutToken = (string) session()->get('checkout_token');
            if (! preg_match('/\A[a-f0-9]{64}\z/i', $checkoutToken)) {
                $checkoutToken = bin2hex(random_bytes(32));
                session()->set('checkout_token', $checkoutToken);
            }
            $data['checkoutToken'] = $checkoutToken;
        }

        if ($page === 'extras') {
            $data['extras'] = (new ExtraModel())
                ->where('aktif', 1)
                ->orderBy('id', 'ASC')
                ->findAll();
        }

        if ($page === 'cart' || $page === 'booking') {
            $mainPackageId = null;
            $existingExtraIds = [];

            foreach ($items as $item) {
                if (($item['type'] ?? '') === 'package') {
                    $mainPackageId = (int) $item['id'];
                } elseif (($item['type'] ?? '') === 'extra') {
                    $existingExtraIds[] = (int) $item['id'];
                }
            }

            $recommendations = [];
            if ($mainPackageId !== null) {
                $apriori = new \App\Libraries\AprioriService();
                $recommendations = $apriori->getRecommendationsForPackage($mainPackageId, $existingExtraIds, 4);

                if ($recommendations === [] && db_connect()->table('aturan_asosiasi_apriori')->countAllResults() === 0) {
                    $apriori->trainRules();
                    $recommendations = $apriori->getRecommendationsForPackage($mainPackageId, $existingExtraIds, 4);
                }
            }
            $data['crossSellingItems'] = $recommendations;
        }

        return view('customer/flow', $data);
    }
    


    /** Validasi bukti pembayaran yang diunggah pelanggan. */
    private function validatedPaymentProofUpload(): ?\CodeIgniter\HTTP\Files\UploadedFile
    {
        $file = $this->request->getFile('bukti_pembayaran');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $file->isValid() || $file->hasMoved()) {
            throw new \DomainException('File bukti pembayaran tidak berhasil dibaca. Silakan pilih ulang file.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \DomainException('Ukuran bukti pembayaran maksimal 5 MB.');
        }
        $extension = strtolower($file->getClientExtension());
        $mime = strtolower((string) $file->getMimeType());
        $allowed = [
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
            'png' => ['image/png'], 'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
        ];
        if (! isset($allowed[$extension]) || ! in_array($mime, $allowed[$extension], true)) {
            throw new \DomainException('Format bukti harus JPG, JPEG, PNG, WEBP, atau PDF.');
        }
        return $file;
    }

    /** Simpan file bukti ke folder privat writable, bukan public. */
    private function storePaymentProof(\CodeIgniter\HTTP\Files\UploadedFile $file): string
    {
        $directory = WRITEPATH . 'uploads/payment-proofs';
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Folder penyimpanan bukti pembayaran tidak dapat dibuat.');
        }
        if (! is_writable($directory)) {
            throw new \RuntimeException('Folder penyimpanan bukti pembayaran tidak dapat ditulis.');
        }
        $filename = bin2hex(random_bytes(20)) . '.' . strtolower($file->getClientExtension());
        $file->move($directory, $filename, true);
        return $filename;
    }

    private function deletePaymentProofFile(?string $filename): void
    {
        if ($filename === null || $filename === '') return;
        $path = WRITEPATH . 'uploads/payment-proofs/' . basename($filename);
        if (is_file($path)) @unlink($path);
    }

    private function verifiedAmountPaid(array $order): float
    {
        $total = max(0, (float) ($order['total'] ?? 0));
        $status = (string) ($order['status_pembayaran'] ?? 'Belum Bayar');
        if ($status === 'Lunas') return $total;
        if ($status === 'DP') return min($total, max(0, (float) ($order['jumlah_dp'] ?? 0)));
        return 0.0;
    }

    /** Mengambil user ID aktif (baik dari sesi pelanggan maupun sesi admin). */
    private function effectiveUserId(): int
    {
        $userId = (int) session()->get('user_id');
        if ($userId > 0) {
            return $userId;
        }
        return (int) session()->get('admin_id');
    }

    /** Halaman pengajuan pelunasan untuk pesanan yang telah membayar DP. */
    public function settlement(int $orderId)
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) return redirect()->to('/login');

        $order = db_connect()->table('orders')
            ->where('id', $orderId)->where('user_id', $userId)->get()->getRowArray();
        if ($order === null) return redirect()->to('/riwayat-pesanan')->with('error', 'Pesanan tidak ditemukan.');

        $paid = $this->verifiedAmountPaid($order);
        $remaining = max(0, round((float) $order['total'] - $paid, 2));
        if (($order['status_pembayaran'] ?? '') !== 'DP' || $remaining <= 0 || ($order['status_pesanan'] ?? '') !== 'Diproses') {
            return redirect()->to('/invoice?id=' . $orderId)->with('error', 'Pesanan ini belum dapat mengajukan pelunasan.');
        }

        $latest = db_connect()->table('order_payments')->where('order_id', $orderId)->orderBy('id', 'DESC')->get()->getRowArray();
        if ($latest !== null && in_array($latest['status'] ?? '', ['Menunggu Verifikasi', 'Menunggu Pembayaran di Tempat'], true)) {
            return redirect()->to('/invoice?id=' . $orderId)->with('info', 'Pengajuan pelunasan sebelumnya masih menunggu tindak lanjut admin.');
        }

        return view('customer/flow', [
            'page' => 'settlement',
            'order' => $order,
            'remainingAmount' => $remaining,
            'latestSettlement' => $latest,
            'cart' => ['items' => [], 'count' => 0, 'total' => 0],
        ]);
    }

    /** Simpan permintaan pelunasan; pembayaran hanya diakui setelah admin memverifikasi. */
    public function submitSettlement(int $orderId)
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) return redirect()->to('/login');

        $method = trim((string) $this->request->getPost('metode_pelunasan'));
        if (! in_array($method, ['QRIS', 'Transfer Bank', 'Bayar di Tempat Acara'], true)) {
            return redirect()->back()->withInput()->with('error', 'Pilih metode pelunasan yang tersedia.');
        }

        $file = null;
        if ($method !== 'Bayar di Tempat Acara') {
            try {
                $file = $this->validatedPaymentProofUpload();
                if ($file === null) return redirect()->back()->withInput()->with('error', 'Unggah bukti transfer atau QRIS terlebih dahulu.');
            } catch (\DomainException $exception) {
                return redirect()->back()->withInput()->with('error', $exception->getMessage());
            }
        }

        $db = db_connect();
        if ($db->transBegin() === false) {
            return redirect()->back()->with('error', 'Pengajuan pelunasan belum dapat dimulai. Silakan coba kembali.');
        }

        $storedFilename = null;
        try {
            $order = $db->query('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE', [$orderId, $userId])->getRowArray();
            if ($order === null) throw new \DomainException('Pesanan tidak ditemukan.');
            if (($order['status_pembayaran'] ?? '') !== 'DP' || ($order['status_pesanan'] ?? '') !== 'Diproses') {
                throw new \DomainException('Pelunasan hanya tersedia untuk pesanan yang sudah membayar DP dan sedang diproses.');
            }

            $remaining = max(0, round((float) $order['total'] - $this->verifiedAmountPaid($order), 2));
            if ($remaining <= 0) throw new \DomainException('Tidak ada sisa tagihan pada pesanan ini.');

            $pendingCount = $db->table('order_payments')
                ->where('order_id', $orderId)
                ->whereIn('status', ['Menunggu Verifikasi', 'Menunggu Pembayaran di Tempat'])
                ->countAllResults();
            if ($pendingCount > 0) throw new \DomainException('Masih ada pengajuan pelunasan yang menunggu tindak lanjut admin.');

            if ($file !== null) $storedFilename = $this->storePaymentProof($file);

            $now = date('Y-m-d H:i:s');
            $paymentStatus = $method === 'Bayar di Tempat Acara' ? 'Menunggu Pembayaran di Tempat' : 'Menunggu Verifikasi';
            $inserted = $db->table('order_payments')->insert([
                'order_id' => $orderId,
                'jenis_pembayaran' => 'Pelunasan',
                'metode_pembayaran' => $method,
                'jumlah' => $remaining,
                'bukti_pembayaran' => $storedFilename,
                'status' => $paymentStatus,
                'catatan' => null,
                'diverifikasi_oleh' => null,
                'diverifikasi_pada' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if (! $inserted || $db->transStatus() === false || $db->transCommit() === false) {
                throw new \RuntimeException('Pengajuan pelunasan gagal disimpan.');
            }

            $message = $method === 'Bayar di Tempat Acara'
                ? 'Permintaan bayar di tempat tercatat. Admin akan mencatat penerimaan pembayaran saat acara.'
                : 'Bukti pelunasan berhasil dikirim. Status akan diperbarui setelah admin memverifikasi bukti.';
            return redirect()->to('/invoice?id=' . $orderId)->with('info', $message);
        } catch (\DomainException $exception) {
            $db->transRollback();
            $this->deletePaymentProofFile($storedFilename);
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $db->transRollback();
            $this->deletePaymentProofFile($storedFilename);
            log_message('error', 'Pengajuan pelunasan pesanan #{id} gagal: {message}', ['id' => $orderId, 'message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Pengajuan pelunasan gagal disimpan. Silakan coba kembali.');
        }
    }

    /** Upload bukti pembayaran awal melalui invoice untuk order yang belum bayar/ditolak. */
    public function uploadPaymentProof(int $orderId)
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) return redirect()->to('/login');
        $order = (new OrderModel())->where('id', $orderId)->where('user_id', $userId)->first();
        if ($order === null) return redirect()->to('/riwayat-pesanan')->with('error', 'Pesanan tidak ditemukan.');
        if (in_array((string) ($order['status_pesanan'] ?? ''), ['Selesai', 'Dibatalkan'], true)
            || ! in_array((string) ($order['status_pembayaran'] ?? ''), ['Belum Bayar', 'Ditolak'], true)) {
            return redirect()->to('/invoice?id=' . $orderId)->with('error', 'Bukti awal tidak dapat diunggah untuk status pesanan saat ini.');
        }
        $storedFilename = null;
        try {
            $file = $this->validatedPaymentProofUpload();
            if ($file === null) return redirect()->to('/invoice?id=' . $orderId)->with('error', 'Pilih file bukti pembayaran terlebih dahulu.');
            $storedFilename = $this->storePaymentProof($file);
            $db = db_connect();
            $updated = $db->table('orders')->where('id', $orderId)->where('user_id', $userId)
                ->whereIn('status_pembayaran', ['Belum Bayar', 'Ditolak'])
                ->update([
                    'bukti_pembayaran' => $storedFilename,
                    'bukti_diupload_pada' => date('Y-m-d H:i:s'),
                    'status_pembayaran' => 'Menunggu Verifikasi',
                    'catatan_verifikasi' => null,
                    'diverifikasi_oleh' => null,
                    'diverifikasi_pada' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            if ($updated === false || $db->affectedRows() !== 1) {
                throw new \DomainException('Status pesanan berubah saat bukti diunggah. Muat ulang invoice sebelum mencoba kembali.');
            }
            return redirect()->to('/invoice?id=' . $orderId)->with('success', 'Bukti pembayaran berhasil diunggah. Admin akan memeriksa bukti terlebih dahulu.');
        } catch (\DomainException $exception) {
            $this->deletePaymentProofFile($storedFilename);
            return redirect()->to('/invoice?id=' . $orderId)->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->deletePaymentProofFile($storedFilename);
            log_message('error', 'Upload bukti awal pesanan #{id} gagal: {message}', ['id' => $orderId, 'message' => $exception->getMessage()]);
            return redirect()->to('/invoice?id=' . $orderId)->with('error', 'Bukti pembayaran gagal disimpan. Silakan coba kembali.');
        }
    }

    public function extras(): string
    {
        return $this->show('extras');
    }

    public function cart(): string
    {
        return $this->show('cart');
    }

    public function apiKelurahanByKecamatan($kecamatanId = null)
    {
        $id = filter_var($kecamatanId, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return $this->response
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode([]));
        }

        $shippingModel = new \App\Models\ShippingZoneModel();
        $list = $shippingModel->getKelurahanByKecamatan((int) $id);

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody(json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** Langkah 1: tampilkan form informasi pemesan dan acara. */
    public function booking()
    {
        $items = $this->refreshCartFromDatabase();
        if ($items === []) {
            return redirect()->to('/keranjang')->with('error', 'Keranjang masih kosong. Silakan pilih paket atau tambahan dekorasi terlebih dahulu.');
        }

        return $this->show('booking');
    }

    /**
     * Langkah 2: pilih skema DP/Lunas dan kanal pembayaran.
     * Halaman ini hanya dapat dibuka setelah data booking disimpan.
     */
    public function payment()
    {
        $items = $this->refreshCartFromDatabase();
        if ($items === []) {
            return redirect()->to('/keranjang')->with('error', 'Keranjang masih kosong. Silakan pilih paket atau tambahan dekorasi terlebih dahulu.');
        }

        $draft = session()->get('booking_draft');
        if (! is_array($draft) || empty($draft['nama_pemesan']) || empty($draft['no_whatsapp']) || empty($draft['tanggal_acara']) || empty($draft['lokasi_acara']) || empty($draft['kelurahan_id'])) {
            return redirect()->to('/booking')->with('error', 'Lengkapi informasi pemesan, kecamatan, kelurahan, dan lokasi acara terlebih dahulu.');
        }

        return $this->show('payment');
    }

    /**
     * Langkah 1: validasi dan simpan detail booking sementara ke session.
     * Pesanan belum dibuat di database pada langkah ini.
     */
    public function saveBooking()
    {
        $items = $this->refreshCartFromDatabase();
        if ($items === []) {
            return redirect()->to('/keranjang')->with('error', 'Keranjang masih kosong.');
        }

        if (! $this->validate([
            'nama_pemesan'   => 'required|min_length[3]|max_length[150]',
            'no_whatsapp'    => 'required|min_length[8]|max_length[20]',
            'tanggal_acara'  => 'required|valid_date[Y-m-d]',
            'kecamatan_id'   => 'required|is_natural_no_zero',
            'kelurahan_id'   => 'required|is_natural_no_zero',
            'lokasi_acara'   => 'required|max_length[5000]',
            'checkout_token' => 'required|exact_length[64]',
        ], [
            'nama_pemesan' => [
                'required'   => 'Nama lengkap pemesan wajib diisi.',
                'min_length' => 'Nama lengkap pemesan minimal terdiri dari 3 karakter.',
                'max_length' => 'Nama lengkap pemesan maksimal 150 karakter.',
            ],
            'no_whatsapp' => [
                'required'   => 'Nomor WhatsApp wajib diisi.',
                'min_length' => 'Nomor WhatsApp tidak lengkap, minimal 8 digit angka.',
                'max_length' => 'Nomor WhatsApp maksimal 20 karakter.',
            ],
            'tanggal_acara' => [
                'required'   => 'Tanggal acara wajib dipilih.',
                'valid_date' => 'Format tanggal acara tidak valid.',
            ],
            'kecamatan_id' => [
                'required'           => 'Kecamatan lokasi acara wajib dipilih.',
                'is_natural_no_zero' => 'Kecamatan lokasi acara tidak valid.',
            ],
            'kelurahan_id' => [
                'required'           => 'Kelurahan/desa lokasi acara wajib dipilih.',
                'is_natural_no_zero' => 'Kelurahan/desa lokasi acara tidak valid.',
            ],
            'lokasi_acara' => [
                'required'   => 'Alamat lengkap lokasi acara wajib diisi.',
                'max_length' => 'Alamat lokasi acara terlalu panjang (maksimal 5000 karakter).',
            ],
            'checkout_token' => [
                'required'     => 'Sesi pemesanan tidak valid, silakan muat ulang halaman.',
                'exact_length' => 'Sesi pemesanan tidak valid, silakan muat ulang halaman.',
            ],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $eventDate = trim((string) $this->request->getPost('tanggal_acara'));
        $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
        if ($dateObject === false || $dateObject->format('Y-m-d') !== $eventDate || $eventDate < date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', 'Tanggal acara harus valid dan tidak boleh berada di masa lalu.');
        }

        // Anti Double-Booking: pastikan paket utama dalam keranjang belum dibooking pada tanggal ini
        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'package') {
                $conflict = db_connect()->table('orders')
                    ->where('package_id', (int) $item['id'])
                    ->where('tanggal_acara', $eventDate)
                    ->whereNotIn('status_pesanan', ['Dibatalkan'])
                    ->whereNotIn('status_pembayaran', ['Ditolak'])
                    ->countAllResults();
                if ($conflict > 0) {
                    return redirect()->back()->withInput()->with('error', 'Paket "' . esc($item['nama']) . '" sudah dibooking pada tanggal ' . date('d/m/Y', strtotime($eventDate)) . '. Silakan pilih tanggal lain untuk menghindari double-booking.');
                }
            }
        }

        $postedToken = strtolower(trim((string) $this->request->getPost('checkout_token')));
        $sessionToken = strtolower((string) session()->get('checkout_token'));
        if (! preg_match('/\\A[a-f0-9]{64}\\z/', $postedToken) || $sessionToken === '' || ! hash_equals($sessionToken, $postedToken)) {
            return redirect()->to('/booking')->with('error', 'Sesi booking sudah kedaluwarsa. Silakan muat ulang halaman booking.');
        }

        // Ambil data ongkos kirim berdasarkan kelurahan yang dipilih
        $kecamatanId = (int) $this->request->getPost('kecamatan_id');
        $kelurahanId = (int) $this->request->getPost('kelurahan_id');
        $ongkosKirim = 0.0;
        $namaKecamatan = '';
        $namaKelurahan = '';
        if ($kelurahanId > 0) {
            $shippingModel = new \App\Models\ShippingZoneModel();
            $kelDetail = $shippingModel->getKelurahanDetail($kelurahanId);
            if ($kelDetail !== null) {
                $ongkosKirim   = (float) ($kelDetail['ongkos_kirim'] ?? 0);
                $namaKecamatan = (string) ($kelDetail['nama_kecamatan'] ?? '');
                $namaKelurahan = (string) ($kelDetail['nama_kelurahan'] ?? '');
            }
        }

        $draft = [
            'nama_pemesan'   => trim((string) $this->request->getPost('nama_pemesan')),
            'no_whatsapp'    => trim((string) $this->request->getPost('no_whatsapp')),
            'tanggal_acara'  => $eventDate,
            'kecamatan_id'   => $kecamatanId,
            'kelurahan_id'   => $kelurahanId,
            'nama_kecamatan' => $namaKecamatan,
            'nama_kelurahan' => $namaKelurahan,
            'ongkos_kirim'   => $ongkosKirim,
            'lokasi_acara'   => trim((string) $this->request->getPost('lokasi_acara')),
        ];

        session()->set('booking_draft', $draft);

        return redirect()->to('/pembayaran')->with('success', 'Detail acara tersimpan. Silakan pilih skema dan metode pembayaran.');
    }

    public function account()
    {
        return $this->accountPage('account');
    }

    public function history()
    {
        return $this->accountPage('history');
    }

    public function invoice()
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) {
            return redirect()->to('/login');
        }

        $orderId = filter_var($this->request->getGet('id'), FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId < 1) {
            return redirect()->to('/riwayat-pesanan')->with('error', 'Invoice tidak ditemukan.');
        }

        $orderQuery = (new OrderModel())->where('id', $orderId);
        if (! session('admin_id')) {
            $orderQuery->where('user_id', $userId);
        }
        $order = $orderQuery->first();

        if (! $order) {
            return redirect()->to('/riwayat-pesanan')->with('error', 'Invoice tidak ditemukan.');
        }

        $orderItems = [];

        // Gunakan snapshot paket saat pesanan dibuat. Untuk pesanan lama
        // sebelum snapshot tersedia, gunakan data paket yang masih ada di database.
        if (! empty($order['package_id'])) {
            $packageName = trim((string) ($order['package_name'] ?? ''));
            $packagePrice = $order['package_price'] ?? null;

            if ($packageName === '' || $packagePrice === null) {
                $currentPackage = (new PackageModel())->find((int) $order['package_id']);
                if ($currentPackage !== null) {
                    if ($packageName === '') {
                        $packageName = (string) $currentPackage['nama_paket'];
                    }
                    if ($packagePrice === null) {
                        $packagePrice = $currentPackage['harga'];
                    }
                }
            }

            if ($packageName !== '' && $packagePrice !== null) {
                $price = (float) $packagePrice;
                $orderItems[] = [
                    'nama'       => $packageName,
                    'keterangan' => 'Paket dekorasi',
                    'jumlah'     => 1,
                    'harga'      => $price,
                    'subtotal'   => $price,
                ];
            }
        }

        // order_extras menyimpan nama dan harga sebagai snapshot transaksi.
        $extraRows = db_connect()
            ->table('order_extras')
            ->where('order_id', $orderId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($extraRows as $extra) {
            $quantity = max(1, (int) ($extra['jumlah'] ?? 1));
            $price = (float) ($extra['harga'] ?? 0);
            $orderItems[] = [
                'nama'       => (string) ($extra['nama'] ?? 'Tambahan dekorasi'),
                'keterangan' => 'Tambahan dekorasi',
                'jumlah'     => $quantity,
                'harga'      => $price,
                'subtotal'   => round($price * $quantity, 2),
            ];
        }

        $settlementPayments = db_connect()->table('order_payments')
            ->where('order_id', $orderId)->orderBy('id', 'DESC')->get()->getResultArray();

        return view('customer/flow', [
            'page' => 'invoice',
            'order' => $order,
            'orderItems' => $orderItems,
            'settlementPayments' => $settlementPayments,
            'latestSettlement' => $settlementPayments[0] ?? null,
            'cart' => ['items' => [], 'count' => 0, 'total' => 0],
        ]);
    }

    public function addPackage(int $id)
    {
        $package = (new PackageModel())
            ->where('aktif', 1)
            ->find($id);

        if ($package === null) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Paket tidak ditemukan.']);
            }
            return redirect()->back()->with('error', 'Paket tidak ditemukan atau sudah tidak tersedia.');
        }

        $cart = $this->refreshCartFromDatabase();
        $existingPackage = null;

        foreach ($cart as $item) {
            if (($item['type'] ?? '') === 'package') {
                $existingPackage = $item;
                break;
            }
        }

        if ($existingPackage !== null && (int) $existingPackage['id'] !== $id) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Keranjang hanya dapat berisi satu paket utama. Hapus paket saat ini sebelum memilih paket lain.'
                ]);
            }
            return redirect()->to('/keranjang')->with('error', 'Keranjang hanya dapat berisi satu paket utama. Hapus paket saat ini sebelum memilih paket lain.');
        }

        $eventDate = trim((string) $this->request->getPost('tanggal_acara'));
        if ($eventDate !== '') {
            $conflict = db_connect()->table('orders')
                ->where('package_id', $id)
                ->where('tanggal_acara', $eventDate)
                ->whereNotIn('status_pesanan', ['Dibatalkan'])
                ->whereNotIn('status_pembayaran', ['Ditolak'])
                ->countAllResults();
            if ($conflict > 0) {
                if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'message' => 'Paket ini sudah dibooking pada tanggal ' . date('d/m/Y', strtotime($eventDate)) . '. Silakan pilih tanggal lain.'
                    ]);
                }
                return redirect()->back()->withInput()->with('error', 'Paket ini sudah dibooking pada tanggal terpilih.');
            }

            $draft = session()->get('booking_draft');
            if (! is_array($draft)) $draft = [];
            $draft['tanggal_acara'] = $eventDate;
            session()->set('booking_draft', $draft);
        }

        $key = 'package_' . (int) $package['id'];
        $cart[$key] = [
            'key'    => $key,
            'type'   => 'package',
            'id'     => (int) $package['id'],
            'nama'   => (string) $package['nama_paket'],
            'harga'  => (float) $package['harga'],
            'jumlah' => 1,
            'stok'   => null,
        ];

        session()->set('cart', $cart);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
            $summary = $this->makeCartSummary($cart);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Paket ' . $package['nama_paket'] . ' berhasil ditambahkan ke keranjang.',
                'cartCount' => $summary['count'],
            ]);
        }

        return redirect()->to('/keranjang')->with('success', 'Paket ' . $package['nama_paket'] . ' berhasil dimasukkan ke keranjang.');
    }

    public function directBook(int $id)
    {
        $package = (new PackageModel())
            ->where('aktif', 1)
            ->find($id);

        if ($package === null) {
            return redirect()->back()->with('error', 'Paket tidak ditemukan.');
        }

        $eventDate = trim((string) $this->request->getPost('tanggal_acara'));
        if ($eventDate === '') {
            return redirect()->back()->withInput()->with('error', 'Silakan pilih tanggal acara terlebih dahulu pada kalender ketersediaan.');
        }

        $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
        if ($dateObject === false || $dateObject->format('Y-m-d') !== $eventDate || $eventDate < date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', 'Tanggal acara tidak valid atau sudah berlalu.');
        }

        // Cek double booking
        $conflict = db_connect()->table('orders')
            ->where('package_id', $id)
            ->where('tanggal_acara', $eventDate)
            ->whereNotIn('status_pesanan', ['Dibatalkan'])
            ->whereNotIn('status_pembayaran', ['Ditolak'])
            ->countAllResults();
        if ($conflict > 0) {
            return redirect()->back()->withInput()->with('error', 'Paket ini sudah dibooking pada tanggal ' . date('d/m/Y', strtotime($eventDate)) . '. Silakan pilih tanggal lain.');
        }

        // Set cart untuk paket langsung ini (menghapus paket utama lain)
        $cart = $this->refreshCartFromDatabase();
        foreach ($cart as $k => $item) {
            if (($item['type'] ?? '') === 'package') {
                unset($cart[$k]);
            }
        }
        $key = 'package_' . (int) $package['id'];
        $cart[$key] = [
            'key'    => $key,
            'type'   => 'package',
            'id'     => (int) $package['id'],
            'nama'   => (string) $package['nama_paket'],
            'harga'  => (float) $package['harga'],
            'jumlah' => 1,
            'stok'   => null,
        ];
        session()->set('cart', $cart);

        // Set draft booking dengan tanggal yang sudah dipilih
        $draft = session()->get('booking_draft');
        if (! is_array($draft)) $draft = [];
        $draft['tanggal_acara'] = $eventDate;
        session()->set('booking_draft', $draft);

        return redirect()->to('/booking')->with('success', 'Paket dan tanggal acara berhasil dipilih. Silakan lengkapi detail pesanan Anda.');
    }

    public function apiBookedDates(int $packageId)
    {
        $rows = db_connect()->table('orders')
            ->select('tanggal_acara')
            ->where('package_id', $packageId)
            ->where('tanggal_acara >=', date('Y-m-d'))
            ->whereNotIn('status_pesanan', ['Dibatalkan'])
            ->whereNotIn('status_pembayaran', ['Ditolak'])
            ->get()
            ->getResultArray();

        $dates = array_values(array_unique(array_filter(array_column($rows, 'tanggal_acara'))));
        return $this->response->setJSON([
            'package_id' => $packageId,
            'booked_dates' => $dates,
        ]);
    }

    public function addExtra(int $id)
    {
        $extraModel = new ExtraModel();
        $extra = $extraModel
            ->where('aktif', 1)
            ->find($id);

        if ($extra === null) {
            return redirect()->back()->with('error', 'Tambahan dekorasi tidak ditemukan atau sudah tidak tersedia.');
        }

        $cart = $this->refreshCartFromDatabase();
        $key = 'extra_' . (int) $extra['id'];
        $currentQuantity = (int) ($cart[$key]['jumlah'] ?? 0);
        $availableStock = (int) ($extra['stok'] ?? 0);

        if ($availableStock < 1 || $currentQuantity >= $availableStock) {
            return redirect()->back()->with('error', 'Stok ' . $extra['nama'] . ' tidak mencukupi.');
        }

        $cart[$key] = [
            'key'    => $key,
            'type'   => 'extra',
            'id'     => (int) $extra['id'],
            'nama'   => (string) $extra['nama'],
            'harga'  => (float) $extra['harga'],
            'jumlah' => $currentQuantity + 1,
            'stok'   => $availableStock,
        ];

        session()->set('cart', $cart);

        return redirect()->to('/keranjang')->with('success', 'Tambahan ' . $extra['nama'] . ' berhasil ditambahkan ke keranjang.');
    }

    public function updateCart(string $key)
    {
        $isAjax = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' || str_contains($this->request->getHeaderLine('Accept'), 'application/json');

        if (! preg_match('/^(package|extra)_([0-9]+)$/', $key, $matches)) {
            if ($isAjax) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Item keranjang tidak valid.']);
            return redirect()->to('/keranjang')->with('error', 'Item keranjang tidak valid.');
        }

        $type = $matches[1];
        $id = (int) $matches[2];
        $cart = $this->refreshCartFromDatabase();

        if (! isset($cart[$key])) {
            if ($isAjax) return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Item tidak ditemukan atau sudah tidak tersedia.']);
            return redirect()->to('/keranjang')->with('error', 'Item tidak ditemukan atau sudah tidak tersedia.');
        }

        if ($type === 'package') {
            if ($isAjax) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Paket utama hanya dapat dipilih satu kali.']);
            return redirect()->to('/keranjang')->with('error', 'Paket utama hanya dapat dipilih satu kali.');
        }

        $requestedQuantity = filter_var(
            $this->request->getPost('jumlah'),
            FILTER_VALIDATE_INT
        );

        if ($requestedQuantity === false || $requestedQuantity < 1) {
            if ($isAjax) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Jumlah item minimal 1.']);
            return redirect()->to('/keranjang')->with('error', 'Jumlah item minimal 1.');
        }

        $extra = (new ExtraModel())
            ->where('aktif', 1)
            ->find($id);

        if ($extra === null) {
            unset($cart[$key]);
            session()->set('cart', $cart);
            if ($isAjax) return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Tambahan dekorasi sudah tidak tersedia.']);
            return redirect()->to('/keranjang')->with('error', 'Tambahan dekorasi sudah tidak tersedia dan dihapus dari keranjang.');
        }

        $availableStock = (int) ($extra['stok'] ?? 0);
        if ($availableStock < 1 || $requestedQuantity > $availableStock) {
            if ($isAjax) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Jumlah melebihi stok yang tersedia. Stok saat ini: ' . $availableStock . '.',
                    'availableStock' => $availableStock,
                ]);
            }
            return redirect()->to('/keranjang')->with('error', 'Jumlah melebihi stok yang tersedia. Stok saat ini: ' . $availableStock . '.');
        }

        $cart[$key] = [
            'key'    => $key,
            'type'   => 'extra',
            'id'     => (int) $extra['id'],
            'nama'   => (string) $extra['nama'],
            'harga'  => (float) $extra['harga'],
            'jumlah' => (int) $requestedQuantity,
            'stok'   => $availableStock,
        ];

        session()->set('cart', $cart);

        if ($isAjax) {
            $summary = $this->makeCartSummary($cart);
            $lineTotal = (float) $extra['harga'] * (int) $requestedQuantity;
            return $this->response->setJSON([
                'success' => true,
                'key' => $key,
                'quantity' => (int) $requestedQuantity,
                'price' => (float) $extra['harga'],
                'lineTotal' => $lineTotal,
                'lineTotalFormatted' => 'Rp ' . number_format($lineTotal, 0, ',', '.'),
                'priceFormatted' => 'Rp ' . number_format((float) $extra['harga'], 0, ',', '.'),
                'cartCount' => $summary['count'],
                'cartTotal' => $summary['total'],
                'cartTotalFormatted' => 'Rp ' . number_format($summary['total'], 0, ',', '.'),
            ]);
        }

        return redirect()->to('/keranjang')->with('success', 'Jumlah ' . $extra['nama'] . ' berhasil diperbarui.');
    }

    public function removeCart(string $key)
    {
        if (! preg_match('/^(package|extra)_([0-9]+)$/', $key)) {
            return redirect()->to('/keranjang')->with('error', 'Item keranjang tidak valid.');
        }

        $cart = $this->refreshCartFromDatabase();
        if (! isset($cart[$key])) {
            return redirect()->to('/keranjang')->with('error', 'Item tidak ditemukan di keranjang.');
        }

        unset($cart[$key]);
        session()->set('cart', $cart);

        return redirect()->to('/keranjang')->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    public function updateAccount()
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) {
            return redirect()->to('/login');
        }

        if (! $this->validate([
            'nama_lengkap' => 'required|min_length[3]',
            'no_whatsapp'  => 'required|min_length[8]',
        ], [
            'nama_lengkap' => [
                'required'   => 'Nama lengkap wajib diisi.',
                'min_length' => 'Nama lengkap minimal terdiri dari 3 karakter.',
            ],
            'no_whatsapp' => [
                'required'   => 'Nomor WhatsApp wajib diisi.',
                'min_length' => 'Nomor WhatsApp tidak lengkap, minimal 8 digit angka.',
            ],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name = (string) $this->request->getPost('nama_lengkap');
        $phone = (string) $this->request->getPost('no_whatsapp');

        (new UserModel())->update($userId, [
            'nama_lengkap' => $name,
            'no_whatsapp'  => $phone,
        ]);

        if (session('admin_id')) {
            session()->set([
                'admin_name'  => $name,
                'admin_phone' => $phone,
            ]);
        } else {
            session()->set([
                'user_name'  => $name,
                'user_phone' => $phone,
            ]);
        }

        return redirect()->to('/akun')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword()
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) {
            return redirect()->to('/login');
        }

        if (! $this->validate([
            'password_saat_ini'   => 'required|min_length[6]',
            'password_baru'       => 'required|min_length[8]',
            'konfirmasi_password' => 'required|matches[password_baru]',
        ], [
            'password_saat_ini' => [
                'required'   => 'Password saat ini wajib diisi.',
                'min_length' => 'Password saat ini minimal 6 karakter.',
            ],
            'password_baru' => [
                'required'   => 'Password baru wajib diisi.',
                'min_length' => 'Password baru minimal 8 karakter.',
            ],
            'konfirmasi_password' => [
                'required' => 'Konfirmasi password baru wajib diisi.',
                'matches'  => 'Konfirmasi password tidak cocok dengan password baru.',
            ],
        ])) {
            return redirect()->to('/akun')->withInput()->with('password_modal_open', true)->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if ($user === null) {
            return redirect()->to('/login')->with('error', 'Sesi akun tidak valid.');
        }

        $currentPassword = (string) $this->request->getPost('password_saat_ini');
        if (! password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
            return redirect()->to('/akun')->withInput()->with('password_modal_open', true)->with('error', 'Password saat ini tidak sesuai.');
        }

        $newPassword = (string) $this->request->getPost('password_baru');
        $userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/akun')->with('success', 'Password Anda berhasil diubah.');
    }

    /**
     * Menyimpan header pesanan dan seluruh tambahan dalam satu transaksi DB.
     * Harga/nama selalu dibaca ulang dari database; Session hanya menyimpan ID
     * dan kuantitas. Stok tambahan direservasi saat checkout dan akan dilepas
     * oleh admin ketika pesanan selesai atau dibatalkan.
     */
    public function checkout()
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        // Skema dan kanal dipost pada langkah pembayaran, bukan di form booking.
        $method = trim((string) $this->request->getPost('skema_pembayaran'));
        $paymentChannel = trim((string) $this->request->getPost('kanal_pembayaran'));
        if (! in_array($method, ['DP', 'Lunas'], true)) {
            return redirect()->back()->withInput()->with('error', 'Pilih skema DP 50% atau Lunas 100%.');
        }
        if (! in_array($paymentChannel, ['QRIS', 'Transfer Bank'], true)) {
            return redirect()->back()->withInput()->with('error', 'Pilih metode QRIS atau Transfer Bank.');
        }

        $bookingDraft = session()->get('booking_draft');
        if (! is_array($bookingDraft)
            || empty($bookingDraft['nama_pemesan'])
            || empty($bookingDraft['no_whatsapp'])
            || empty($bookingDraft['tanggal_acara'])
            || empty($bookingDraft['lokasi_acara'])
            || empty($bookingDraft['kelurahan_id'])) {
            return redirect()->to('/booking')->with('error', 'Data booking belum lengkap. Silakan lengkapi form booking terlebih dahulu.');
        }

        $eventDate = (string) $bookingDraft['tanggal_acara'];
        $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
        if ($dateObject === false || $dateObject->format('Y-m-d') !== $eventDate || $eventDate < date('Y-m-d')) {
            return redirect()->to('/booking')->with('error', 'Tanggal acara harus valid dan tidak boleh berada di masa lalu.');
        }

        if (! $this->validate([
            'checkout_token' => 'required|exact_length[64]',
        ], [
            'checkout_token' => [
                'required'     => 'Sesi pembayaran tidak valid, silakan muat ulang halaman.',
                'exact_length' => 'Sesi pembayaran tidak valid, silakan muat ulang halaman.',
            ],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = (new UserModel())->find($userId);
        if ($user === null) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Sesi akun tidak valid. Silakan masuk kembali.');
        }

        $storedCart = session()->get('cart');
        if (! is_array($storedCart) || $storedCart === []) {
            return redirect()->to('/keranjang')->with('error', 'Keranjang kosong. Silakan pilih paket atau tambahan dekorasi terlebih dahulu.');
        }

        $checkoutToken = strtolower(trim((string) $this->request->getPost('checkout_token')));
        if (! preg_match('/\A[a-f0-9]{64}\z/', $checkoutToken)) {
            return redirect()->to('/booking')->with('error', 'Token checkout tidak valid. Silakan buka ulang halaman booking.');
        }

        $db = db_connect();

        // Idempotency: jika form yang sama terkirim ulang, jangan buat transaksi kedua.
        $existingOrder = $db->table('orders')
            ->where('checkout_token', $checkoutToken)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();
        if ($existingOrder !== null) {
            session()->remove(['cart', 'checkout_token', 'payment_scheme', 'payment_channel', 'booking_draft']);
            return redirect()->to('/invoice?id=' . (int) $existingOrder['id'])
                ->with('success', 'Pesanan ini sudah tercatat sebelumnya.');
        }

        $sessionToken = (string) session()->get('checkout_token');
        if ($sessionToken === '' || ! hash_equals($sessionToken, $checkoutToken)) {
            return redirect()->to('/booking')->with('error', 'Form booking sudah kedaluwarsa. Silakan periksa kembali keranjang dan buka halaman booking lagi.');
        }

        // Urutan penguncian konsisten mengurangi kemungkinan deadlock ketika
        // dua pelanggan memesan tambahan yang sama pada waktu bersamaan.
        ksort($storedCart, SORT_STRING);

        if ($db->transBegin() === false) {
            log_message('error', 'Checkout Nauli Dekor gagal memulai transaksi database.');
            return redirect()->back()->withInput()->with('error', 'Pesanan belum dapat diproses. Silakan coba kembali.');
        }

        $initialProofFilename = null;
        try {
            $initialProofFile = $this->validatedPaymentProofUpload();
            if ($initialProofFile !== null) {
                $initialProofFilename = $this->storePaymentProof($initialProofFile);
            }

            $package = null;
            $extraDetails = [];
            $totalCents = 0;
            $seenExtraIds = [];

            foreach ($storedCart as $storedKey => $storedItem) {
                if (! is_array($storedItem)) {
                    throw new \DomainException('Terdapat item keranjang yang tidak valid. Silakan periksa kembali keranjang Anda.');
                }

                $type = (string) ($storedItem['type'] ?? '');
                $itemId = filter_var($storedItem['id'] ?? null, FILTER_VALIDATE_INT);
                if ($itemId === false || $itemId < 1 || ! in_array($type, ['package', 'extra'], true)) {
                    throw new \DomainException('Terdapat item keranjang yang tidak valid. Silakan periksa kembali keranjang Anda.');
                }

                $expectedKey = $type . '_' . (int) $itemId;
                if ((string) $storedKey !== $expectedKey) {
                    throw new \DomainException('Identitas item keranjang tidak sesuai. Silakan tambahkan kembali item tersebut.');
                }

                if ($type === 'package') {
                    if ($package !== null) {
                        throw new \DomainException('Satu pesanan hanya dapat menggunakan satu paket utama.');
                    }

                    $packageRow = $db->query(
                        'SELECT id, nama_paket, harga FROM packages WHERE id = ? AND aktif = 1 FOR UPDATE',
                        [(int) $itemId]
                    )->getRowArray();

                    if ($packageRow === null) {
                        throw new \DomainException('Paket sudah tidak tersedia. Silakan pilih paket lain.');
                    }

                    $priceCents = (int) round(((float) $packageRow['harga']) * 100);
                    if ($priceCents < 0) {
                        throw new \DomainException('Harga paket tidak valid. Hubungi pengelola Nauli Dekor.');
                    }

                    $package = [
                        'id'    => (int) $packageRow['id'],
                        'nama'  => (string) $packageRow['nama_paket'],
                        'harga' => $priceCents / 100,
                    ];
                    $totalCents += $priceCents;
                    continue;
                }

                if (isset($seenExtraIds[$itemId])) {
                    throw new \DomainException('Tambahan dekorasi yang sama terduplikasi di keranjang. Hapus lalu tambahkan kembali.');
                }
                $seenExtraIds[$itemId] = true;

                $quantity = filter_var($storedItem['jumlah'] ?? null, FILTER_VALIDATE_INT);
                if ($quantity === false || $quantity < 1) {
                    throw new \DomainException('Jumlah tambahan dekorasi tidak valid. Silakan periksa keranjang Anda.');
                }

                $extraRow = $db->query(
                    'SELECT id, nama, harga, stok FROM extras WHERE id = ? AND aktif = 1 FOR UPDATE',
                    [(int) $itemId]
                )->getRowArray();

                if ($extraRow === null) {
                    throw new \DomainException('Salah satu tambahan dekorasi sudah tidak tersedia. Silakan periksa keranjang Anda.');
                }

                $availableStock = (int) $extraRow['stok'];
                if ($quantity > $availableStock) {
                    throw new \DomainException(
                        'Stok ' . $extraRow['nama'] . ' tidak mencukupi. Stok tersedia saat ini: ' . $availableStock . '. Silakan perbarui keranjang.'
                    );
                }

                $priceCents = (int) round(((float) $extraRow['harga']) * 100);
                if ($priceCents < 0) {
                    throw new \DomainException('Harga tambahan dekorasi tidak valid. Hubungi pengelola Nauli Dekor.');
                }

                $unitPrice = $priceCents / 100;
                $totalCents += $priceCents * $quantity;

                $extraDetails[] = [
                    'extra_id' => (int) $extraRow['id'],
                    'nama'     => (string) $extraRow['nama'],
                    'harga'    => $unitPrice,
                    'jumlah'   => (int) $quantity,
                ];
            }

            if ($package === null && $extraDetails === []) {
                throw new \DomainException('Keranjang tidak berisi item yang dapat dipesan.');
            }

            if ($totalCents <= 0) {
                throw new \DomainException('Total pesanan tidak valid. Silakan periksa kembali pilihan Anda.');
            }

            $ongkosKirim = max(0, (float) ($bookingDraft['ongkos_kirim'] ?? 0));
            $itemsTotal = $totalCents / 100;
            $total = round($itemsTotal + $ongkosKirim, 2);
            $initialPayment = $method === 'DP'
                ? round($total * 0.5, 2)
                : $total;

            // Kurangi stok dalam transaksi yang sama dengan pembuatan pesanan.
            // Jika salah satu update gagal, seluruh checkout akan di-rollback.
            foreach ($extraDetails as $detail) {
                $stockUpdate = $db->query(
                    'UPDATE extras SET stok = stok - ?, updated_at = ? WHERE id = ? AND aktif = 1 AND stok >= ?',
                    [$detail['jumlah'], date('Y-m-d H:i:s'), $detail['extra_id'], $detail['jumlah']]
                );
                if ($stockUpdate === false || $db->affectedRows() !== 1) {
                    throw new \DomainException('Stok ' . $detail['nama'] . ' berubah saat checkout. Silakan periksa kembali keranjang Anda.');
                }
            }

            do {
                $orderCode = 'ND-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $codeExists = $db->table('orders')->where('kode_pesanan', $orderCode)->countAllResults() > 0;
            } while ($codeExists);

            $now = date('Y-m-d H:i:s');
            $orderData = [
                'kode_pesanan'      => $orderCode,
                'checkout_token'    => $checkoutToken,
                'stock_reserved'    => $extraDetails !== [] ? 1 : 0,
                'user_id'           => $userId,
                'package_id'        => $package['id'] ?? null,
                'package_name'      => $package['nama'] ?? null,
                'package_price'     => $package['harga'] ?? null,
                'nama_pemesan'      => (string) $bookingDraft['nama_pemesan'],
                'no_whatsapp'       => (string) $bookingDraft['no_whatsapp'],
                'email'             => ! empty($user['email']) ? $user['email'] : null,
                'tanggal_acara'     => $eventDate,
                'waktu_acara'       => null,
                'kecamatan_id'      => ! empty($bookingDraft['kecamatan_id']) ? (int) $bookingDraft['kecamatan_id'] : null,
                'kelurahan_id'      => ! empty($bookingDraft['kelurahan_id']) ? (int) $bookingDraft['kelurahan_id'] : null,
                'nama_kecamatan'    => ! empty($bookingDraft['nama_kecamatan']) ? (string) $bookingDraft['nama_kecamatan'] : null,
                'nama_kelurahan'    => ! empty($bookingDraft['nama_kelurahan']) ? (string) $bookingDraft['nama_kelurahan'] : null,
                'ongkos_kirim'      => $ongkosKirim,
                'lokasi_acara'      => (string) $bookingDraft['lokasi_acara'],
                'metode_pembayaran' => $method,
                'kanal_pembayaran'  => $paymentChannel,
                'total'             => $total,
                'jumlah_dp'         => $initialPayment,
                'bukti_pembayaran' => $initialProofFilename,
                'bukti_diupload_pada' => $initialProofFilename !== null ? $now : null,
                'status_pembayaran' => $initialProofFilename !== null ? 'Menunggu Verifikasi' : 'Belum Bayar',
                'status_pesanan'    => 'Pending',
                'status_dekorasi'   => 'Menunggu Pembayaran',
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            if (! $db->table('orders')->insert($orderData)) {
                throw new \RuntimeException('Gagal menyimpan data utama pesanan.');
            }

            $orderId = (int) $db->insertID();
            if ($orderId < 1) {
                throw new \RuntimeException('ID pesanan tidak berhasil dibuat.');
            }

            if ($extraDetails !== []) {
                $orderExtraRows = [];
                foreach ($extraDetails as $detail) {
                    $orderExtraRows[] = [
                        'order_id' => $orderId,
                        'extra_id' => $detail['extra_id'],
                        'nama'     => $detail['nama'],
                        'harga'    => $detail['harga'],
                        'jumlah'   => $detail['jumlah'],
                    ];
                }

                if ($db->table('order_extras')->insertBatch($orderExtraRows) === false) {
                    throw new \RuntimeException('Gagal menyimpan rincian tambahan dekorasi.');
                }
            }

            if ($db->transStatus() === false || $db->transCommit() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            try {
                (new \App\Libraries\AprioriService())->trainRules();
            } catch (\Throwable $e) {
                log_message('warning', 'Pembaruan aturan Apriori tertunda: ' . $e->getMessage());
            }

            session()->remove(['cart', 'checkout_token', 'payment_scheme', 'payment_channel', 'booking_draft']);

            return redirect()->to('/invoice?id=' . $orderId)
                ->with('order_created', 'Pesanan ' . $orderCode . ' berhasil dibuat. Silakan periksa invoice Anda.');
        } catch (\DomainException $exception) {
            $db->transRollback();
            $this->deletePaymentProofFile($initialProofFilename);
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $db->transRollback();
            $this->deletePaymentProofFile($initialProofFilename);

            // Dua submit simultan dengan token yang sama hanya boleh menghasilkan satu order.
            try {
                $duplicateOrder = $db->table('orders')
                    ->where('checkout_token', $checkoutToken)
                    ->where('user_id', $userId)
                    ->get()->getRowArray();
                if ($duplicateOrder !== null) {
                    session()->remove(['cart', 'checkout_token', 'payment_scheme', 'payment_channel', 'booking_draft']);
                    return redirect()->to('/invoice?id=' . (int) $duplicateOrder['id'])
                        ->with('success', 'Pesanan ini sudah tercatat sebelumnya.');
                }
            } catch (\Throwable $ignored) {
                // Pertahankan pesan error umum tanpa membocorkan detail database.
            }

            log_message('error', 'Checkout Nauli Dekor gagal: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Pesanan belum berhasil disimpan. Tidak ada perubahan transaksi yang disimpan. Silakan coba kembali.');
        }
    }


    private function accountPage(string $page)
    {
        $userId = $this->effectiveUserId();
        if ($userId < 1) {
            return redirect()->to('/login');
        }

        $user = (new UserModel())->find($userId);
        if ($user === null) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Sesi akun tidak valid. Silakan masuk kembali.');
        }

        $orders = [];
        if ($page === 'history') {
            // Ambil pesanan milik pelanggan yang sedang login.
            // Jika akun admin sedang memantau, tampilkan pesanan admin atau preview pesanan terkini.
            $orderModel = new OrderModel();
            if (! session('admin_id')) {
                $orderModel->where('user_id', $userId);
            } else {
                $adminOrderCount = (new OrderModel())->where('user_id', $userId)->countAllResults();
                if ($adminOrderCount > 0) {
                    $orderModel->where('user_id', $userId);
                }
            }

            $orders = $orderModel
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll();

            if ($orders !== []) {
                $orderIds = array_map(static fn (array $row): int => (int) $row['id'], $orders);

                $packageIds = [];
                foreach ($orders as $row) {
                    if (! empty($row['package_id']) && empty($row['package_name'])) {
                        $packageIds[] = (int) $row['package_id'];
                    }
                }

                $packageNames = [];
                if ($packageIds !== []) {
                    $packageRows = (new PackageModel())
                        ->select('id, nama_paket')
                        ->whereIn('id', array_values(array_unique($packageIds)))
                        ->findAll();

                    foreach ($packageRows as $packageRow) {
                        $packageNames[(int) $packageRow['id']] = (string) $packageRow['nama_paket'];
                    }
                }

                // Ambil ringkasan tambahan dalam satu query untuk menghindari
                // query berulang pada setiap baris riwayat pesanan.
                $extraSummaryRows = db_connect()->table('order_extras')
                    ->select('order_id, COUNT(*) AS extra_type_count, COALESCE(SUM(jumlah), 0) AS extra_quantity')
                    ->whereIn('order_id', $orderIds)
                    ->groupBy('order_id')
                    ->get()
                    ->getResultArray();

                $extraSummaryByOrder = [];
                foreach ($extraSummaryRows as $summaryRow) {
                    $extraSummaryByOrder[(int) $summaryRow['order_id']] = [
                        'type_count' => (int) $summaryRow['extra_type_count'],
                        'quantity'   => (int) $summaryRow['extra_quantity'],
                    ];
                }

                $settlementRows = db_connect()->table('order_payments')
                    ->whereIn('order_id', $orderIds)->orderBy('id', 'DESC')->get()->getResultArray();
                $latestSettlementByOrder = [];
                foreach ($settlementRows as $settlementRow) {
                    $settlementOrderId = (int) $settlementRow['order_id'];
                    if (! isset($latestSettlementByOrder[$settlementOrderId])) {
                        $latestSettlementByOrder[$settlementOrderId] = $settlementRow;
                    }
                }

                foreach ($orders as &$orderRow) {
                    $orderId = (int) $orderRow['id'];
                    $packageName = trim((string) ($orderRow['package_name'] ?? ''));

                    if ($packageName === '' && ! empty($orderRow['package_id'])) {
                        $packageName = $packageNames[(int) $orderRow['package_id']] ?? '';
                    }

                    $orderRow['display_package_name'] = $packageName !== ''
                        ? $packageName
                        : (! empty($orderRow['package_id']) ? 'Paket tidak tersedia' : 'Tambahan dekorasi');

                    $extraSummary = $extraSummaryByOrder[$orderId] ?? [
                        'type_count' => 0,
                        'quantity'   => 0,
                    ];
                    $orderRow['extra_type_count'] = $extraSummary['type_count'];
                    $orderRow['extra_quantity'] = $extraSummary['quantity'];
                    $orderRow['latest_settlement'] = $latestSettlementByOrder[$orderId] ?? null;
                    $orderRow['remaining_amount'] = max(0, round((float) ($orderRow['total'] ?? 0) - $this->verifiedAmountPaid($orderRow), 2));
                }
                unset($orderRow);
            }
        }

        $items = $this->refreshCartFromDatabase();

        return view('customer/flow', [
            'page'   => $page,
            'cart'   => $this->makeCartSummary($items),
            'user'   => $user,
            'orders' => $orders,
        ]);
    }
}
