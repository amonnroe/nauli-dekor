<?php

namespace App\Libraries;

class AprioriService
{
    private float $minSupport = 5.0;     // minimal muncul di 5% dari total transaksi paket
    private float $minConfidence = 30.0; // minimal 30% kepastian dibeli bersamaan

    /**
     * Melatih ulang model Apriori berdasarkan seluruh riwayat transaksi valid.
     */
    public function trainRules(): void
    {
        $db = db_connect();

        $orders = $db->table('orders')
            ->select('id, package_id')
            ->where('package_id IS NOT NULL')
            ->whereNotIn('status_pesanan', ['Dibatalkan'])
            ->whereNotIn('status_pembayaran', ['Ditolak'])
            ->get()
            ->getResultArray();

        $totalTransactions = count($orders);
        if ($totalTransactions === 0) {
            return;
        }

        $orderIds = array_column($orders, 'id');
        $orderExtras = $db->table('order_extras')
            ->select('order_id, extra_id')
            ->whereIn('order_id', $orderIds)
            ->get()
            ->getResultArray();

        $extrasByOrder = [];
        foreach ($orderExtras as $row) {
            $oid = (int) $row['order_id'];
            $eid = (int) $row['extra_id'];
            $extrasByOrder[$oid][$eid] = true;
        }

        $packageCounts = [];
        $pairCounts = [];

        foreach ($orders as $order) {
            $oid = (int) $order['id'];
            $pkgId = (int) $order['package_id'];

            $packageCounts[$pkgId] = ($packageCounts[$pkgId] ?? 0) + 1;

            if (! empty($extrasByOrder[$oid])) {
                foreach (array_keys($extrasByOrder[$oid]) as $extraId) {
                    $pairCounts[$pkgId][$extraId] = ($pairCounts[$pkgId][$extraId] ?? 0) + 1;
                }
            }
        }

        $newRules = [];
        $now = date('Y-m-d H:i:s');

        foreach ($pairCounts as $pkgId => $extras) {
            $pkgFreq = $packageCounts[$pkgId] ?? 0;
            if ($pkgFreq === 0) {
                continue;
            }

            foreach ($extras as $extraId => $pairFreq) {
                $support = round(($pairFreq / $totalTransactions) * 100, 2);
                $confidence = round(($pairFreq / $pkgFreq) * 100, 2);

                if ($support >= $this->minSupport && $confidence >= $this->minConfidence) {
                    $newRules[] = [
                        'paket_utama_id'       => $pkgId,
                        'rekomendasi_extra_id' => $extraId,
                        'nilai_support'        => $support,
                        'nilai_confidence'     => $confidence,
                        'updated_at'           => $now,
                    ];
                }
            }
        }

        $db->transStart();
        $db->table('aturan_asosiasi_apriori')->truncate();
        if ($newRules !== []) {
            $db->table('aturan_asosiasi_apriori')->insertBatch($newRules);
        }
        $db->transComplete();
    }

    /**
     * Mengambil daftar rekomendasi item tambahan (extras) untuk paket tertentu.
     */
    public function getRecommendationsForPackage(int $packageId, array $excludeExtraIds = [], int $limit = 4): array
    {
        $db = db_connect();
        $builder = $db->table('aturan_asosiasi_apriori a')
            ->select('e.*, a.nilai_support, a.nilai_confidence')
            ->join('extras e', 'e.id = a.rekomendasi_extra_id')
            ->where('a.paket_utama_id', $packageId)
            ->where('e.aktif', 1)
            ->where('e.stok >', 0)
            ->orderBy('a.nilai_confidence', 'DESC')
            ->orderBy('a.nilai_support', 'DESC')
            ->limit($limit);

        if ($excludeExtraIds !== []) {
            $builder->whereNotIn('e.id', $excludeExtraIds);
        }

        return $builder->get()->getResultArray();
    }
}