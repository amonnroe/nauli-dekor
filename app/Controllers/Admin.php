<?php

namespace App\Controllers;

use App\Models\ExtraModel;
use App\Models\PackageModel;
use App\Models\ShippingZoneModel;
use CodeIgniter\HTTP\RedirectResponse;

class Admin extends BaseController
{
    private function render(string $section, string $title, array $data = []): string
    {
        return view('admin/panel', array_merge([
            'section' => $section,
            'title'   => $title,
            'adminPhone' => (string) session()->get('admin_phone'),
        ], $data));
    }

    public function dashboard(): string
    {
        $db = db_connect();
        $totalOrderCount = $db->table('orders')->countAllResults();

        $paidOrderValue = $db->table('orders')
            ->select('COALESCE(SUM(total), 0) AS order_value')
            ->whereIn('status_pembayaran', ['DP', 'Lunas'])
            ->whereNotIn('status_pesanan', ['Dibatalkan'])
            ->get()->getRowArray()['order_value'] ?? 0;

        $recentOrders = $db->table('orders o')
            ->select('o.id, o.kode_pesanan, o.nama_pemesan, o.tanggal_acara, o.total, o.status_pesanan, o.status_pembayaran, o.status_dekorasi, o.created_at, u.nama_lengkap AS account_name')
            ->join('users u', 'u.user_id = o.user_id', 'left')
            ->orderBy('o.id', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        // 4 KPI counts:
        // 1. Status Proses (status_dekorasi = 'Diproses')
        // 2. Status Selesai (status_dekorasi = 'Selesai' atau status_pesanan = 'Selesai')
        // 3. Total Pesanan
        // 4. Nilai Total Pesanan
        $prosesCount = $db->table('orders')
            ->where('status_dekorasi', 'Diproses')
            ->countAllResults();

        $selesaiCount = $db->table('orders')
            ->where('status_dekorasi', 'Selesai')
            ->countAllResults();

        // Data jadwal kalender dekorasi admin:
        // HANYA mengambil pesanan yang pembayarannya SUDAH DITERIMA (status_pembayaran 'DP' atau 'Lunas').
        // Pesanan yang belum bayar, menunggu verifikasi, atau ditolak TIDAK muncul di kalender tenggat.
        $calendarOrders = $db->table('orders o')
            ->select('o.id, o.kode_pesanan, o.nama_pemesan, o.no_whatsapp, o.tanggal_acara, o.waktu_acara, o.lokasi_acara, o.nama_kecamatan, o.nama_kelurahan, o.total, o.status_pesanan, o.status_pembayaran, o.status_dekorasi, COALESCE(NULLIF(o.package_name, \'\'), p.nama_paket, \'Paket dekorasi\') AS display_package_name', false)
            ->join('packages p', 'p.id = o.package_id', 'left')
            ->whereNotIn('o.status_pesanan', ['Dibatalkan'])
            ->whereIn('o.status_pembayaran', ['DP', 'Lunas'])
            ->where('o.tanggal_acara IS NOT NULL')
            ->orderBy('o.tanggal_acara', 'ASC')
            ->orderBy('o.id', 'ASC')
            ->get()->getResultArray();

        $calendarSchedule = [];
        foreach ($calendarOrders as $ord) {
            $tgl = trim((string) $ord['tanggal_acara']);
            if ($tgl !== '') {
                $calendarSchedule[$tgl][] = [
                    'id'                => (int) $ord['id'],
                    'kode_pesanan'      => (string) ($ord['kode_pesanan'] ?? ''),
                    'nama_pemesan'      => (string) ($ord['nama_pemesan'] ?? ''),
                    'no_whatsapp'       => (string) ($ord['no_whatsapp'] ?? ''),
                    'package_name'      => (string) ($ord['display_package_name'] ?? 'Paket dekorasi'),
                    'lokasi_acara'      => (string) ($ord['lokasi_acara'] ?? ''),
                    'nama_kecamatan'    => (string) ($ord['nama_kecamatan'] ?? ''),
                    'nama_kelurahan'    => (string) ($ord['nama_kelurahan'] ?? ''),
                    'waktu_acara'       => (string) ($ord['waktu_acara'] ?? ''),
                    'total'             => (float) ($ord['total'] ?? 0),
                    'total_formatted'   => 'Rp ' . number_format((float) ($ord['total'] ?? 0), 0, ',', '.'),
                    'status_pesanan'    => (string) ($ord['status_pesanan'] ?? 'Pending'),
                    'status_pembayaran' => (string) ($ord['status_pembayaran'] ?? 'Belum Bayar'),
                    'status_dekorasi'   => (string) ($ord['status_dekorasi'] ?? 'Menunggu Pembayaran'),
                ];
            }
        }

        return $this->render('dashboard', 'Ringkasan Dashboard', [
            'stats' => [
                'proses'     => $prosesCount,
                'selesai'    => $selesaiCount,
                'orders'     => (int) $totalOrderCount,
                'orderValue' => (float) $paidOrderValue,
            ],
            'calendarSchedule' => $calendarSchedule,
            'recentOrders'     => $recentOrders,
        ]);
    }

    public function packages(): string
    {
        $model = new PackageModel();
        $keyword = trim((string) $this->request->getGet('q'));
        $category = (string) $this->request->getGet('kategori');
        $type = (string) $this->request->getGet('jenis');

        if ($keyword !== '') {
            $model->groupStart()
                ->like('nama_paket', $keyword)
                ->orLike('lokasi', $keyword)
                ->groupEnd();
        }
        if (in_array($category, ['pernikahan', 'duka'], true)) {
            $model->where('kategori', $category);
        }
        if (in_array($type, ['biasa', 'lengkap', 'tambahan'], true)) {
            $model->where('jenis_paket', $type);
        }

        return $this->render('packages', 'Manajemen Paket Dekorasi', [
            'rows' => $model->orderBy('id', 'DESC')->findAll(),
            'filters' => ['q' => $keyword, 'kategori' => $category, 'jenis' => $type],
        ]);
    }

    public function newPackage(): string
    {
        return $this->render('package-form', 'Tambah Paket Dekorasi', [
            'item' => ['nama_paket' => '', 'kategori' => 'pernikahan', 'jenis_paket' => 'biasa', 'lokasi' => '', 'deskripsi' => '', 'harga' => '', 'gambar' => '', 'aktif' => 1],
            'formAction' => base_url('admin/paket'),
            'isEdit' => false,
        ]);
    }

    public function createPackage(): RedirectResponse
    {
        if (! $this->validate($this->packageRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $category = (string) $this->request->getPost('kategori');
        $type = (string) $this->request->getPost('jenis_paket');
        if ($category === 'duka' && $type === 'lengkap') {
            return redirect()->back()->withInput()->with('error', 'Kategori Rumah Duka hanya mendukung jenis Biasa dan Tambahan.');
        }

        $imageUpload = $this->storeCatalogImage(true);
        if ($imageUpload['error'] !== null) {
            return redirect()->back()->withInput()->with('error', $imageUpload['error']);
        }

        $payload = $this->packagePayload((string) $imageUpload['filename']);
        if ((new PackageModel())->insert($payload) === false) {
            $this->removeNewCatalogImage($imageUpload['newFilename']);
            return redirect()->back()->withInput()->with('error', 'Paket gagal disimpan. Periksa kembali data dan koneksi database.');
        }

        return redirect()->to('/admin/paket')->with('success', 'Paket dekorasi berhasil ditambahkan.');
    }

    public function editPackage(int $id)
    {
        $item = (new PackageModel())->find($id);
        if ($item === null) {
            return redirect()->to('/admin/paket')->with('error', 'Paket tidak ditemukan.');
        }

        return $this->render('package-form', 'Ubah Paket Dekorasi', [
            'item' => $item,
            'formAction' => base_url('admin/paket/' . $id),
            'isEdit' => true,
        ]);
    }

    public function updatePackage(int $id): RedirectResponse
    {
        $model = new PackageModel();
        $currentItem = $model->find($id);
        if ($currentItem === null) {
            return redirect()->to('/admin/paket')->with('error', 'Paket tidak ditemukan.');
        }
        if (! $this->validate($this->packageRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $category = (string) $this->request->getPost('kategori');
        $type = (string) $this->request->getPost('jenis_paket');
        if ($category === 'duka' && $type === 'lengkap') {
            return redirect()->back()->withInput()->with('error', 'Kategori Rumah Duka hanya mendukung jenis Biasa dan Tambahan.');
        }

        $currentImage = basename((string) ($currentItem['gambar'] ?? ''));
        $currentImageMissing = $currentImage === '' || ! is_file(FCPATH . 'assets/images/' . $currentImage);
        $imageUpload = $this->storeCatalogImage($currentImageMissing, $currentImage);
        if ($imageUpload['error'] !== null) {
            return redirect()->back()->withInput()->with('error', $imageUpload['error']);
        }

        $payload = $this->packagePayload((string) $imageUpload['filename']);
        if ($model->update($id, $payload) === false) {
            $this->removeNewCatalogImage($imageUpload['newFilename']);
            return redirect()->back()->withInput()->with('error', 'Perubahan paket gagal disimpan.');
        }
        return redirect()->to('/admin/paket')->with('success', 'Paket dekorasi berhasil diperbarui.');
    }

    public function togglePackage(int $id): RedirectResponse
    {
        $model = new PackageModel();
        $item = $model->find($id);
        if ($item === null) {
            return redirect()->to('/admin/paket')->with('error', 'Paket tidak ditemukan.');
        }

        if ($model->update($id, ['aktif' => (int) $item['aktif'] === 1 ? 0 : 1]) === false) {
            return redirect()->to('/admin/paket')->with('error', 'Status paket gagal diperbarui.');
        }
        return redirect()->to('/admin/paket')->with('success', 'Status paket berhasil diperbarui.');
    }

    public function extras(): string
    {
        $model = new ExtraModel();
        $keyword = trim((string) $this->request->getGet('q'));
        if ($keyword !== '') {
            $model->groupStart()->like('nama', $keyword)->orLike('deskripsi', $keyword)->groupEnd();
        }

        return $this->render('extras', 'Manajemen Tambahan Dekorasi', [
            'rows' => $model->orderBy('id', 'DESC')->findAll(),
            'keyword' => $keyword,
        ]);
    }

    public function newExtra(): string
    {
        return $this->render('extra-form', 'Tambah Tambahan Dekorasi', [
            'item' => ['nama' => '', 'deskripsi' => '', 'harga' => '', 'stok' => 0, 'gambar' => '', 'aktif' => 1],
            'formAction' => base_url('admin/tambahan'),
            'isEdit' => false,
        ]);
    }

    public function createExtra(): RedirectResponse
    {
        if (! $this->validate($this->extraRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $imageUpload = $this->storeCatalogImage(true);
        if ($imageUpload['error'] !== null) {
            return redirect()->back()->withInput()->with('error', $imageUpload['error']);
        }

        if ((new ExtraModel())->insert($this->extraPayload((string) $imageUpload['filename'])) === false) {
            $this->removeNewCatalogImage($imageUpload['newFilename']);
            return redirect()->back()->withInput()->with('error', 'Tambahan dekorasi gagal disimpan. Periksa kembali data dan koneksi database.');
        }
        return redirect()->to('/admin/tambahan')->with('success', 'Tambahan dekorasi berhasil ditambahkan.');
    }

    public function editExtra(int $id)
    {
        $item = (new ExtraModel())->find($id);
        if ($item === null) {
            return redirect()->to('/admin/tambahan')->with('error', 'Tambahan dekorasi tidak ditemukan.');
        }

        return $this->render('extra-form', 'Ubah Tambahan Dekorasi', [
            'item' => $item,
            'formAction' => base_url('admin/tambahan/' . $id),
            'isEdit' => true,
        ]);
    }

    public function updateExtra(int $id): RedirectResponse
    {
        $model = new ExtraModel();
        $currentItem = $model->find($id);
        if ($currentItem === null) {
            return redirect()->to('/admin/tambahan')->with('error', 'Tambahan dekorasi tidak ditemukan.');
        }
        if (! $this->validate($this->extraRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $currentImage = basename((string) ($currentItem['gambar'] ?? ''));
        $currentImageMissing = $currentImage === '' || ! is_file(FCPATH . 'assets/images/' . $currentImage);
        $imageUpload = $this->storeCatalogImage($currentImageMissing, $currentImage);
        if ($imageUpload['error'] !== null) {
            return redirect()->back()->withInput()->with('error', $imageUpload['error']);
        }

        if ($model->update($id, $this->extraPayload((string) $imageUpload['filename'])) === false) {
            $this->removeNewCatalogImage($imageUpload['newFilename']);
            return redirect()->back()->withInput()->with('error', 'Perubahan tambahan dekorasi gagal disimpan.');
        }
        return redirect()->to('/admin/tambahan')->with('success', 'Tambahan dekorasi berhasil diperbarui.');
    }

    public function toggleExtra(int $id): RedirectResponse
    {
        $model = new ExtraModel();
        $item = $model->find($id);
        if ($item === null) {
            return redirect()->to('/admin/tambahan')->with('error', 'Tambahan dekorasi tidak ditemukan.');
        }

        if ($model->update($id, ['aktif' => (int) $item['aktif'] === 1 ? 0 : 1]) === false) {
            return redirect()->to('/admin/tambahan')->with('error', 'Status tambahan dekorasi gagal diperbarui.');
        }
        return redirect()->to('/admin/tambahan')->with('success', 'Status tambahan dekorasi berhasil diperbarui.');
    }

    public function orders(): string
    {
        $db = db_connect();
        $status = (string) $this->request->getGet('status');
        $keyword = trim((string) $this->request->getGet('q'));
        $builder = $db->table('orders o')
            ->select('o.id, o.kode_pesanan, o.nama_pemesan, o.no_whatsapp, o.tanggal_acara, o.total, o.jumlah_dp, o.metode_pembayaran, o.status_pembayaran, o.status_pesanan, o.status_dekorasi, o.created_at, COALESCE(NULLIF(o.package_name, \'\'), p.nama_paket, \'Paket dekorasi\') AS display_package_name, u.nama_lengkap AS account_name', false)
            ->join('users u', 'u.user_id = o.user_id', 'left')
            ->join('packages p', 'p.id = o.package_id', 'left');

        if (in_array($status, ['Pending', 'Diproses', 'Selesai', 'Dibatalkan'], true)) {
            $builder->where('o.status_pesanan', $status);
        }
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('o.kode_pesanan', $keyword)
                ->orLike('o.nama_pemesan', $keyword)
                ->orLike('o.no_whatsapp', $keyword)
                ->groupEnd();
        }

        return $this->render('orders', 'Manajemen Pesanan', [
            'rows' => $builder->orderBy('o.id', 'DESC')->get()->getResultArray(),
            'statusFilter' => $status,
            'keyword' => $keyword,
        ]);
    }

    public function orderDetail(int $id)
    {
        $db = db_connect();
        $order = $db->table('orders o')
            ->select('o.*, COALESCE(NULLIF(o.package_name, \'\'), p.nama_paket, \'Paket dekorasi\') AS display_package_name, COALESCE(o.package_price, p.harga, 0) AS display_package_price, u.nama_lengkap AS account_name, u.email AS account_email', false)
            ->join('users u', 'u.user_id = o.user_id', 'left')
            ->join('packages p', 'p.id = o.package_id', 'left')
            ->where('o.id', $id)
            ->get()->getRowArray();

        if ($order === null) {
            return redirect()->to('/admin/pesanan')->with('error', 'Pesanan tidak ditemukan.');
        }

        $extras = $db->table('order_extras')
            ->where('order_id', $id)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $settlementPayments = $db->table('order_payments')
            ->where('order_id', $id)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return $this->render('order-detail', 'Detail Pesanan', [
            'order' => $order,
            'orderExtras' => $extras,
            'settlementPayments' => $settlementPayments,
        ]);
    }

    /** Mengirim bukti pembayaran dari penyimpanan privat hanya untuk admin login. */
    public function paymentProof(int $id)
    {
        $order = db_connect()->table('orders')->select('bukti_pembayaran')->where('id', $id)->get()->getRowArray();
        $filename = basename((string) ($order['bukti_pembayaran'] ?? ''));
        if ($filename === '') {
            return $this->response->setStatusCode(404)->setBody('Bukti pembayaran tidak ditemukan.');
        }

        $path = WRITEPATH . 'uploads/payment-proofs/' . $filename;
        if (! is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('File bukti pembayaran tidak tersedia.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (! in_array($mime, $allowedMimes, true)) {
            return $this->response->setStatusCode(415)->setBody('Format file bukti tidak didukung.');
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return $this->response->setStatusCode(500)->setBody('File bukti tidak dapat dibaca.');
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setHeader('Content-Disposition', 'inline; filename="bukti-pembayaran-' . $id . '.' . pathinfo($filename, PATHINFO_EXTENSION) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($contents);
    }

    /**
     * Verifikasi bukti pembayaran. Pembayaran baru diakui setelah admin
     * menyetujui file; persetujuan otomatis mengubah pesanan menjadi Diproses.
     */
    public function verifyPayment(int $id): RedirectResponse
    {
        if (! $this->validate([
            'keputusan_verifikasi' => 'required|in_list[approve,reject]',
            'catatan_verifikasi' => 'permit_empty|max_length[500]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $decision = (string) $this->request->getPost('keputusan_verifikasi');
        $note = trim((string) $this->request->getPost('catatan_verifikasi'));
        $db = db_connect();
        if ($db->transBegin() === false) {
            return redirect()->back()->with('error', 'Verifikasi belum dapat dimulai. Silakan coba kembali.');
        }

        try {
            $order = $db->query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if ($order === null) {
                throw new \DomainException('Pesanan tidak ditemukan.');
            }
            if ((string) ($order['status_pembayaran'] ?? '') !== 'Menunggu Verifikasi') {
                throw new \DomainException('Pesanan ini tidak sedang menunggu verifikasi pembayaran.');
            }
            if (! in_array((string) ($order['status_pesanan'] ?? ''), ['Pending'], true)) {
                throw new \DomainException('Hanya pesanan berstatus Pending yang dapat diverifikasi.');
            }

            $proofName = basename((string) ($order['bukti_pembayaran'] ?? ''));
            $proofPath = WRITEPATH . 'uploads/payment-proofs/' . $proofName;
            if ($proofName === '' || ! is_file($proofPath)) {
                throw new \DomainException('File bukti pembayaran tidak ditemukan. Minta pelanggan mengunggah ulang bukti.');
            }

            $now = date('Y-m-d H:i:s');
            if ($decision === 'approve') {
                $scheme = (string) ($order['metode_pembayaran'] ?? '');
                $total = (float) ($order['total'] ?? 0);
                $plannedPayment = (float) ($order['jumlah_dp'] ?? 0);
                if ($scheme === 'DP') {
                    if ($plannedPayment <= 0 || $plannedPayment >= $total) {
                        throw new \DomainException('Nominal DP pada pesanan tidak valid. Periksa data pesanan sebelum menyetujui.');
                    }
                    $paymentStatus = 'DP';
                } elseif ($scheme === 'Lunas') {
                    $paymentStatus = 'Lunas';
                } else {
                    throw new \DomainException('Skema pembayaran pesanan tidak dikenali.');
                }

                $orderStatus = 'Diproses';
                $note = null;
                $success = 'Bukti pembayaran disetujui. Status pembayaran diperbarui dan pesanan masuk ke tahap Diproses.';
            } else {
                $paymentStatus = 'Ditolak';
                $orderStatus = 'Pending';
                if ($note === '') {
                    $note = 'Bukti pembayaran belum dapat diverifikasi. Silakan unggah ulang bukti yang jelas.';
                }
                $success = 'Bukti pembayaran ditolak. Pelanggan dapat melihat catatan dan mengunggah ulang bukti melalui invoice.';
            }

            // status_dekorasi: approve -> Diproses, reject -> tetap Menunggu Pembayaran
            $statusDekorasi = ($decision === 'approve') ? 'Diproses' : 'Menunggu Pembayaran';

            $updated = $db->table('orders')->where('id', $id)->update([
                'status_pembayaran' => $paymentStatus,
                'status_pesanan'    => $orderStatus,
                'status_dekorasi'   => $statusDekorasi,
                'catatan_verifikasi' => $note,
                'diverifikasi_oleh' => (int) session()->get('admin_id'),
                'diverifikasi_pada' => $now,
                'updated_at'        => $now,
            ]);

            if ($updated === false || $db->transStatus() === false || $db->transCommit() === false) {
                throw new \RuntimeException('Gagal menyimpan hasil verifikasi pembayaran.');
            }

            return redirect()->to('/admin/pesanan/' . $id)->with('success', $success);
        } catch (\DomainException $exception) {
            $db->transRollback();
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Verifikasi pembayaran pesanan #{id} gagal: {message}', ['id' => $id, 'message' => $exception->getMessage()]);
            return redirect()->back()->with('error', 'Verifikasi gagal disimpan. Tidak ada perubahan status yang diterapkan.');
        }
    }


    /** Bukti pelunasan hanya dapat dibaca admin melalui endpoint terproteksi. */
    public function settlementPaymentProof(int $paymentId)
    {
        $payment = db_connect()->table('order_payments')->where('id', $paymentId)->get()->getRowArray();
        $filename = basename((string) ($payment['bukti_pembayaran'] ?? ''));
        if ($filename === '') return $this->response->setStatusCode(404)->setBody('Bukti pelunasan tidak ditemukan.');

        $path = WRITEPATH . 'uploads/payment-proofs/' . $filename;
        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('File bukti pelunasan tidak tersedia.');
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
            return $this->response->setStatusCode(415)->setBody('Format file bukti tidak didukung.');
        }
        $contents = file_get_contents($path);
        if ($contents === false) return $this->response->setStatusCode(500)->setBody('File bukti tidak dapat dibaca.');

        return $this->response->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setHeader('Content-Disposition', 'inline; filename="bukti-pelunasan-' . $paymentId . '.' . pathinfo($filename, PATHINFO_EXTENSION) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')->setBody($contents);
    }

    /**
     * Verifikasi transfer/QRIS atau konfirmasi pembayaran tunai di lokasi acara.
     * Untuk bayar di tempat, admin wajib mengisi catatan dan pesanan diselesaikan.
     */
    public function reviewSettlement(int $paymentId): RedirectResponse
    {
        if (! $this->validate([
            'keputusan_pelunasan' => 'required|in_list[approve,reject,confirm_onsite]',
            'catatan_pelunasan' => 'permit_empty|max_length[500]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $decision = (string) $this->request->getPost('keputusan_pelunasan');
        $note = trim((string) $this->request->getPost('catatan_pelunasan'));
        $db = db_connect();
        if ($db->transBegin() === false) return redirect()->back()->with('error', 'Verifikasi pelunasan belum dapat dimulai.');

        try {
            $payment = $db->query('SELECT * FROM order_payments WHERE id = ? FOR UPDATE', [$paymentId])->getRowArray();
            if ($payment === null) throw new \DomainException('Data pelunasan tidak ditemukan.');
            $orderId = (int) $payment['order_id'];
            $order = $db->query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId])->getRowArray();
            if ($order === null) throw new \DomainException('Pesanan terkait tidak ditemukan.');
            if (($order['status_pembayaran'] ?? '') !== 'DP' || ($order['status_pesanan'] ?? '') !== 'Diproses') {
                throw new \DomainException('Pesanan tidak lagi memiliki tagihan pelunasan yang dapat diproses.');
            }

            $method = (string) ($payment['metode_pembayaran'] ?? '');
            $paymentStatus = (string) ($payment['status'] ?? '');
            $now = date('Y-m-d H:i:s');
            $total = (float) ($order['total'] ?? 0);
            $alreadyPaid = min($total, max(0, (float) ($order['jumlah_dp'] ?? 0)));
            $remaining = max(0, round($total - $alreadyPaid, 2));
            if ($remaining <= 0 || abs($remaining - (float) ($payment['jumlah'] ?? 0)) > 0.01) {
                throw new \DomainException('Nominal pelunasan sudah berubah atau tidak sesuai dengan sisa tagihan.');
            }

            if ($method === 'Bayar di Tempat Acara') {
                if ($decision !== 'confirm_onsite' || $paymentStatus !== 'Menunggu Pembayaran di Tempat') {
                    throw new \DomainException('Pembayaran di tempat hanya dapat dikonfirmasi melalui aksi khusus admin.');
                }
                if ($note === '') throw new \DomainException('Catatan admin wajib diisi untuk konfirmasi pembayaran di tempat.');

                // Pengembalian stok pada status Selesai mengikuti kebijakan stok pesanan saat ini.
                if ((int) ($order['stock_reserved'] ?? 0) === 1) {
                    $extras = $db->table('order_extras')->where('order_id', $orderId)->orderBy('extra_id', 'ASC')->get()->getResultArray();
                    if ($extras === []) throw new \RuntimeException('Rincian tambahan tidak ditemukan; stok tidak diubah.');
                    foreach ($extras as $extra) {
                        $qty = (int) ($extra['jumlah'] ?? 0);
                        $extraId = (int) ($extra['extra_id'] ?? 0);
                        if ($qty < 1 || $extraId < 1) throw new \RuntimeException('Rincian stok tidak valid.');
                        $restock = $db->query('UPDATE extras SET stok = stok + ?, updated_at = ? WHERE id = ?', [$qty, $now, $extraId]);
                        if ($restock === false || $db->affectedRows() !== 1) throw new \RuntimeException('Pengembalian stok gagal. Status pesanan tidak diubah.');
                    }
                }

                $updatedPayment = $db->table('order_payments')->where('id', $paymentId)->update([
                    'status' => 'Dikonfirmasi di Tempat', 'catatan' => $note,
                    'diverifikasi_oleh' => (int) session()->get('admin_id'), 'diverifikasi_pada' => $now, 'updated_at' => $now,
                ]);
                $updatedOrder = $db->table('orders')->where('id', $orderId)->update([
                    'status_pembayaran' => 'Lunas', 'jumlah_dp' => $total,
                    'status_pesanan' => 'Selesai', 'stock_reserved' => 0, 'updated_at' => $now,
                ]);
                $successMessage = 'Pelunasan di tempat berhasil dicatat. Catatan admin tersimpan dan pesanan ditandai Selesai.';
            } else {
                if ($decision === 'confirm_onsite') {
                    throw new \DomainException('Aksi konfirmasi di tempat hanya berlaku untuk metode Bayar di Tempat Acara.');
                }
                if (! in_array($method, ['QRIS', 'Transfer Bank'], true) || $paymentStatus !== 'Menunggu Verifikasi') {
                    throw new \DomainException('Bukti pelunasan ini tidak sedang menunggu verifikasi.');
                }
                $proofName = basename((string) ($payment['bukti_pembayaran'] ?? ''));
                $proofPath = WRITEPATH . 'uploads/payment-proofs/' . $proofName;
                if ($proofName === '' || ! is_file($proofPath)) throw new \DomainException('File bukti pelunasan tidak ditemukan.');

                if ($decision === 'approve') {
                    $updatedPayment = $db->table('order_payments')->where('id', $paymentId)->update([
                        'status' => 'Disetujui', 'catatan' => $note !== '' ? $note : null,
                        'diverifikasi_oleh' => (int) session()->get('admin_id'), 'diverifikasi_pada' => $now, 'updated_at' => $now,
                    ]);
                    $updatedOrder = $db->table('orders')->where('id', $orderId)->update([
                        'status_pembayaran' => 'Lunas', 'jumlah_dp' => $total, 'updated_at' => $now,
                    ]);
                    $successMessage = 'Bukti pelunasan disetujui. Status pembayaran menjadi Lunas; pesanan tetap Diproses sampai acara selesai.';
                } else {
                    if ($note === '') throw new \DomainException('Catatan penolakan wajib diisi agar pelanggan mengetahui alasan penolakan.');
                    $updatedPayment = $db->table('order_payments')->where('id', $paymentId)->update([
                        'status' => 'Ditolak', 'catatan' => $note,
                        'diverifikasi_oleh' => (int) session()->get('admin_id'), 'diverifikasi_pada' => $now, 'updated_at' => $now,
                    ]);
                    $updatedOrder = true; // Status DP dan Diproses dipertahankan.
                    $successMessage = 'Bukti pelunasan ditolak. Pelanggan dapat mengajukan ulang setelah memperbaiki bukti.';
                }
            }

            if ($updatedPayment === false || $updatedOrder === false || $db->transStatus() === false || $db->transCommit() === false) {
                throw new \RuntimeException('Hasil verifikasi pelunasan gagal disimpan.');
            }
            return redirect()->to('/admin/pesanan/' . $orderId)->with('success', $successMessage);
        } catch (\DomainException $exception) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Verifikasi pelunasan #{id} gagal: {message}', ['id' => $paymentId, 'message' => $exception->getMessage()]);
            return redirect()->back()->with('error', 'Verifikasi pelunasan gagal disimpan. Tidak ada perubahan status yang diterapkan.');
        }
    }

    public function updateOrderStatus(int $id): RedirectResponse
    {
        if (! $this->validate([
            'status_pesanan' => 'required|in_list[Pending,Diproses,Selesai,Dibatalkan]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $requestedOrderStatus = (string) $this->request->getPost('status_pesanan');
        $db = db_connect();
        if ($db->transBegin() === false) {
            return redirect()->back()->with('error', 'Perubahan status belum dapat diproses. Silakan coba kembali.');
        }

        try {
            $order = $db->query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if ($order === null) {
                throw new \DomainException('Pesanan tidak ditemukan.');
            }

            $currentOrderStatus = (string) $order['status_pesanan'];
            $allowedOrderTransitions = [
                'Pending' => ['Pending', 'Diproses', 'Dibatalkan'],
                'Diproses' => ['Diproses', 'Selesai', 'Dibatalkan'],
                'Selesai' => ['Selesai'],
                'Dibatalkan' => ['Dibatalkan'],
            ];
            if (! in_array($requestedOrderStatus, $allowedOrderTransitions[$currentOrderStatus] ?? [], true)) {
                throw new \DomainException('Perubahan status pesanan tidak diizinkan. Pesanan selesai atau dibatalkan tidak dapat dibuka kembali.');
            }

            $paymentStatus = (string) ($order['status_pembayaran'] ?? 'Belum Bayar');
            if ($requestedOrderStatus === 'Diproses' && ! in_array($paymentStatus, ['DP', 'Lunas'], true)) {
                throw new \DomainException('Pesanan hanya dapat diproses setelah bukti pembayaran disetujui admin.');
            }
            if ($requestedOrderStatus === 'Selesai' && $paymentStatus !== 'Lunas') {
                throw new \DomainException('Pesanan hanya dapat ditandai selesai setelah pembayaran berstatus Lunas.');
            }

            $stockReserved = (int) ($order['stock_reserved'] ?? 0);
            $releasesInventory = in_array($requestedOrderStatus, ['Selesai', 'Dibatalkan'], true);
            if ($stockReserved === 1 && $releasesInventory) {
                $orderExtras = $db->table('order_extras')->where('order_id', $id)->orderBy('extra_id', 'ASC')->get()->getResultArray();
                if ($orderExtras === []) {
                    throw new \RuntimeException('Rincian tambahan untuk pengembalian stok tidak ditemukan. Stok tidak diubah.');
                }
                foreach ($orderExtras as $extra) {
                    $quantity = (int) ($extra['jumlah'] ?? 0);
                    $extraId = (int) ($extra['extra_id'] ?? 0);
                    if ($quantity < 1 || $extraId < 1) {
                        throw new \RuntimeException('Rincian stok pesanan tidak valid.');
                    }
                    $restock = $db->query('UPDATE extras SET stok = stok + ?, updated_at = ? WHERE id = ?', [$quantity, date('Y-m-d H:i:s'), $extraId]);
                    if ($restock === false || $db->affectedRows() !== 1) {
                        throw new \RuntimeException('Stok tambahan tidak dapat dikembalikan. Status tidak diubah.');
                    }
                }
                $stockReserved = 0;
            }

            $orderUpdateData = [
                'status_pesanan' => $requestedOrderStatus,
                'stock_reserved' => $stockReserved,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($requestedOrderStatus === 'Selesai') {
                $orderUpdateData['status_dekorasi'] = 'Selesai';
            }

            $updated = $db->table('orders')->where('id', $id)->update($orderUpdateData);
            if ($updated === false || $db->transStatus() === false || $db->transCommit() === false) {
                throw new \RuntimeException('Perubahan status pesanan gagal disimpan.');
            }

            return redirect()->to('/admin/pesanan/' . $id)->with('success', 'Status pesanan berhasil diperbarui. Status pembayaran hanya berubah melalui verifikasi bukti.');
        } catch (\DomainException $exception) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Perubahan status pesanan #{id} gagal: {message}', ['id' => $id, 'message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Perubahan status gagal disimpan. Tidak ada perubahan stok atau status yang diterapkan.');
        }
    }

    /**
     * Tandai status dekorasi pesanan menjadi Selesai.
     * Hanya bisa dilakukan ketika status_dekorasi = 'Diproses'.
     */
    public function tandaiSelesai(int $id): RedirectResponse
    {
        $db = db_connect();
        $order = $db->table('orders')->select('id, kode_pesanan, status_dekorasi, status_pembayaran, stock_reserved')->where('id', $id)->get()->getRowArray();

        if ($order === null) {
            return redirect()->to('/admin/pesanan')->with('error', 'Pesanan tidak ditemukan.');
        }
        if ((string) ($order['status_dekorasi'] ?? '') !== 'Diproses') {
            return redirect()->back()->with('error', 'Status dekorasi hanya dapat ditandai Selesai ketika sedang Diproses.');
        }

        $now = date('Y-m-d H:i:s');
        $stockReserved = (int) ($order['stock_reserved'] ?? 0);

        if ($stockReserved === 1) {
            $orderExtras = $db->table('order_extras')->where('order_id', $id)->orderBy('extra_id', 'ASC')->get()->getResultArray();
            foreach ($orderExtras as $extra) {
                $quantity = (int) ($extra['jumlah'] ?? 0);
                $extraId = (int) ($extra['extra_id'] ?? 0);
                if ($quantity > 0 && $extraId > 0) {
                    $db->query('UPDATE extras SET stok = stok + ?, updated_at = ? WHERE id = ?', [$quantity, $now, $extraId]);
                }
            }
            $stockReserved = 0;
        }

        $updated = $db->table('orders')->where('id', $id)->update([
            'status_dekorasi' => 'Selesai',
            'status_pesanan'  => 'Selesai',
            'stock_reserved'  => $stockReserved,
            'updated_at'      => $now,
        ]);

        if ($updated === false) {
            return redirect()->back()->with('error', 'Gagal memperbarui status dekorasi. Silakan coba kembali.');
        }

        return redirect()->back()->with('success', 'Pesanan ' . esc($order['kode_pesanan'] ?? ('#' . $id)) . ' berhasil ditandai Selesai.');
    }

    public function customers(): string
    {
        $db = db_connect();
        $keyword = trim((string) $this->request->getGet('q'));
        $builder = $db->table('users u')
            ->select('u.user_id, u.nama_lengkap, u.no_whatsapp, u.email, u.created_at, COUNT(o.id) AS order_count')
            ->join('orders o', 'o.user_id = u.user_id', 'left')
            ->where('u.role', 'Pelanggan')
            ->groupBy(['u.user_id', 'u.nama_lengkap', 'u.no_whatsapp', 'u.email', 'u.created_at']);

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('u.nama_lengkap', $keyword)
                ->orLike('u.no_whatsapp', $keyword)
                ->orLike('u.email', $keyword)
                ->groupEnd();
        }

        return $this->render('customers', 'Data Pelanggan', [
            'rows' => $builder->orderBy('u.user_id', 'DESC')->get()->getResultArray(),
            'keyword' => $keyword,
        ]);
    }


    /**
     * Laporan keuangan berbasis kas: pendapatan hanya menghitung pembayaran yang
     * sudah diverifikasi. Pembayaran awal pada orders direkonsiliasi dengan
     * pelunasan terverifikasi pada order_payments agar tidak dihitung dua kali.
     */
    private function financialReportData(bool $forPdf = false): array
    {
        $today = date('Y-m-d');
        $period = (string) $this->request->getGet('periode');
        if ($period === '' && ($this->request->getGet('tanggal_mulai') || $this->request->getGet('tanggal_selesai'))) {
            $period = 'custom';
        }
        if (! in_array($period, ['custom', 'mingguan', 'bulanan', 'tahunan', 'harian'], true)) {
            $period = 'custom';
        }

        $date = trim((string) $this->request->getGet('tanggal'));
        $week = trim((string) $this->request->getGet('minggu'));
        $month = trim((string) $this->request->getGet('bulan'));
        $year = (int) ($this->request->getGet('tahun') ?? date('Y'));
        $from = trim((string) $this->request->getGet('tanggal_mulai'));
        $to = trim((string) $this->request->getGet('tanggal_selesai'));
        $isValidDate = static function (string $value): bool {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $parsed !== false && $parsed->format('Y-m-d') === $value;
        };

        if ($period === 'harian') {
            if (! $isValidDate($date)) $date = $today;
            $startDate = $date;
            $endDate = $date;
        } elseif ($period === 'mingguan') {
            if (preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) {
                $dto = new \DateTimeImmutable();
                $dto = $dto->setISODate((int) $m[1], (int) $m[2]);
                $startDate = $dto->format('Y-m-d');
                $endDate = $dto->modify('+6 days')->format('Y-m-d');
            } else {
                $dto = (new \DateTimeImmutable($today))->modify('monday this week');
                $startDate = $dto->format('Y-m-d');
                $endDate = $dto->modify('+6 days')->format('Y-m-d');
                $week = (new \DateTimeImmutable($startDate))->format('o-\WW');
            }
        } elseif ($period === 'bulanan') {
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) $month = date('Y-m');
            $startDate = $month . '-01';
            $endDate = (new \DateTimeImmutable($startDate))->modify('last day of this month')->format('Y-m-d');
        } elseif ($period === 'tahunan') {
            if ($year < 2000 || $year > 2100) $year = (int) date('Y');
            $startDate = sprintf('%04d-01-01', $year);
            $endDate = sprintf('%04d-12-31', $year);
        } else {
            if (! $isValidDate($from)) $from = date('Y-m-01');
            if (! $isValidDate($to)) $to = $today;
            if ($from > $to) [$from, $to] = [$to, $from];
            $startDate = $from;
            $endDate = $to;
        }

        $channel = trim((string) $this->request->getGet('kanal'));
        $allowedChannels = ['QRIS', 'Transfer Bank', 'Bayar di Tempat Acara', 'Tidak diketahui'];
        if (! in_array($channel, $allowedChannels, true)) $channel = '';

        $transactionStatus = trim((string) $this->request->getGet('status_transaksi'));
        $allowedStatuses = ['Terverifikasi', 'Belum Bayar', 'Menunggu Verifikasi', 'Ditolak', 'Menunggu Pembayaran di Tempat'];
        if (! in_array($transactionStatus, $allowedStatuses, true)) $transactionStatus = '';

        // Sumber pembayaran awal: orders. Jumlah pembayaran pelunasan yang sudah
        // disetujui dikurangi dari jumlah_dp ketika status order sudah Lunas.
        // Sumber pelunasan: masing-masing baris order_payments.
        $ledgerSql = <<<'SQL'
SELECT
    CONVERT(CONCAT('ORDER-', o.id) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS payment_ref,
    o.id AS order_id,
    CONVERT(o.kode_pesanan USING utf8mb4) COLLATE utf8mb4_unicode_ci AS kode_pesanan,
    CONVERT(o.nama_pemesan USING utf8mb4) COLLATE utf8mb4_unicode_ci AS nama_pemesan,
    CONVERT(COALESCE(NULLIF(o.kanal_pembayaran, ''), 'Tidak diketahui') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS metode,
    CASE
        WHEN o.status_pembayaran = 'Lunas'
            THEN GREATEST(CAST(o.total AS DECIMAL(15,2)) - COALESCE(sp.verified_total, 0), 0)
        ELSE GREATEST(CAST(o.jumlah_dp AS DECIMAL(15,2)), 0)
    END AS jumlah,
    CASE
        WHEN o.status_pembayaran IN ('DP', 'Lunas') THEN CONVERT('Terverifikasi' USING utf8mb4) COLLATE utf8mb4_unicode_ci
        WHEN o.status_pembayaran = 'Menunggu Verifikasi' THEN CONVERT('Menunggu Verifikasi' USING utf8mb4) COLLATE utf8mb4_unicode_ci
        WHEN o.status_pembayaran = 'Ditolak' THEN CONVERT('Ditolak' USING utf8mb4) COLLATE utf8mb4_unicode_ci
        ELSE CONVERT('Belum Bayar' USING utf8mb4) COLLATE utf8mb4_unicode_ci
    END COLLATE utf8mb4_unicode_ci AS status_transaksi,
    CASE
        WHEN o.status_pembayaran IN ('DP', 'Lunas') THEN COALESCE(o.diverifikasi_pada, o.created_at)
        ELSE COALESCE(o.bukti_diupload_pada, o.created_at)
    END AS tanggal_transaksi,
    CONVERT('Pembayaran Awal' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS jenis_transaksi,
    CAST(o.total AS DECIMAL(15,2)) AS nilai_pesanan
FROM orders o
LEFT JOIN (
    SELECT order_id,
           SUM(CASE WHEN status IN ('Disetujui', 'Dikonfirmasi di Tempat') THEN jumlah ELSE 0 END) AS verified_total
    FROM order_payments
    GROUP BY order_id
) sp ON sp.order_id = o.id
UNION ALL
SELECT
    CONVERT(CONCAT('SETTLEMENT-', p.id) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS payment_ref,
    p.order_id,
    CONVERT(o.kode_pesanan USING utf8mb4) COLLATE utf8mb4_unicode_ci AS kode_pesanan,
    CONVERT(o.nama_pemesan USING utf8mb4) COLLATE utf8mb4_unicode_ci AS nama_pemesan,
    CONVERT(p.metode_pembayaran USING utf8mb4) COLLATE utf8mb4_unicode_ci AS metode,
    CAST(p.jumlah AS DECIMAL(15,2)) AS jumlah,
    CASE
        WHEN p.status IN ('Disetujui', 'Dikonfirmasi di Tempat') THEN CONVERT('Terverifikasi' USING utf8mb4) COLLATE utf8mb4_unicode_ci
        ELSE CONVERT(p.status USING utf8mb4) COLLATE utf8mb4_unicode_ci
    END COLLATE utf8mb4_unicode_ci AS status_transaksi,
    COALESCE(p.diverifikasi_pada, p.created_at) AS tanggal_transaksi,
    CONVERT('Pelunasan' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS jenis_transaksi,
    CAST(o.total AS DECIMAL(15,2)) AS nilai_pesanan
FROM order_payments p
INNER JOIN orders o ON o.id = p.order_id
SQL;

        $where = ' WHERE ledger.tanggal_transaksi >= ? AND ledger.tanggal_transaksi < DATE_ADD(?, INTERVAL 1 DAY)';
        $params = [$startDate . ' 00:00:00', $endDate . ' 00:00:00'];
        if ($channel !== '') {
            if ($channel === 'Tidak diketahui') {
                $where .= " AND (ledger.metode = 'Tidak diketahui' OR ledger.metode IS NULL OR ledger.metode = '')";
            } else {
                $where .= ' AND ledger.metode = ?';
                $params[] = $channel;
            }
        }
        if ($transactionStatus !== '') {
            $where .= ' AND ledger.status_transaksi = ?';
            $params[] = $transactionStatus;
        }

        $db = db_connect();
        $summary = $db->query(
            'SELECT COUNT(DISTINCT ledger.order_id) AS transaction_count,
                    SUM(CASE WHEN ledger.status_transaksi = \'Terverifikasi\' THEN ledger.jumlah ELSE 0 END) AS verified_revenue,
                    SUM(CASE WHEN ledger.status_transaksi = \'Terverifikasi\' THEN 1 ELSE 0 END) AS verified_payment_count
             FROM (' . $ledgerSql . ') AS ledger' . $where,
            $params
        )->getRowArray() ?? [];

        $totalTransactionsCount = (int) ($db->query(
            'SELECT COUNT(*) AS total_count FROM (' . $ledgerSql . ') AS ledger' . $where,
            $params
        )->getRowArray()['total_count'] ?? 0);

        $perPage = 20;
        $totalPages = max(1, (int) ceil($totalTransactionsCount / $perPage));
        $pageParam = max(1, (int) ($this->request->getGet('page') ?? 1));
        $currentPage = min($pageParam, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        if ($forPdf) {
            $transactions = $db->query(
                'SELECT ledger.* FROM (' . $ledgerSql . ') AS ledger' . $where .
                ' ORDER BY ledger.tanggal_transaksi DESC, ledger.payment_ref DESC LIMIT 1000',
                $params
            )->getResultArray();
        } else {
            $transactions = $db->query(
                'SELECT ledger.* FROM (' . $ledgerSql . ') AS ledger' . $where .
                ' ORDER BY ledger.tanggal_transaksi DESC, ledger.payment_ref DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
                $params
            )->getResultArray();
        }

        $pagination = [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRows' => $totalTransactionsCount,
            'perPage' => $perPage,
            'hasPrev' => $currentPage > 1,
            'hasNext' => $currentPage < $totalPages,
            'prevPage' => $currentPage - 1,
            'nextPage' => $currentPage + 1,
            'startRow' => $totalTransactionsCount > 0 ? $offset + 1 : 0,
            'endRow' => min($totalTransactionsCount, $offset + count($transactions)),
        ];

        $methodRows = $db->query(
            'SELECT ledger.metode, COUNT(*) AS record_count,
                    SUM(CASE WHEN ledger.status_transaksi = \'Terverifikasi\' THEN ledger.jumlah ELSE 0 END) AS verified_amount
             FROM (' . $ledgerSql . ') AS ledger' . $where .
            ' GROUP BY ledger.metode ORDER BY verified_amount DESC',
            $params
        )->getResultArray();

        // Biaya dicatat terpisah. HPP harus terhubung ke pesanan; biaya operasional
        // boleh berdiri sendiri atau ditautkan ke pesanan tertentu.
        $expenseRows = $db->table('financial_expenses fe')
            ->select('fe.*, o.kode_pesanan, o.nama_pemesan')
            ->join('orders o', 'o.id = fe.order_id', 'left')
            ->where('fe.tanggal_biaya >=', $startDate)
            ->where('fe.tanggal_biaya <=', $endDate)
            ->orderBy('fe.tanggal_biaya', 'DESC')
            ->orderBy('fe.id', 'DESC')
            ->limit(300)
            ->get()->getResultArray();

        $expenseSummaryRows = $db->query(
            'SELECT jenis_biaya, SUM(jumlah) AS total
             FROM financial_expenses
             WHERE status = ? AND tanggal_biaya >= ? AND tanggal_biaya <= ?
             GROUP BY jenis_biaya',
            ['Aktif', $startDate, $endDate]
        )->getResultArray();
        $expenseTotals = ['HPP' => 0.0, 'Operasional' => 0.0];
        foreach ($expenseSummaryRows as $expenseSummaryRow) {
            $kind = (string) ($expenseSummaryRow['jenis_biaya'] ?? '');
            if (array_key_exists($kind, $expenseTotals)) $expenseTotals[$kind] = (float) ($expenseSummaryRow['total'] ?? 0);
        }

        $ordersForExpense = $db->table('orders')
            ->select('id, kode_pesanan, nama_pemesan, tanggal_acara, total')
            ->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();

        $monthlyRevenueRows = $db->query(
            'SELECT DATE_FORMAT(ledger.tanggal_transaksi, \'%Y-%m\') AS month_key,
                    SUM(CASE WHEN ledger.status_transaksi = \'Terverifikasi\' THEN ledger.jumlah ELSE 0 END) AS revenue
             FROM (' . $ledgerSql . ') AS ledger' . $where .
            ' GROUP BY DATE_FORMAT(ledger.tanggal_transaksi, \'%Y-%m\') ORDER BY month_key',
            $params
        )->getResultArray();
        $monthlyExpenseRows = $db->query(
            'SELECT DATE_FORMAT(tanggal_biaya, \'%Y-%m\') AS month_key,
                    SUM(CASE WHEN jenis_biaya = \'HPP\' THEN jumlah ELSE 0 END) AS hpp,
                    SUM(CASE WHEN jenis_biaya = \'Operasional\' THEN jumlah ELSE 0 END) AS operational
             FROM financial_expenses
             WHERE status = ? AND tanggal_biaya >= ? AND tanggal_biaya <= ?
             GROUP BY DATE_FORMAT(tanggal_biaya, \'%Y-%m\') ORDER BY month_key',
            ['Aktif', $startDate, $endDate]
        )->getResultArray();

        $revenueByMonth = [];
        foreach ($monthlyRevenueRows as $row) $revenueByMonth[(string) $row['month_key']] = (float) $row['revenue'];
        $expenseByMonth = [];
        foreach ($monthlyExpenseRows as $row) {
            $expenseByMonth[(string) $row['month_key']] = [
                'hpp' => (float) ($row['hpp'] ?? 0),
                'operational' => (float) ($row['operational'] ?? 0),
            ];
        }

        $chart = [];
        $cursor = new \DateTimeImmutable(substr($startDate, 0, 7) . '-01');
        $lastMonth = new \DateTimeImmutable(substr($endDate, 0, 7) . '-01');
        $monthCount = 0;
        while ($cursor <= $lastMonth && $monthCount < 36) {
            $key = $cursor->format('Y-m');
            $costs = $expenseByMonth[$key] ?? ['hpp' => 0.0, 'operational' => 0.0];
            $chart[] = [
                'key' => $key,
                'label' => $cursor->format('M Y'),
                'revenue' => $revenueByMonth[$key] ?? 0.0,
                'hpp' => $costs['hpp'],
                'operational' => $costs['operational'],
            ];
            $cursor = $cursor->modify('+1 month');
            $monthCount++;
        }
        $chartMax = 0.0;
        foreach ($chart as $point) $chartMax = max($chartMax, $point['revenue'], $point['hpp'], $point['operational']);

        $revenue = (float) ($summary['verified_revenue'] ?? 0);
        $hpp = $expenseTotals['HPP'];
        $operational = $expenseTotals['Operasional'];

        return [
            'filters' => [
                'periode' => $period, 'tanggal' => $date, 'minggu' => $week, 'bulan' => $month, 'tahun' => $year,
                'tanggal_mulai' => $from, 'tanggal_selesai' => $to,
                'kanal' => $channel, 'status_transaksi' => $transactionStatus,
                'tanggal_awal_hasil' => $startDate, 'tanggal_akhir_hasil' => $endDate,
            ],
            'filterQuery' => [
                'periode' => $period, 'tanggal' => $date, 'minggu' => $week, 'bulan' => $month, 'tahun' => $year,
                'tanggal_mulai' => $from, 'tanggal_selesai' => $to,
                'kanal' => $channel, 'status_transaksi' => $transactionStatus,
            ],
            'summary' => [
                'transactionCount' => (int) ($summary['transaction_count'] ?? 0),
                'paymentRecordCount' => count($transactions),
                'verifiedPaymentCount' => (int) ($summary['verified_payment_count'] ?? 0),
                'revenue' => $revenue,
                'hpp' => $hpp,
                'operational' => $operational,
                'totalExpenses' => $hpp + $operational,
                'grossProfit' => $revenue - $hpp,
                'netCashResult' => $revenue - $hpp - $operational,
            ],
            'transactions' => $transactions,
            'pagination' => $pagination,
            'expenseRows' => $expenseRows,
            'ordersForExpense' => $ordersForExpense,
            'methodRows' => $methodRows,
            'chart' => $chart,
            'chartMax' => $chartMax,
        ];
    }

    public function financialReports(): string
    {
        return $this->render('financial-reports', 'Laporan dan Keuangan', [
            'report' => $this->financialReportData(),
        ]);
    }

    /**
     * Halaman cetak menggunakan fitur Print browser. Dari dialog cetak, admin
     * dapat memilih "Save as PDF" tanpa memasang library PDF eksternal.
     */
    public function financialReportPdf()
    {
        return $this->response
            ->setContentType('text/html; charset=UTF-8')
            ->setBody(view('admin/financial-report-print', [
                'report' => $this->financialReportData(true),
                'adminPhone' => (string) session()->get('admin_phone'),
            ]));
    }

    public function createFinancialExpense(): RedirectResponse
    {
        if (! $this->validate([
            'jenis_biaya' => 'required|in_list[HPP,Operasional]',
            'kategori' => 'required|min_length[2]|max_length[80]',
            'deskripsi' => 'required|min_length[3]|max_length[255]',
            'jumlah' => 'required|decimal|greater_than[0]',
            'tanggal_biaya' => 'required|valid_date[Y-m-d]',
            'order_id' => 'permit_empty|is_natural_no_zero',
        ])) {
            return redirect()->to('/admin/laporan')->withInput()->with('errors', $this->validator->getErrors());
        }

        $kind = (string) $this->request->getPost('jenis_biaya');
        $orderIdRaw = trim((string) $this->request->getPost('order_id'));
        $orderId = $orderIdRaw === '' ? null : (int) $orderIdRaw;
        if ($kind === 'HPP' && ($orderId === null || $orderId < 1)) {
            return redirect()->to('/admin/laporan')->withInput()->with('error', 'Biaya HPP harus dikaitkan dengan pesanan agar perhitungan per acara dapat ditelusuri.');
        }

        $db = db_connect();
        if ($orderId !== null && $db->table('orders')->where('id', $orderId)->countAllResults() !== 1) {
            return redirect()->to('/admin/laporan')->withInput()->with('error', 'Pesanan yang dipilih tidak ditemukan.');
        }

        $inserted = $db->table('financial_expenses')->insert([
            'order_id' => $orderId,
            'jenis_biaya' => $kind,
            'kategori' => trim((string) $this->request->getPost('kategori')),
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')),
            'jumlah' => round((float) $this->request->getPost('jumlah'), 2),
            'tanggal_biaya' => (string) $this->request->getPost('tanggal_biaya'),
            'status' => 'Aktif',
            'dicatat_oleh' => (int) session()->get('admin_id'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($inserted === false) {
            log_message('error', 'Pencatatan biaya keuangan gagal: {errors}', ['errors' => json_encode($db->error())]);
            return redirect()->to('/admin/laporan')->withInput()->with('error', 'Biaya gagal disimpan. Periksa data dan koneksi database.');
        }
        return redirect()->to('/admin/laporan')->with('success', 'Biaya berhasil dicatat ke laporan keuangan.');
    }

    /** Biaya tidak dihapus permanen; pembatalan menyimpan alasan dan identitas admin. */
    public function voidFinancialExpense(int $id): RedirectResponse
    {
        if (! $this->validate(['alasan_pembatalan' => 'required|min_length[5]|max_length[500]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = db_connect();
        $expense = $db->table('financial_expenses')->where('id', $id)->get()->getRowArray();
        if ($expense === null) return redirect()->to('/admin/laporan')->with('error', 'Catatan biaya tidak ditemukan.');
        if (($expense['status'] ?? '') !== 'Aktif') return redirect()->to('/admin/laporan')->with('error', 'Catatan biaya ini sudah dibatalkan sebelumnya.');

        $updated = $db->table('financial_expenses')->where('id', $id)->where('status', 'Aktif')->update([
            'status' => 'Dibatalkan',
            'alasan_pembatalan' => trim((string) $this->request->getPost('alasan_pembatalan')),
            'dibatalkan_oleh' => (int) session()->get('admin_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($updated === false) return redirect()->to('/admin/laporan')->with('error', 'Catatan biaya gagal dibatalkan.');
        return redirect()->to('/admin/laporan')->with('success', 'Catatan biaya dibatalkan dan tetap tersimpan untuk jejak audit.');
    }

    private function packageRules(): array
    {
        return [
            'nama_paket' => 'required|min_length[3]|max_length[180]',
            'kategori' => 'required|in_list[pernikahan,duka]',
            'jenis_paket' => 'required|in_list[biasa,lengkap,tambahan]',
            'lokasi' => 'required|max_length[120]',
            'deskripsi' => 'permit_empty|max_length[5000]',
            'harga' => 'required|numeric|greater_than[0]',
            'aktif' => 'required|in_list[0,1]',
        ];
    }

    private function packagePayload(string $imageName): array
    {
        return [
            'nama_paket' => trim((string) $this->request->getPost('nama_paket')),
            'kategori' => (string) $this->request->getPost('kategori'),
            'jenis_paket' => (string) $this->request->getPost('jenis_paket'),
            'lokasi' => trim((string) $this->request->getPost('lokasi')),
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')),
            'harga' => (float) $this->request->getPost('harga'),
            'gambar' => $imageName,
            'aktif' => (int) $this->request->getPost('aktif'),
        ];
    }

    private function extraRules(): array
    {
        return [
            'nama' => 'required|min_length[2]|max_length[120]',
            'deskripsi' => 'permit_empty|max_length[5000]',
            'harga' => 'required|numeric|greater_than[0]',
            'stok' => 'required|integer|greater_than_equal_to[0]',
            'aktif' => 'required|in_list[0,1]',
        ];
    }

    private function extraPayload(string $imageName): array
    {
        return [
            'nama' => trim((string) $this->request->getPost('nama')),
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')),
            'harga' => (float) $this->request->getPost('harga'),
            'stok' => (int) $this->request->getPost('stok'),
            'gambar' => $imageName,
            'aktif' => (int) $this->request->getPost('aktif'),
        ];
    }

    /**
     * Upload gambar katalog ke public/assets/images.
     * Nama file dibangkitkan server; nama/path dari browser tidak dipercaya.
     * Return filename lama ketika edit tanpa memilih file baru.
     *
     * @return array{filename:string,newFilename:?string,error:?string}
     */
    private function storeCatalogImage(bool $required, ?string $currentFilename = null): array
    {
        $file = $this->request->getFile('gambar_file');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [
                'filename' => (string) ($currentFilename ?? ''),
                'newFilename' => null,
                'error' => $required ? 'Silakan pilih gambar terlebih dahulu.' : null,
            ];
        }

        if (! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'File gambar gagal diunggah. Silakan pilih ulang.'];
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'Ukuran gambar maksimal 5 MB.'];
        }

        $imageInfo = @getimagesize($file->getTempName());
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! isset($extensions[$mime])) {
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.'];
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || ($width * $height) > 40000000) {
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'Dimensi gambar tidak valid atau terlalu besar.'];
        }

        $targetDirectory = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images';
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0755, true) && ! is_dir($targetDirectory)) {
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'Folder penyimpanan gambar tidak dapat dibuat.'];
        }

        try {
            $newFilename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
            $file->move($targetDirectory, $newFilename, false);
        } catch (\Throwable $exception) {
            log_message('error', 'Catalog image upload failed: {message}', ['message' => $exception->getMessage()]);
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'Gambar gagal disimpan ke server. Periksa izin folder public/assets/images.'];
        }

        if (! $file->hasMoved() || ! is_file($targetDirectory . DIRECTORY_SEPARATOR . $newFilename)) {
            return ['filename' => (string) ($currentFilename ?? ''), 'newFilename' => null, 'error' => 'Gambar belum berhasil disimpan. Silakan coba kembali.'];
        }

        return ['filename' => $newFilename, 'newFilename' => $newFilename, 'error' => null];
    }

    /** Hanya menghapus file baru milik upload yang gagal disimpan ke database. */
    private function removeNewCatalogImage(?string $filename): void
    {
        if ($filename === null || preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $filename) !== 1) {
            return;
        }

        $path = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    // =========================================================================
    // ATURAN ONGKOS KIRIM
    // =========================================================================

    public function shippingZones(): string
    {
        $model = new ShippingZoneModel();
        return $this->render('shipping-zones', 'Aturan Ongkos Kirim', [
            'allKecamatan' => $model->getAllKecamatan(),
            'allKelurahan' => $model->getAllKelurahan(),
        ]);
    }

    public function newKecamatan(): string
    {
        return $this->render('shipping-kecamatan-form', 'Tambah Kecamatan Baru', [
            'formAction' => base_url('admin/ongkos-kirim/kecamatan'),
            'isEdit'     => false,
        ]);
    }

    public function createKecamatan(): RedirectResponse
    {
        $nama = trim((string) $this->request->getPost('nama_kecamatan'));
        if ($nama === '' || mb_strlen($nama) > 120) {
            return redirect()->back()->withInput()->with('error', 'Nama kecamatan wajib diisi, maksimal 120 karakter.');
        }
        $model = new ShippingZoneModel();
        $model->saveKecamatan($nama);
        return redirect()->to('/admin/ongkos-kirim')->with('success', 'Kecamatan "' . $nama . '" berhasil ditambahkan.');
    }

    public function editKecamatan(int $id): string
    {
        $db  = db_connect();
        $kec = $db->table('kecamatan')->where('id', $id)->get()->getRowArray();
        if ($kec === null) {
            return redirect()->to('/admin/ongkos-kirim')->with('error', 'Kecamatan tidak ditemukan.'); // @phpstan-ignore-line
        }
        return $this->render('shipping-kecamatan-form', 'Ubah Kecamatan', [
            'formAction' => base_url('admin/ongkos-kirim/kecamatan/' . $id),
            'isEdit'     => true,
            'kecItem'    => $kec,
        ]);
    }

    public function updateKecamatan(int $id): RedirectResponse
    {
        $nama = trim((string) $this->request->getPost('nama_kecamatan'));
        if ($nama === '' || mb_strlen($nama) > 120) {
            return redirect()->back()->withInput()->with('error', 'Nama kecamatan wajib diisi, maksimal 120 karakter.');
        }
        (new ShippingZoneModel())->updateKecamatan($id, $nama);
        return redirect()->to('/admin/ongkos-kirim')->with('success', 'Kecamatan berhasil diperbarui.');
    }

    public function deleteKecamatan(int $id): RedirectResponse
    {
        (new ShippingZoneModel())->deleteKecamatan($id);
        return redirect()->to('/admin/ongkos-kirim')->with('success', 'Kecamatan dan seluruh kelurahan di bawahnya berhasil dihapus.');
    }

    public function newKelurahan(): string
    {
        return $this->render('shipping-kelurahan-form', 'Tambah Kelurahan Baru', [
            'formAction'    => base_url('admin/ongkos-kirim/kelurahan'),
            'isEdit'        => false,
            'kecamatanList' => (new ShippingZoneModel())->getAllKecamatan(),
        ]);
    }

    public function createKelurahan(): RedirectResponse
    {
        $kecId = (int) $this->request->getPost('kecamatan_id');
        $nama  = trim((string) $this->request->getPost('nama_kelurahan'));
        $ongkos = (float) $this->request->getPost('ongkos_kirim');

        if ($kecId < 1 || $nama === '' || mb_strlen($nama) > 120 || $ongkos < 0) {
            return redirect()->back()->withInput()->with('error', 'Semua field wajib diisi dengan benar. Pilih kecamatan dan isi nama kelurahan.');
        }
        (new ShippingZoneModel())->saveKelurahan($kecId, $nama, $ongkos);
        return redirect()->to('/admin/ongkos-kirim')->with('success', 'Kelurahan "' . $nama . '" berhasil ditambahkan.');
    }

    public function editKelurahan(int $id): string
    {
        $model = new ShippingZoneModel();
        $kel   = $model->getKelurahanDetail($id);
        if ($kel === null) {
            return redirect()->to('/admin/ongkos-kirim')->with('error', 'Kelurahan tidak ditemukan.'); // @phpstan-ignore-line
        }
        return $this->render('shipping-kelurahan-form', 'Ubah Kelurahan', [
            'formAction'    => base_url('admin/ongkos-kirim/kelurahan/' . $id),
            'isEdit'        => true,
            'kelItem'       => $kel,
            'kecamatanList' => $model->getAllKecamatan(),
        ]);
    }

    public function updateKelurahan(int $id): RedirectResponse
    {
        $kecId  = (int) $this->request->getPost('kecamatan_id');
        $nama   = trim((string) $this->request->getPost('nama_kelurahan'));
        $ongkos = (float) $this->request->getPost('ongkos_kirim');

        if ($kecId < 1 || $nama === '' || mb_strlen($nama) > 120 || $ongkos < 0) {
            return redirect()->back()->withInput()->with('error', 'Semua field wajib diisi dengan benar.');
        }
        (new ShippingZoneModel())->updateKelurahan($id, $kecId, $nama, $ongkos);
        return redirect()->to('/admin/ongkos-kirim')->with('success', 'Kelurahan berhasil diperbarui.');
    }

    public function deleteKelurahan(int $id): RedirectResponse
    {
        (new ShippingZoneModel())->deleteKelurahan($id);
        return redirect()->to('/admin/ongkos-kirim')->with('success', 'Kelurahan berhasil dihapus.');
    }

}
