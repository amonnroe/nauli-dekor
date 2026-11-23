<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model untuk mengelola aturan ongkos kirim berdasarkan kecamatan dan kelurahan.
 */
class ShippingZoneModel extends Model
{
    protected $table      = 'kecamatan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama_kecamatan'];
    protected $useTimestamps = true;

    /**
     * Ambil semua kecamatan, diurutkan nama.
     */
    public function getAllKecamatan(): array
    {
        return $this->orderBy('nama_kecamatan', 'ASC')->findAll();
    }

    /**
     * Ambil semua kelurahan untuk kecamatan tertentu.
     */
    public function getKelurahanByKecamatan(int $kecamatanId): array
    {
        $db = db_connect();
        return $db->table('kelurahan')
            ->where('kecamatan_id', $kecamatanId)
            ->orderBy('nama_kelurahan', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Ambil satu kelurahan berdasarkan ID, termasuk nama kecamatan induknya.
     */
    public function getKelurahanDetail(int $kelurahanId): ?array
    {
        $db = db_connect();
        $row = $db->table('kelurahan k')
            ->select('k.*, kec.nama_kecamatan')
            ->join('kecamatan kec', 'kec.id = k.kecamatan_id', 'left')
            ->where('k.id', $kelurahanId)
            ->get()->getRowArray();
        return $row ?: null;
    }

    /**
     * Simpan kecamatan baru. Kembalikan ID yang baru dibuat.
     */
    public function saveKecamatan(string $nama): int
    {
        $db = db_connect();
        $db->table('kecamatan')->insert([
            'nama_kecamatan' => trim($nama),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    /**
     * Simpan kelurahan baru.
     */
    public function saveKelurahan(int $kecamatanId, string $nama, float $ongkos): int
    {
        $db = db_connect();
        $db->table('kelurahan')->insert([
            'kecamatan_id'  => $kecamatanId,
            'nama_kelurahan' => trim($nama),
            'ongkos_kirim'  => round($ongkos, 2),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    /**
     * Update kecamatan.
     */
    public function updateKecamatan(int $id, string $nama): void
    {
        $db = db_connect();
        $db->table('kecamatan')->where('id', $id)->update([
            'nama_kecamatan' => trim($nama),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update kelurahan.
     */
    public function updateKelurahan(int $id, int $kecamatanId, string $nama, float $ongkos): void
    {
        $db = db_connect();
        $db->table('kelurahan')->where('id', $id)->update([
            'kecamatan_id'  => $kecamatanId,
            'nama_kelurahan' => trim($nama),
            'ongkos_kirim'  => round($ongkos, 2),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Hapus kecamatan beserta seluruh kelurahan di bawahnya.
     */
    public function deleteKecamatan(int $id): void
    {
        $db = db_connect();
        $db->table('kelurahan')->where('kecamatan_id', $id)->delete();
        $db->table('kecamatan')->where('id', $id)->delete();
    }

    /**
     * Hapus kelurahan tunggal.
     */
    public function deleteKelurahan(int $id): void
    {
        $db = db_connect();
        $db->table('kelurahan')->where('id', $id)->delete();
    }

    /**
     * Semua kelurahan dengan nama kecamatan, untuk tabel di admin.
     */
    public function getAllKelurahan(): array
    {
        $db = db_connect();
        return $db->table('kelurahan k')
            ->select('k.id, k.kecamatan_id, kec.nama_kecamatan, k.nama_kelurahan, k.ongkos_kirim, k.created_at')
            ->join('kecamatan kec', 'kec.id = k.kecamatan_id', 'left')
            ->orderBy('kec.nama_kecamatan, k.nama_kelurahan', 'ASC')
            ->get()->getResultArray();
    }
}
